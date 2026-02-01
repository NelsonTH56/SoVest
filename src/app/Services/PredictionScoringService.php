<?php
/**
 * SoVest Prediction Scoring Service - V3 Algorithm
 *
 * This service evaluates stock predictions against actual performance
 * and calculates user reputation scores based on prediction accuracy.
 *
 * V3 Features:
 * - Alpha calculation (performance vs benchmark)
 * - Dynamic learning rate based on user experience
 * - Thesis quality multiplier
 * - Bell curve score distribution
 */

namespace App\Services;

use App\Services\Interfaces\PredictionScoringServiceInterface;
use App\Models\User;
use App\Models\Prediction;
use App\Models\Stock;
use App\Models\StockPrice;
use App\Mail\PredictionEvaluated;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\Capsule\Manager as DB;
use Carbon\Carbon;

class PredictionScoringService implements PredictionScoringServiceInterface {

    // --- V3 ALGORITHM PARAMETERS ---

    // Score system constants
    private const BASE_VALUE = 50.0;
    private const MIN_SCORE = 0.0;
    private const MAX_SCORE = 1000.0;
    private const TARGET_MEAN_SCORE = 500.0;

    // Penalty & bonus constants
    private const PENALTY_SCALING_FACTOR = 250.0;
    private const MAX_PENALTY_CLIP = 3.0;
    private const ACCURACY_THRESHOLD = 0.6;
    private const TIME_BONUS_SCALING_FACTOR = 5.0;
    private const MAX_TIME_DAYS = 365.0;
    private const DIRECTIONAL_BONUS = 8.0;
    private const MAGNITUDE_BONUS_SCALING = 50.0;

    // V2: Anti-gaming constants
    private const UNDER_PREDICTION_MULTIPLIER = 1.2;
    private const VOLATILITY_MULTIPLIER_BASE = 0.5;

    // V3: Alpha score
    private const ALPHA_SENSITIVITY = 0.5;

    // V3: Dynamic learning rate
    private const DLR_NEW_USER_RATE = 0.30;
    private const DLR_NEW_USER_PREDICTIONS = 20;
    private const DLR_VETERAN_RATE = 0.05;
    private const DLR_VETERAN_PREDICTIONS = 200;

    // V3: Thesis multiplier map
    private const THESIS_MULTIPLIER_MAP = [
        1 => 0.80,  // 20% penalty for a 1-star thesis
        2 => 0.90,  // 10% penalty
        3 => 1.00,  // No change for a 3-star (average) thesis
        4 => 1.10,  // 10% bonus
        5 => 1.20,  // 20% bonus for a 5-star thesis
    ];

    // Default benchmark symbol (S&P 500 ETF)
    private const BENCHMARK_SYMBOL = 'SPY';

    private $stockService;

    public function __construct(StockDataService $stockService) {
        $this->stockService = $stockService;
    }

    /**
     * Evaluate all active predictions that have passed their end date
     */
    public function evaluateActivePredictions() {
        $results = [
            'total' => 0,
            'evaluated' => 0,
            'errors' => 0
        ];

        try {
            // Get all active predictions that have passed their end date
            $predictions = Prediction::where('is_active', 1)
                ->where('end_date', '<', Carbon::today())
                ->whereNull('accuracy')
                ->with(['stock', 'user'])
                ->get();

            $results['total'] = count($predictions);

            foreach ($predictions as $prediction) {
                try {
                    $this->evaluatePrediction([
                        'prediction_id' => $prediction->prediction_id,
                        'user_id' => $prediction->user_id,
                        'symbol' => $prediction->stock->symbol,
                        'prediction_type' => $prediction->prediction_type,
                        'target_price' => $prediction->target_price,
                        'prediction_date' => $prediction->prediction_date,
                        'end_date' => $prediction->end_date,
                        'thesis_rating' => $prediction->thesis_rating ?? 3,
                    ]);
                    $results['evaluated']++;
                } catch (\Exception $e) {
                    $results['errors']++;
                    error_log("Error evaluating prediction ID {$prediction->prediction_id}: " . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            error_log("Error fetching predictions: " . $e->getMessage());
            throw $e;
        }

        return $results;
    }

    /**
     * Evaluate a single prediction using V3 algorithm
     *
     * @param array $prediction Prediction data
     * @return bool Success status
     */
    public function evaluatePrediction($prediction) {
        $predictionId = $prediction['prediction_id'];
        $userId = $prediction['user_id'];
        $symbol = $prediction['symbol'];
        $predictionType = $prediction['prediction_type'];
        $targetPrice = (float) $prediction['target_price'];
        $startDate = $prediction['prediction_date'];
        $endDate = $prediction['end_date'];
        $thesisRating = $prediction['thesis_rating'] ?? 3;

        try {
            // Get stock prices at prediction time and at end date
            $startPrice = $this->getStockPriceAtDate($symbol, $startDate);
            $endPrice = $this->getStockPriceAtDate($symbol, $endDate);

            if (!$startPrice || !$endPrice) {
                throw new \Exception("Unable to retrieve stock prices for $symbol");
            }

            // Calculate time span in days
            $startDateTime = Carbon::parse($startDate);
            $endDateTime = Carbon::parse($endDate);
            $tDays = $startDateTime->diffInDays($endDateTime);

            // Get benchmark performance for alpha calculation
            $benchmarkPerformance = $this->getBenchmarkPerformance($startDate, $endDate);

            // Calculate volatility (simplified - using price range as proxy)
            $volatility = $this->calculateVolatility($symbol, $startDate, $endDate);

            // Determine predicted price based on prediction type
            // For Bullish: target_price is where they think it will go (up)
            // For Bearish: target_price is where they think it will go (down)
            $predictedPrice = $targetPrice;

            // Calculate the V3 prediction grade
            $pointChange = $this->calculatePredictionGrade(
                $predictedPrice,
                $endPrice,
                $startPrice,
                $tDays,
                $volatility,
                $benchmarkPerformance,
                $thesisRating
            );

            // Convert point change to 0-100 accuracy scale for backwards compatibility
            // Map the point change (-inf to +inf) to 0-100 using a sigmoid-like function
            $accuracy = $this->pointChangeToAccuracy($pointChange);

            // Update prediction with accuracy and benchmark data
            $predictionModel = Prediction::find($predictionId);
            $predictionModel->accuracy = $accuracy;
            $predictionModel->is_active = 0;
            $predictionModel->benchmark_performance = $benchmarkPerformance;
            $predictionModel->save();

            // Calculate reputation change using V3 algorithm
            $reputationChange = $this->calculateReputationChange($userId, $pointChange);

            // Update user reputation score
            $this->updateUserReputation($userId, $accuracy, $pointChange);

            // Send email notification to user
            $this->sendEvaluationEmail($predictionModel, $accuracy, $reputationChange);

            return true;
        } catch (\Exception $e) {
            error_log("Error evaluating prediction: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculate the V3 prediction grade
     *
     * @param float $pPred Predicted price
     * @param float $pActual Actual end price
     * @param float $pInitial Initial price at prediction time
     * @param int $tDays Number of days
     * @param float $volatility Stock volatility
     * @param float $benchmarkPerformance Benchmark performance over the period
     * @param int $thesisRating Thesis quality rating (1-5)
     * @return float Point change for the prediction
     */
    private function calculatePredictionGrade($pPred, $pActual, $pInitial, $tDays, $volatility, $benchmarkPerformance, $thesisRating) {
        if ($pActual <= 0 || $pPred <= 0 || $pInitial <= 0) {
            throw new \InvalidArgumentException("Prices must be > 0");
        }

        // --- 1. Calculate Base Penalty ---
        $logError = abs(log($pPred / $pActual));

        $multiplier = ($pPred <= $pActual) ? self::UNDER_PREDICTION_MULTIPLIER : 1.0;
        $asymmetricError = $logError * $multiplier;

        $scaledError = sqrt($asymmetricError);
        $clippedError = min($scaledError, self::MAX_PENALTY_CLIP);
        $penalty = self::PENALTY_SCALING_FACTOR * $clippedError;

        // --- 2. Calculate Bonuses (if direction is correct) ---
        $predDirection = $pPred - $pInitial;
        $actualDirection = $pActual - $pInitial;

        $directionalBonus = 0;
        $timeAccuracyBonus = 0;

        if (($predDirection * $actualDirection) > 0) { // Correct direction
            $directionalBonus = self::DIRECTIONAL_BONUS;
            $actualMagnitude = abs(($pActual - $pInitial) / $pInitial);
            $magnitudeBonus = self::MAGNITUDE_BONUS_SCALING * $actualMagnitude;
            $directionalBonus += $magnitudeBonus;

            $accuracyFactor = max(0.0, 1.0 - (sqrt($logError) / self::ACCURACY_THRESHOLD));
            $effectiveT = min($tDays, self::MAX_TIME_DAYS);
            $timeAccuracyBonus = self::TIME_BONUS_SCALING_FACTOR * sqrt($effectiveT) * $accuracyFactor;
        }

        // --- 3. Calculate Volatility Multiplier (V2 Anti-Gaming) ---
        $volatilityMultiplier = self::VOLATILITY_MULTIPLIER_BASE + $volatility;

        // --- 4. Calculate Base Prediction Score ---
        $predictionScore = self::BASE_VALUE - $penalty + $directionalBonus + $timeAccuracyBonus;
        $pointChange = ($predictionScore - self::BASE_VALUE) * $volatilityMultiplier;

        // --- 5. Apply V3 Multipliers ---
        $stockPerformance = ($pActual - $pInitial) / $pInitial;
        $alpha = $stockPerformance - $benchmarkPerformance;
        $alphaMultiplier = 1.0 + ($alpha * self::ALPHA_SENSITIVITY);

        $thesisMultiplier = self::THESIS_MULTIPLIER_MAP[$thesisRating] ?? 1.0;

        $finalPointChange = $pointChange * $alphaMultiplier * $thesisMultiplier;

        return $finalPointChange;
    }

    /**
     * Convert point change to 0-100 accuracy scale
     * Uses a sigmoid function to map any point change to 0-100
     *
     * @param float $pointChange The V3 point change
     * @return float Accuracy score between 0 and 100
     */
    private function pointChangeToAccuracy($pointChange) {
        // Use sigmoid to map point change to 0-100
        // Center around 50 (neutral), positive changes go above, negative below
        // Scale factor determines how quickly we approach 0 or 100
        $scaleFactor = 0.05; // Adjust for desired sensitivity
        $sigmoid = 1 / (1 + exp(-$pointChange * $scaleFactor));
        return $sigmoid * 100;
    }

    /**
     * Get the dynamic learning rate based on prediction count
     *
     * @param int $predictionCount Number of predictions the user has made
     * @return float Learning rate
     */
    private function getDynamicLearningRate($predictionCount) {
        if ($predictionCount <= self::DLR_NEW_USER_PREDICTIONS) {
            return self::DLR_NEW_USER_RATE;
        }
        if ($predictionCount >= self::DLR_VETERAN_PREDICTIONS) {
            return self::DLR_VETERAN_RATE;
        }

        // Linear interpolation for users between "new" and "veteran" status
        $progress = ($predictionCount - self::DLR_NEW_USER_PREDICTIONS) /
                   (self::DLR_VETERAN_PREDICTIONS - self::DLR_NEW_USER_PREDICTIONS);
        $rateRange = self::DLR_NEW_USER_RATE - self::DLR_VETERAN_RATE;
        return self::DLR_NEW_USER_RATE - ($progress * $rateRange);
    }

    /**
     * Calculate reputation change using V3 dynamic learning rate and bell curve
     *
     * @param int $userId User ID
     * @param float $pointChange The raw point change from prediction grade
     * @return float The actual reputation change to apply
     */
    private function calculateReputationChange($userId, $pointChange) {
        // Get user's current score and prediction count
        $user = User::find($userId);
        $currentScore = (float) ($user->reputation_score ?? self::TARGET_MEAN_SCORE);
        $predictionCount = Prediction::where('user_id', $userId)
            ->whereNotNull('accuracy')
            ->count();

        // V3 - Dynamic Learning Rate
        $learningRate = $this->getDynamicLearningRate($predictionCount);

        // Bell Curve Damping Factor
        $dampingFactor = 1 - pow(abs($currentScore - self::TARGET_MEAN_SCORE) / (self::MAX_SCORE - self::TARGET_MEAN_SCORE), 2);

        // Apply learning rate and damping
        $adjustedChange = $pointChange * $dampingFactor * $learningRate;

        return $adjustedChange;
    }

    /**
     * Update user reputation score using V3 algorithm
     *
     * @param int $userId User ID
     * @param float $accuracy Accuracy of prediction (0-100, for backwards compat)
     * @param float|null $pointChange V3 point change (if null, falls back to V2 calculation)
     * @return bool Success status
     */
    public function updateUserReputation($userId, $accuracy, $pointChange = null) {
        try {
            $user = User::find($userId);
            $currentScore = (float) ($user->reputation_score ?? self::TARGET_MEAN_SCORE);

            if ($pointChange !== null) {
                // V3 algorithm
                $reputationChange = $this->calculateReputationChange($userId, $pointChange);
                $newScore = $currentScore + $reputationChange;

                // Clamp score within bounds
                $newScore = max(self::MIN_SCORE, min(self::MAX_SCORE, $newScore));
            } else {
                // V2 fallback for backwards compatibility
                $reputationChange = $this->calculateReputationPoints($accuracy);
                $newScore = $currentScore + $reputationChange;
            }

            $user->reputation_score = $newScore;
            $user->save();

            // Clear leaderboard cache when user reputation changes
            cache()->forget('leaderboard:top_users');
            cache()->forget("user:stats:{$userId}");

            return true;
        } catch (\Exception $e) {
            error_log("Error updating user reputation: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculate reputation points based on prediction accuracy (V2 fallback)
     *
     * @param float $accuracy Accuracy score (0-100)
     * @return int Reputation points
     */
    private function calculateReputationPoints($accuracy) {
        if ($accuracy >= 90) {
            return 10;
        } else if ($accuracy >= 70) {
            return 5;
        } else if ($accuracy >= 50) {
            return 2;
        } else if ($accuracy >= 30) {
            return 0;
        } else {
            return -2;
        }
    }

    /**
     * Get benchmark performance (e.g., S&P 500) over a period
     *
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return float Benchmark performance as a decimal (e.g., 0.05 for 5%)
     */
    private function getBenchmarkPerformance($startDate, $endDate) {
        try {
            $startPrice = $this->getStockPriceAtDate(self::BENCHMARK_SYMBOL, $startDate);
            $endPrice = $this->getStockPriceAtDate(self::BENCHMARK_SYMBOL, $endDate);

            if (!$startPrice || !$endPrice || $startPrice <= 0) {
                // Default to 0 if we can't get benchmark data
                error_log("Could not get benchmark prices for " . self::BENCHMARK_SYMBOL);
                return 0.0;
            }

            return ($endPrice - $startPrice) / $startPrice;
        } catch (\Exception $e) {
            error_log("Error getting benchmark performance: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Calculate stock volatility over a period
     * Uses a simplified approach based on price range
     *
     * @param string $symbol Stock symbol
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return float Volatility measure (typically 0.5 to 2.0)
     */
    private function calculateVolatility($symbol, $startDate, $endDate) {
        try {
            $symbol = strtoupper($symbol);
            $stock = Stock::where('symbol', $symbol)->first();

            if (!$stock) {
                return 1.0; // Default volatility
            }

            // Get price history for the period
            $prices = StockPrice::where('stock_id', $stock->stock_id)
                ->whereBetween('price_date', [
                    Carbon::parse($startDate)->format('Y-m-d'),
                    Carbon::parse($endDate)->format('Y-m-d')
                ])
                ->get();

            if ($prices->isEmpty()) {
                return 1.0;
            }

            // Calculate volatility as standard deviation of daily returns
            $closePrices = $prices->pluck('close_price')->toArray();

            if (count($closePrices) < 2) {
                return 1.0;
            }

            $returns = [];
            for ($i = 1; $i < count($closePrices); $i++) {
                if ($closePrices[$i - 1] > 0) {
                    $returns[] = ($closePrices[$i] - $closePrices[$i - 1]) / $closePrices[$i - 1];
                }
            }

            if (empty($returns)) {
                return 1.0;
            }

            $mean = array_sum($returns) / count($returns);
            $variance = 0;
            foreach ($returns as $return) {
                $variance += pow($return - $mean, 2);
            }
            $variance /= count($returns);
            $stdDev = sqrt($variance);

            // Annualize and normalize to roughly 0.5-2.0 range
            $annualizedVol = $stdDev * sqrt(252); // 252 trading days

            // Map to 0.5-2.0 range (low vol stocks ~0.5, high vol ~2.0)
            return max(0.5, min(2.0, $annualizedVol * 5 + 0.5));

        } catch (\Exception $e) {
            error_log("Error calculating volatility: " . $e->getMessage());
            return 1.0;
        }
    }

    /**
     * Send email notification to user about prediction evaluation
     *
     * @param Prediction $prediction The evaluated prediction
     * @param float $accuracy The accuracy score
     * @param float $reputationChange The reputation points gained/lost
     * @return void
     */
    private function sendEvaluationEmail($prediction, $accuracy, $reputationChange) {
        try {
            $user = User::find($prediction->user_id);
            $stock = Stock::find($prediction->stock_id);

            if (!$user || !$user->email || !$stock) {
                error_log("Cannot send email: Missing user or stock data for prediction {$prediction->prediction_id}");
                return;
            }

            $predictionData = [
                'prediction_id' => $prediction->prediction_id,
                'prediction_type' => $prediction->prediction_type,
                'target_price' => $prediction->target_price,
                'end_date' => $prediction->end_date,
                'reasoning' => $prediction->reasoning,
            ];

            $userData = [
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
            ];

            $stockData = [
                'stock_id' => $stock->stock_id,
                'symbol' => $stock->symbol,
                'company_name' => $stock->company_name,
            ];

            Mail::to($user->email)->queue(
                new PredictionEvaluated($predictionData, $userData, $stockData, $accuracy, $reputationChange)
            );

        } catch (\Exception $e) {
            error_log("Error sending evaluation email: " . $e->getMessage());
        }
    }

    /**
     * Get stock price at a specific date
     *
     * @param string $symbol Stock symbol
     * @param string $date Date to check
     * @return float|null Stock price or null if not found
     */
    private function getStockPriceAtDate($symbol, $date) {
        try {
            $date = date('Y-m-d', strtotime($date));
            $stock = Stock::where('symbol', $symbol)->first();

            if (!$stock) {
                error_log("Stock not found: $symbol");
                return null;
            }

            // Get the price record closest to the date (within 7 days)
            $price = StockPrice::where('stock_id', $stock->stock_id)
                ->where('price_date', '<=', $date)
                ->where('price_date', '>=', date('Y-m-d', strtotime($date . ' -7 days')))
                ->orderBy('price_date', 'desc')
                ->first();

            if ($price) {
                error_log("Found historical price for $symbol on {$price->price_date}: {$price->close_price}");
                return (float)$price->close_price;
            }

            // If no historical price found within 7 days, fetch from API
            error_log("No historical price found for $symbol near $date, fetching from API...");

            $fetchResult = $this->stockService->fetchAndStoreStockData($symbol);

            if ($fetchResult) {
                error_log("Successfully fetched and stored price for $symbol from API");

                $storedPrice = StockPrice::where('stock_id', $stock->stock_id)
                    ->orderBy('price_date', 'desc')
                    ->first();

                if ($storedPrice) {
                    error_log("Using stored price from {$storedPrice->price_date}: {$storedPrice->close_price}");
                    return (float)$storedPrice->close_price;
                }
            }

            error_log("Failed to fetch price for $symbol from API");
            return null;
        } catch (\Exception $e) {
            error_log("Error getting stock price at date: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get top users by reputation score
     *
     * @param int $limit Number of users to return
     * @return array Top users
     */
    public function getTopUsers($limit = 10) {
        try {
            $users = User::select([
                'id',
                'first_name',
                'last_name',
                'email',
                'reputation_score'
            ])
            ->orderBy('reputation_score', 'desc')
            ->limit($limit)
            ->get();

            $result = $users->map(function($user) {
                $predictionsCount = Prediction::where('user_id', $user->id)->count();

                $avgAccuracy = Prediction::where('user_id', $user->id)
                    ->whereNotNull('accuracy')
                    ->avg('accuracy');

                $userData = $user->toArray();
                $userData['predictions_count'] = $predictionsCount ?? 0;
                $userData['avg_accuracy'] = $avgAccuracy ?? 0;

                return $userData;
            })
            ->toArray();

            return $result;
        } catch (\Exception $e) {
            error_log("Error fetching top users: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get prediction stats for a user
     *
     * @param int $userId User ID
     * @return array User prediction stats
     */
    public function getUserPredictionStats($userId) {
        $stats = [
            'total' => 0,
            'total_predictions' => 0,
            'accurate' => 0,
            'inaccurate' => 0,
            'pending' => 0,
            'avg_accuracy' => 0,
            'reputation' => 0,
            'learning_rate' => 0,
        ];

        try {
            $total = Prediction::where('user_id', $userId)->count();
            $pending = Prediction::where('user_id', $userId)->whereNull('accuracy')->count();
            $accurate = Prediction::where('user_id', $userId)->where('accuracy', '>=', 50)->count();
            $inaccurate = Prediction::where('user_id', $userId)
                ->whereNotNull('accuracy')
                ->where('accuracy', '<', 50)
                ->count();
            $avgAccuracy = Prediction::where('user_id', $userId)
                ->whereNotNull('accuracy')
                ->avg('accuracy');

            $finalized = Prediction::where('user_id', $userId)
                ->whereNotNull('accuracy')
                ->count();

            $stats['total'] = $total;
            $stats['total_predictions'] = $finalized;
            $stats['pending'] = $pending;
            $stats['accurate'] = $accurate;
            $stats['inaccurate'] = $inaccurate;
            $stats['avg_accuracy'] = $avgAccuracy ? round((float)$avgAccuracy, 1) : 0;

            // V3: Include learning rate in stats
            $stats['learning_rate'] = round($this->getDynamicLearningRate($finalized) * 100, 1);

            $user = User::find($userId);
            if ($user) {
                $stats['reputation'] = (int)$user->reputation_score;
            }

            return $stats;
        } catch (\Exception $e) {
            error_log("Error getting user prediction stats: " . $e->getMessage());
            return $stats;
        }
    }
}
