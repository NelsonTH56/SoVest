<?php

namespace App\Jobs;

use App\Models\Prediction;
use App\Models\User;
use App\Models\Stock;
use App\Models\StockPrice;
use App\Mail\PredictionEvaluated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EvaluatePredictionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // --- V3 ALGORITHM PARAMETERS ---

    // SCORE SYSTEM CONSTANTS
    const BASE_VALUE = 50.0;
    const MIN_SCORE = 0.0;
    const MAX_SCORE = 1000.0;
    const TARGET_MEAN_SCORE = 500.0;

    // PENALTY & BONUS CONSTANTS
    const PENALTY_SCALING_FACTOR = 250.0;
    const MAX_PENALTY_CLIP = 3.0;
    const ACCURACY_THRESHOLD = 0.6;  // Scaled error level where time bonus becomes 0
    const TIME_BONUS_SCALING_FACTOR = 5.0;
    const MAX_TIME_DAYS = 365.0;
    const DIRECTIONAL_BONUS = 8.0;
    const MAGNITUDE_BONUS_SCALING = 50.0;

    // V2: ANTI-GAMING CONSTANTS
    const UNDER_PREDICTION_MULTIPLIER = 1.2;
    const VOLATILITY_MULTIPLIER_BASE = 0.5; // Base for volatility scaling

    // V3: NEW FEATURE CONSTANTS
    // 1. Alpha Score
    const ALPHA_SENSITIVITY = 0.5; // How much alpha affects the score change (0.5 = 50%)

    // 3. Dynamic Learning Rate
    const DLR_NEW_USER_RATE = 0.30;
    const DLR_NEW_USER_PREDICTIONS = 20;
    const DLR_VETERAN_RATE = 0.05;
    const DLR_VETERAN_PREDICTIONS = 200;

    // 4. Thesis Score
    const THESIS_MULTIPLIER_MAP = [
        1 => 0.80,  // 20% penalty for a 1-star thesis
        2 => 0.90,  // 10% penalty
        3 => 1.00,  // No change for a 3-star (average) thesis
        4 => 1.10,  // 10% bonus
        5 => 1.20,  // 20% bonus for a 5-star thesis
    ];

    protected $predictionId;

    /**
     * Create a new job instance.
     *
     * @param int $predictionId
     */
    public function __construct($predictionId)
    {
        $this->predictionId = $predictionId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $prediction = Prediction::with(['stock', 'user'])->find($this->predictionId);

            if (!$prediction) {
                Log::warning("Prediction {$this->predictionId} not found");
                return;
            }

            // Skip if already evaluated
            if ($prediction->accuracy !== null || !$prediction->is_active) {
                Log::info("Prediction {$this->predictionId} already evaluated");
                return;
            }

            // Get stock prices
            $startPrice = $this->getStockPriceAtDate($prediction->stock->symbol, $prediction->prediction_date);
            $endPrice = $this->getStockPriceAtDate($prediction->stock->symbol, $prediction->end_date);

            if (!$startPrice || !$endPrice) {
                Log::error("Unable to retrieve stock prices for {$prediction->stock->symbol}");
                return;
            }

            // Calculate prediction accuracy using new algorithm
            $result = $this->evaluatePrediction($prediction, $startPrice, $endPrice);

            // Update prediction
            $prediction->accuracy = $result['accuracy'];
            $prediction->is_active = 0;
            $prediction->save();

            // Update user score
            $user = $prediction->user;
            $oldScore = $user->reputation_score ?? self::TARGET_MEAN_SCORE;
            $newScore = $result['new_user_score'];
            $scoreChange = $newScore - $oldScore;

            $user->reputation_score = $newScore;
            $user->save();

            // Clear caches
            cache()->forget('leaderboard:top_users');
            cache()->forget("user:stats:{$user->id}");

            // Send email notification
            $this->sendEmailNotification($prediction, $result['accuracy'], $scoreChange);

            Log::info("Evaluated prediction {$this->predictionId}: Accuracy={$result['accuracy']}%, Score: {$oldScore} -> {$newScore}");

        } catch (\Exception $e) {
            Log::error("Error evaluating prediction {$this->predictionId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Evaluate a prediction using the V3 algorithm
     *
     * @param Prediction $prediction
     * @param float $startPrice
     * @param float $endPrice
     * @return array
     */
    protected function evaluatePrediction($prediction, $startPrice, $endPrice)
    {
        $user = $prediction->user;
        $currentScore = $user->reputation_score ?? self::TARGET_MEAN_SCORE;

        // Get prediction parameters
        $pPred = (float) $prediction->target_price ?? $endPrice; // Use target price if available
        $pActual = $endPrice;
        $pInitial = $startPrice;

        // Calculate days in advance
        $predictionDate = Carbon::parse($prediction->prediction_date);
        $endDate = Carbon::parse($prediction->end_date);
        $tDays = $predictionDate->diffInDays($endDate);

        // Get volatility for the stock (default to 1.0 if not available)
        $volatility = $this->getStockVolatility($prediction->stock) ?? 1.0;

        // Get benchmark performance (default to 0 if not available) - TODO: fetch real benchmark
        $benchmarkPerformance = $prediction->benchmark_performance ?? 0.0;

        // Get thesis rating (default to 3 if not available)
        $thesisRating = $prediction->thesis_rating ?? 3;

        // Get user's prediction count for dynamic learning rate
        $predictionCount = Prediction::where('user_id', $user->id)
            ->whereNotNull('accuracy')
            ->count();

        // Guard against invalid inputs
        if ($pActual <= 0 || $pPred <= 0 || $pInitial <= 0) {
            throw new \Exception("Prices must be > 0");
        }

        // --- PART 1: Calculate the score for this single prediction ---

        // 1a. Calculate Base Penalty
        $logError = abs(log($pPred / $pActual));
        $multiplier = ($pPred <= $pActual)
            ? self::UNDER_PREDICTION_MULTIPLIER
            : 1.0;
        $asymmetricError = $logError * $multiplier;

        // 1b. Calculate Penalty (P)
        $scaledError = sqrt($asymmetricError);
        $clippedError = min($scaledError, self::MAX_PENALTY_CLIP);
        $penalty = self::PENALTY_SCALING_FACTOR * $clippedError;

        // 1c. Calculate Bonuses (if direction is correct)
        $predDirection = $pPred - $pInitial;
        $actualDirection = $pActual - $pInitial;

        $directionalBonus = 0.0;
        $timeAccuracyBonus = 0.0;

        if (($predDirection * $actualDirection) > 0) { // Correct direction
            $directionalBonus = self::DIRECTIONAL_BONUS;
            $actualMagnitude = abs(($pActual - $pInitial) / $pInitial);
            $magnitudeBonus = self::MAGNITUDE_BONUS_SCALING * $actualMagnitude;
            $directionalBonus += $magnitudeBonus;

            $accuracyFactor = max(0.0, 1.0 - (sqrt($logError) / self::ACCURACY_THRESHOLD));
            $effectiveT = min($tDays, self::MAX_TIME_DAYS);
            $timeAccuracyBonus = self::TIME_BONUS_SCALING_FACTOR * sqrt($effectiveT) * $accuracyFactor;
        }

        // 1d. Calculate Volatility Multiplier (V2 Anti-Gaming)
        $volatilityMultiplier = self::VOLATILITY_MULTIPLIER_BASE + $volatility;

        // 1e. Calculate Base Prediction Score
        $predictionScore = self::BASE_VALUE - $penalty + $directionalBonus + $timeAccuracyBonus;
        $pointChange = ($predictionScore - self::BASE_VALUE) * $volatilityMultiplier;

        // --- PART 2: Apply V3 Multipliers ---

        // 2a. Alpha Score
        $stockPerformance = ($pActual - $pInitial) / $pInitial;
        $alpha = $stockPerformance - $benchmarkPerformance;
        $alphaMultiplier = 1.0 + ($alpha * self::ALPHA_SENSITIVITY);

        // 2b. Thesis Multiplier
        $thesisMultiplier = self::THESIS_MULTIPLIER_MAP[$thesisRating] ?? 1.0;

        // 2c. Final Point Change with V3 multipliers
        $finalPointChange = $pointChange * $alphaMultiplier * $thesisMultiplier;

        // --- PART 3: Update the user's overall score ---

        // 3a. Dynamic Learning Rate (V3 Feature)
        $learningRate = $this->getDynamicLearningRate($predictionCount);

        // 3b. Calculate Bell Curve Damping Factor
        $distFromMean = abs($currentScore - self::TARGET_MEAN_SCORE);
        $maxDist = self::MAX_SCORE - self::TARGET_MEAN_SCORE;
        $dampingFactor = 1 - pow($distFromMean / $maxDist, 2);
        $dampingFactor = max(0, $dampingFactor);

        // 3c. Calculate Final Score Update and apply it
        $adjustedChange = $finalPointChange * $dampingFactor * $learningRate;
        $newScore = $currentScore + $adjustedChange;

        // Clamp the score to be within the min/max bounds
        $newScore = max(self::MIN_SCORE, min(self::MAX_SCORE, $newScore));

        // Convert prediction score to 0-100 percentage for display
        $accuracy = max(0, min(100, $predictionScore));

        return [
            'accuracy' => $accuracy,
            'new_user_score' => $newScore,
            'prediction_score' => $predictionScore,
            'point_change' => $finalPointChange,
            'penalty' => $penalty,
            'bonuses' => $directionalBonus + $timeAccuracyBonus,
            'volatility' => $volatility,
            'alpha_multiplier' => $alphaMultiplier,
            'thesis_multiplier' => $thesisMultiplier,
            'learning_rate' => $learningRate,
        ];
    }

    /**
     * Get stock price at a specific date
     *
     * @param string $symbol
     * @param string $date
     * @return float|null
     */
    protected function getStockPriceAtDate($symbol, $date)
    {
        $date = Carbon::parse($date)->format('Y-m-d');

        // Try to find exact date first
        $stockPrice = StockPrice::whereHas('stock', function ($query) use ($symbol) {
            $query->where('symbol', $symbol);
        })
        ->where('price_date', $date)
        ->first();

        if ($stockPrice) {
            return (float) $stockPrice->close_price;
        }

        // If exact date not found, get closest previous date (within 7 days)
        $stockPrice = StockPrice::whereHas('stock', function ($query) use ($symbol) {
            $query->where('symbol', $symbol);
        })
        ->where('price_date', '<=', $date)
        ->where('price_date', '>=', Carbon::parse($date)->subDays(7)->format('Y-m-d'))
        ->orderBy('price_date', 'desc')
        ->first();

        return $stockPrice ? (float) $stockPrice->close_price : null;
    }

    /**
     * Get stock volatility based on sector or historical data
     *
     * @param Stock $stock
     * @return float
     */
    protected function getStockVolatility($stock)
    {
        // Default volatility multipliers by sector
        $sectorVolatility = [
            'Technology' => 1.5,
            'Healthcare' => 1.3,
            'Finance' => 1.2,
            'Energy' => 1.4,
            'Utilities' => 0.8,
            'Consumer' => 1.0,
            'Industrial' => 1.1,
        ];

        // Return sector-based volatility or default to 1.0
        return $sectorVolatility[$stock->sector] ?? 1.0;
    }

    /**
     * Calculate dynamic learning rate based on user's prediction count
     * V3 Feature: New users get higher learning rate, veterans get lower
     *
     * @param int $predictionCount
     * @return float
     */
    protected function getDynamicLearningRate($predictionCount)
    {
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
     * Send email notification to user
     *
     * @param Prediction $prediction
     * @param float $accuracy
     * @param float $scoreChange
     * @return void
     */
    protected function sendEmailNotification($prediction, $accuracy, $scoreChange)
    {
        try {
            $user = $prediction->user;
            $stock = $prediction->stock;

            if (!$user || !$user->email || !$stock) {
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
                new PredictionEvaluated($predictionData, $userData, $stockData, $accuracy, round($scoreChange))
            );

        } catch (\Exception $e) {
            Log::error("Error sending evaluation email: " . $e->getMessage());
        }
    }
}
