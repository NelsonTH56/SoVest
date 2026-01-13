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

    // --- PART 1: SINGLE PREDICTION PARAMETERS ---

    // Base score for any single prediction
    const C = 50.0;

    // Penalty scaling factor
    const A = 250.0;

    // Time bonus scaling factor
    const B = 5.0;

    // Accuracy threshold for time bonus (lower is stricter)
    const E = 0.6;

    // Cap on the scaled error to prevent extreme penalties
    const P_MAX = 3.0;

    // Cap on the number of days for the time bonus
    const T_MAX = 365.0;

    // Multiplier for under-predicting (makes penalty harsher)
    const UNDER_PREDICTION_PENALTY_MULTIPLIER = 1.2;
    const OVER_PREDICTION_PENALTY_MULTIPLIER = 1.0;

    // Flat bonus for getting the direction right (base for magnitude scaling)
    const DIRECTIONAL_BONUS_BASE = 8.0;

    // --- PART 2: OVERALL SCORE "CREDIT SCORE" PARAMETERS ---

    // The "brake pedal" on score changes (0.20 = 20% of the change is applied)
    const LEARNING_RATE = 0.20;

    // The mean score the "bell curve" damper pulls towards
    const TARGET_MEAN_SCORE = 500.0;

    // Min/Max possible scores for any user
    const MIN_SCORE = 0.0;
    const MAX_SCORE = 1000.0;

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
     * Evaluate a prediction using the V2 anti-gaming algorithm
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

        // Guard against invalid inputs
        if ($pActual <= 0 || $pPred <= 0 || $pInitial <= 0) {
            throw new \Exception("Prices must be > 0");
        }

        // --- PART 1: Calculate the score for this single prediction ---

        // 1a. Calculate Asymmetric Error (x)
        $logError = abs(log($pPred / $pActual));
        $multiplier = ($pPred <= $pActual)
            ? self::UNDER_PREDICTION_PENALTY_MULTIPLIER
            : self::OVER_PREDICTION_PENALTY_MULTIPLIER;
        $x = $logError * $multiplier;

        // 1b. Calculate Penalty (P)
        $penalty = self::A * min(sqrt($x), self::P_MAX);

        // 1c. Calculate Bonuses (B)
        $predictedDirection = $pPred - $pInitial;
        $actualDirection = $pActual - $pInitial;
        $directionCorrect = ($predictedDirection * $actualDirection) >= 0;

        $timeBonus = 0.0;
        $directionalBonus = 0.0;

        if ($directionCorrect) {
            // Magnitude-Adjusted Bonus
            $magnitude = abs(($pActual - $pInitial) / $pInitial);
            $directionalBonus = self::DIRECTIONAL_BONUS_BASE * (1 + $magnitude * 10);

            // Time/Accuracy Bonus
            $accuracyFactor = max(0.0, 1.0 - (sqrt(abs(log($pPred / $pActual))) / self::E));
            $tEff = min($tDays, self::T_MAX);
            $timeBonus = self::B * sqrt($tEff) * $accuracyFactor;
        }

        $bonuses = $directionalBonus + $timeBonus;

        // 1d. Calculate the Final Grade (S_prediction) with Volatility Scaling
        $rawScoreChange = $bonuses - $penalty;
        $scaledScoreChange = $rawScoreChange * $volatility;
        $sPrediction = self::C + $scaledScoreChange;

        // --- PART 2: Update the user's overall score ---

        // 2a. Calculate Point Change (delta_p)
        $pointChange = $sPrediction - self::C;

        // 2b. Calculate Bell Curve Damping Factor (F_damp)
        $distFromMean = abs($currentScore - self::TARGET_MEAN_SCORE);
        $maxDist = self::MAX_SCORE - self::TARGET_MEAN_SCORE;
        $dampingFactor = 1 - pow($distFromMean / $maxDist, 2);
        $dampingFactor = max(0, $dampingFactor);

        // 2c/2d. Calculate Final Score Update and apply it
        $finalUpdate = $pointChange * $dampingFactor * self::LEARNING_RATE;
        $newScore = $currentScore + $finalUpdate;

        // Clamp the score to be within the min/max bounds
        $newScore = max(self::MIN_SCORE, min(self::MAX_SCORE, $newScore));

        // Convert prediction score to 0-100 percentage for display
        $accuracy = max(0, min(100, $sPrediction));

        return [
            'accuracy' => $accuracy,
            'new_user_score' => $newScore,
            'prediction_score' => $sPrediction,
            'point_change' => $pointChange,
            'penalty' => $penalty,
            'bonuses' => $bonuses,
            'volatility' => $volatility,
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
