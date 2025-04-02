<?php
/**
 * SoVest Prediction Scoring Service
 * 
 * This service evaluates stock predictions against actual performance
 * and calculates user reputation scores based on prediction accuracy.
 */

// Include the StockDataService for accessing price data
require_once __DIR__ . '/StockDataService.php';
require_once __DIR__ . '/../bootstrap/database.php';

use Database\Models\User;
use Database\Models\Prediction;
use Database\Models\Stock;
use Database\Models\StockPrice;
use Illuminate\Database\Capsule\Manager as DB;

class PredictionScoringService {
    private $stockService;
    
    /**
     * Constructor - initializes stock service
     */
    public function __construct() {
        // Initialize stock data service
        $this->stockService = new StockDataService();
    }
    
    /**
     * Evaluate all active predictions that have reached their end date
     * 
     * @return array Results of evaluations
     */
    public function evaluateActivePredictions() {
        $results = [
            'total' => 0,
            'evaluated' => 0,
            'errors' => 0
        ];
        
        try {
            // Get all active predictions that have reached their end date
            $predictions = Prediction::where('is_active', 1)
                ->where('end_date', '<=', DB::raw('NOW()'))
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
            
            // Update user reputation score
            $this->updateUserReputation($userId, $accuracy);
            
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
                return null;
            }
            
            // Get the price record closest to the date
            $price = StockPrice::where('stock_id', $stock->stock_id)
                ->where('price_date', '<=', $date)
                ->orderBy('price_date', 'desc')
                ->first();
            
            if ($price) {
                return (float)$price->close_price;
            }
            
            // If no historical price found, try getting latest price
            return $this->stockService->getLatestPrice($symbol);
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
            // Using query builder for more complex query
            $users = DB::table('users')
                ->select([
                    'users.id',
                    'users.first_name',
                    'users.last_name',
                    'users.email',
                    'users.reputation_score',
                    DB::raw('(SELECT COUNT(*) FROM predictions WHERE user_id = users.id) as prediction_count'),
                    DB::raw('(SELECT AVG(accuracy) FROM predictions WHERE user_id = users.id AND accuracy IS NOT NULL) as avg_accuracy')
                ])
                ->orderBy('reputation_score', 'desc')
                ->limit($limit)
                ->get()
                ->toArray();
            
            return $users;
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
            'accurate' => 0,
            'inaccurate' => 0,
            'pending' => 0,
            'avg_accuracy' => 0,
            'reputation' => 0
        ];
        
        try {
            // Using query builder for complex aggregations
            $predictionStats = DB::table('predictions')
                ->select([
                    DB::raw('COUNT(*) as total'),
                    DB::raw('SUM(CASE WHEN accuracy IS NULL THEN 1 ELSE 0 END) as pending'),
                    DB::raw('SUM(CASE WHEN accuracy >= 50 THEN 1 ELSE 0 END) as accurate'),
                    DB::raw('SUM(CASE WHEN accuracy < 50 AND accuracy IS NOT NULL THEN 1 ELSE 0 END) as inaccurate'),
                    DB::raw('AVG(CASE WHEN accuracy IS NOT NULL THEN accuracy ELSE NULL END) as avg_accuracy')
                ])
                ->where('user_id', $userId)
                ->first();
            
            if ($predictionStats) {
                $stats['total'] = (int)$predictionStats->total;
                $stats['pending'] = (int)$predictionStats->pending;
                $stats['accurate'] = (int)$predictionStats->accurate;
                $stats['inaccurate'] = (int)$predictionStats->inaccurate;
                $stats['avg_accuracy'] = $predictionStats->avg_accuracy ? round((float)$predictionStats->avg_accuracy, 1) : 0;
            }
            
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
?>