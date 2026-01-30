<?php
/**
 * SoVest Prediction Scoring Service
 * 
 * This service evaluates stock predictions against actual performance
 * and calculates user reputation scores based on prediction accuracy.
 * It supports both dependency injection and singleton pattern for backward compatibility.
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
    
   
    private $stockService;
    
     public function __construct(StockDataService $stockService) {
        // Initialize stock data service with dependency injection or fallback to singleton
        $this->stockService = $stockService;
    }
    
     public function evaluateActivePredictions() {
        $results = [
            'total' => 0,
            'evaluated' => 0,
            'errors' => 0
        ];
        
        try {
            // Get all active predictions that have passed their end date
            // Using < instead of <= means end_date = Jan 16 stays active until Jan 17 00:00:00
            $predictions = Prediction::where('is_active', 1)
                ->where('end_date', '<', Carbon::today())
                ->whereNull('accuracy')
                ->with(['stock']) // Eager load the stock relationship
                ->get();
            
            $results['total'] = count($predictions);
            
            // Process each prediction
            foreach ($predictions as $prediction) {
                try {
                    $this->evaluatePrediction([
                        'prediction_id' => $prediction->prediction_id,
                        'user_id' => $prediction->user_id,
                        'symbol' => $prediction->stock->symbol,
                        'prediction_type' => $prediction->prediction_type,
                        'target_price' => $prediction->target_price,
                        'prediction_date' => $prediction->prediction_date,
                        'end_date' => $prediction->end_date
                    ]);
                    $results['evaluated']++;
                } catch (\Exception $e) {
                    $results['errors']++;
                    // Log the error
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
     * Evaluate a single prediction
     * 
     * @param array $prediction Prediction data
     * @return bool Success status
     */
    public function evaluatePrediction($prediction) {
        $predictionId = $prediction['prediction_id'];
        $userId = $prediction['user_id'];
        $symbol = $prediction['symbol'];
        $predictionType = $prediction['prediction_type'];
        $targetPrice = $prediction['target_price'];
        $startDate = $prediction['prediction_date'];
        $endDate = $prediction['end_date'];
        
        try {
            // Get stock price at prediction time and at end date
            $startPrice = $this->getStockPriceAtDate($symbol, $startDate);
            $endPrice = $this->getStockPriceAtDate($symbol, $endDate);
            
            if (!$startPrice || !$endPrice) {
                throw new \Exception("Unable to retrieve stock prices for $symbol");
            }
            
            // Calculate price movement
            $priceChange = $endPrice - $startPrice;
            $percentChange = ($priceChange / $startPrice) * 100;
            
            // Determine if prediction was correct
            $predictionCorrect = false;
            
            if ($predictionType == 'Bullish' && $priceChange > 0) {
                $predictionCorrect = true;
            } else if ($predictionType == 'Bearish' && $priceChange < 0) {
                $predictionCorrect = true;
            }
            
            // Calculate accuracy score (0-100)
            $accuracy = $this->calculateAccuracyScore($predictionCorrect, $percentChange);
            
            // Update prediction with accuracy
            $predictionModel = Prediction::find($predictionId);
            $predictionModel->accuracy = $accuracy;
            $predictionModel->is_active = 0;
            $predictionModel->save();

            // Calculate reputation change before updating
            $reputationChange = $this->calculateReputationPoints($accuracy);

            // Update user reputation score
            $this->updateUserReputation($userId, $accuracy);

            // Send email notification to user
            $this->sendEvaluationEmail($predictionModel, $accuracy, $reputationChange);

            return true;
        } catch (\Exception $e) {
            error_log("Error evaluating prediction: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Calculate accuracy score for a prediction
     * 
     * @param bool $predictionCorrect Whether prediction direction was correct
     * @param float $percentChange Percent price change
     * @return float Accuracy score (0-100)
     */
    private function calculateAccuracyScore($predictionCorrect, $percentChange) {
        // Base score
        $baseScore = $predictionCorrect ? 75 : 25;
        
        // Adjust score based on magnitude of price change
        $absChange = abs($percentChange);
        $magnitudeBonus = 0;
        
        // More significant price movements deserve higher scores
        if ($absChange >= 10) {
            $magnitudeBonus = 25; // Very significant movement
        } else if ($absChange >= 5) {
            $magnitudeBonus = 15; // Significant movement
        } else if ($absChange >= 2) {
            $magnitudeBonus = 10; // Moderate movement
        } else {
            $magnitudeBonus = 5;  // Small movement
        }
        
        // If prediction was wrong, magnitude bonus is negative
        if (!$predictionCorrect) {
            $magnitudeBonus = -$magnitudeBonus;
        }
        
        // Calculate final score
        $score = $baseScore + $magnitudeBonus;
        
        // Ensure score is between 0 and 100
        return max(0, min(100, $score));
    }
    
    /**
     * Update user reputation score
     * 
     * @param int $userId User ID
     * @param float $accuracy Accuracy of prediction
     * @return bool Success status
     */
    public function updateUserReputation($userId, $accuracy) {
        try {
            // Calculate reputation points based on accuracy
            $reputationChange = $this->calculateReputationPoints($accuracy);

            // Update user reputation score using Eloquent
            $user = User::find($userId);
            $user->reputation_score = $user->reputation_score + $reputationChange;
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
     * Calculate reputation points based on prediction accuracy
     * 
     * @param float $accuracy Accuracy score (0-100)
     * @return int Reputation points
     */
    private function calculateReputationPoints($accuracy) {
        // Score tiers
        if ($accuracy >= 90) {
            return 10;  // Exceptional prediction
        } else if ($accuracy >= 70) {
            return 5;   // Very good prediction
        } else if ($accuracy >= 50) {
            return 2;   // Good prediction
        } else if ($accuracy >= 30) {
            return 0;   // Poor prediction
        } else {
            return -2;  // Very poor prediction
        }
    }

    /**
     * Send email notification to user about prediction evaluation
     *
     * @param Prediction $prediction The evaluated prediction
     * @param float $accuracy The accuracy score
     * @param int $reputationChange The reputation points gained/lost
     * @return void
     */
    private function sendEvaluationEmail($prediction, $accuracy, $reputationChange) {
        try {
            // Load related data
            $user = User::find($prediction->user_id);
            $stock = Stock::find($prediction->stock_id);

            if (!$user || !$user->email || !$stock) {
                error_log("Cannot send email: Missing user or stock data for prediction {$prediction->prediction_id}");
                return;
            }

            // Prepare data for email
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

            // Send email (queued for better performance)
            Mail::to($user->email)->queue(
                new PredictionEvaluated($predictionData, $userData, $stockData, $accuracy, $reputationChange)
            );

        } catch (\Exception $e) {
            // Log error but don't fail the evaluation process
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
            // Format date
            $date = date('Y-m-d', strtotime($date));

            // Find the stock by symbol
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

            // Fetch and store the latest price from the API
            $fetchResult = $this->stockService->fetchAndStoreStockData($symbol);

            if ($fetchResult) {
                error_log("Successfully fetched and stored price for $symbol from API");

                // Get the newly stored price
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
            // Get users with prediction counts and avg accuracy in a single query
            $users = User::select([
                'id',
                'first_name',
                'last_name',
                'email',
                'reputation_score'
            ])
            ->withCount('predictions')
            ->withAvg(['predictions as avg_accuracy' => function ($query) {
                $query->whereNotNull('accuracy');
            }], 'accuracy')
            ->orderBy('reputation_score', 'desc')
            ->limit($limit)
            ->get();

            // Map to array format
            $result = $users->map(function($user) {
                return [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'reputation_score' => $user->reputation_score,
                    'predictions_count' => $user->predictions_count ?? 0,
                    'avg_accuracy' => $user->avg_accuracy ?? 0,
                ];
            })->toArray();

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
            'reputation' => 0
        ];

        try {
            // Using Eloquent for aggregations
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

            // Finalized predictions = those with accuracy scores (evaluated)
            $finalized = Prediction::where('user_id', $userId)
                ->whereNotNull('accuracy')
                ->count();

            $stats['total'] = $total;
            $stats['total_predictions'] = $finalized; // Only finalized predictions for display
            $stats['pending'] = $pending;
            $stats['accurate'] = $accurate;
            $stats['inaccurate'] = $inaccurate;
            $stats['avg_accuracy'] = $avgAccuracy ? round((float)$avgAccuracy, 1) : 0;
            
            // Get user reputation
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