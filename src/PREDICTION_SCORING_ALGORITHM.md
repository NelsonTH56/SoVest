# SoVest Prediction Scoring Algorithm V2

## Overview

The SoVest prediction scoring system uses an advanced anti-gaming algorithm that evaluates stock predictions and updates user reputation scores. The algorithm is designed to reward accurate predictions, penalize inaccurate ones, and prevent gaming strategies like "grinding" with low-risk predictions.

---

## Architecture

### Job-Based Processing

**File:** [app/Jobs/EvaluatePredictionJob.php](app/Jobs/EvaluatePredictionJob.php)

Predictions are evaluated asynchronously using Laravel's queue system:

```php
// Dispatch a single prediction for evaluation
EvaluatePredictionJob::dispatch($predictionId);
```

**Benefits:**
- Non-blocking processing
- Automatic retries on failure
- Better performance for multiple predictions
- Logging and error tracking

### Automated Scheduling

**File:** [app/Console/Kernel.php](app/Console/Kernel.php)

```php
// Runs hourly to evaluate all expired predictions
$schedule->command('predictions:evaluate')
         ->hourly()
         ->withoutOverlapping()
         ->runInBackground();
```

**Manual Execution:**
```bash
# Evaluate up to 50 expired predictions
php artisan predictions:evaluate

# Evaluate specific number
php artisan predictions:evaluate --limit=100

# Force re-evaluation of already processed predictions
php artisan predictions:evaluate --force

# Process the queue
php artisan queue:work
```

---

## Algorithm Components

### Part 1: Single Prediction Scoring

#### Constants

```php
const C = 50.0;              // Base score for any prediction
const A = 250.0;             // Penalty scaling factor
const B = 5.0;               // Time bonus scaling factor
const E = 0.6;               // Accuracy threshold for time bonus
const P_MAX = 3.0;           // Cap on scaled error
const T_MAX = 365.0;         // Max days for time bonus
const UNDER_PREDICTION_PENALTY_MULTIPLIER = 1.2;  // Harsher penalty for under-predicting
const OVER_PREDICTION_PENALTY_MULTIPLIER = 1.0;   // Standard penalty for over-predicting
const DIRECTIONAL_BONUS_BASE = 8.0;               // Base bonus for correct direction
```

#### Step 1: Calculate Asymmetric Error (x)

```php
$logError = abs(log($pPred / $pActual));
$multiplier = ($pPred <= $pActual)
    ? UNDER_PREDICTION_PENALTY_MULTIPLIER    // 1.2 if under-predicted
    : OVER_PREDICTION_PENALTY_MULTIPLIER;     // 1.0 if over-predicted
$x = $logError * $multiplier;
```

**Purpose:** Penalizes under-predictions more heavily (missing gains) than over-predictions.

#### Step 2: Calculate Penalty (P)

```php
$penalty = A * min(sqrt($x), P_MAX);
```

**Purpose:**
- Square root provides diminishing returns for larger errors
- Capped at P_MAX to prevent extreme penalties
- Scaled by factor A (250.0)

#### Step 3: Calculate Bonuses (B)

**Directional Bonus (Magnitude-Adjusted):**
```php
if ($directionCorrect) {
    $magnitude = abs(($pActual - $pInitial) / $pInitial);
    $directionalBonus = DIRECTIONAL_BONUS_BASE * (1 + $magnitude * 10);
}
```

**Purpose:** Rewards correct predictions more for larger price movements. A 50% move is worth more than a 1% move.

**Time/Accuracy Bonus:**
```php
if ($directionCorrect) {
    $accuracyFactor = max(0.0, 1.0 - (sqrt(abs(log($pPred / $pActual))) / E));
    $tEff = min($tDays, T_MAX);
    $timeBonus = B * sqrt($tEff) * $accuracyFactor;
}
```

**Purpose:**
- Rewards predictions made far in advance (but capped at 365 days)
- Scaled by how accurate the prediction was
- Square root prevents extreme bonuses for very long predictions

#### Step 4: Volatility Scaling

```php
$rawScoreChange = $bonuses - $penalty;
$scaledScoreChange = $rawScoreChange * $volatility;
$sPrediction = C + $scaledScoreChange;
```

**Volatility by Sector:**
- Technology: 1.5x (highly volatile)
- Healthcare: 1.3x
- Energy: 1.4x
- Finance: 1.2x
- Industrial: 1.1x
- Consumer: 1.0x (baseline)
- Utilities: 0.8x (stable)

**Purpose:**
- Rewards riskier predictions more
- Stable utility stocks earn less than volatile tech stocks
- Prevents gaming with only safe predictions

---

### Part 2: User Score Update

#### Constants

```php
const LEARNING_RATE = 0.20;            // 20% of change is applied
const TARGET_MEAN_SCORE = 500.0;       // Center of bell curve
const MIN_SCORE = 0.0;                 // Minimum user score
const MAX_SCORE = 1000.0;              // Maximum user score
```

#### Step 1: Calculate Point Change

```php
$pointChange = $sPrediction - C;
```

This is the raw points earned from the single prediction.

#### Step 2: Bell Curve Damping

```php
$distFromMean = abs($currentScore - TARGET_MEAN_SCORE);
$maxDist = MAX_SCORE - TARGET_MEAN_SCORE;  // 500
$dampingFactor = 1 - pow($distFromMean / $maxDist, 2);
$dampingFactor = max(0, $dampingFactor);
```

**Purpose:**
- Users near 500 score change normally (damping ≈ 1.0)
- Users at extremes (near 0 or 1000) change more slowly
- Creates natural regression toward the mean
- Prevents runaway scores

**Example Damping:**
- At score 500: damping = 1.0 (full change)
- At score 700: damping = 0.84 (84% of change)
- At score 900: damping = 0.36 (36% of change)
- At score 1000: damping = 0.0 (no change)

#### Step 3: Apply Learning Rate

```php
$finalUpdate = $pointChange * $dampingFactor * LEARNING_RATE;
$newScore = $currentScore + $finalUpdate;
$newScore = max(MIN_SCORE, min(MAX_SCORE, $newScore));
```

**Purpose:**
- Learning rate (20%) smooths out score changes
- Prevents wild swings from single predictions
- Requires consistent performance for score changes
- Clamped to 0-1000 range

---

## Anti-Gaming Features

### 1. Magnitude-Adjusted Directional Bonus

**Problem:** Users could make safe predictions on tiny movements.

**Solution:**
```php
$directionalBonus = DIRECTIONAL_BONUS_BASE * (1 + $magnitude * 10);
```

Predicting a 50% increase earns much more than predicting a 0.1% increase.

### 2. Asymmetric Penalty

**Problem:** Users could avoid big moves and predict status quo.

**Solution:**
```php
$multiplier = ($pPred <= $pActual) ? 1.2 : 1.0;
```

Under-predicting (missing upside) is penalized 20% more than over-predicting.

### 3. Volatility Multiplier

**Problem:** Users could only pick stable stocks.

**Solution:**
```php
$scaledScoreChange = $rawScoreChange * $volatility;
```

Tech stocks (1.5x) earn more than utilities (0.8x).

### 4. Bell Curve Damping

**Problem:** Users at high scores could maintain position with mediocre predictions.

**Solution:** The damping factor makes it harder to stay at extremes. High scorers must maintain exceptional accuracy.

### 5. Learning Rate

**Problem:** Single lucky prediction shouldn't skyrocket scores.

**Solution:** Only 20% of each prediction's impact is applied, requiring consistency.

---

## Example Scenarios

### Scenario 1: Pro Investor - Accurate Tech Prediction

```
Current Score: 500
Prediction: $150 → Actual: $155 (Initial: $100)
Days in advance: 90
Volatility: 1.5 (Technology)
```

**Calculation:**
- Direction: ✅ Correct (Bullish, price went up)
- Magnitude: 55% increase
- Directional Bonus: 8.0 * (1 + 0.55 * 10) = 52.0
- Time Bonus: ~5.0
- Penalty: ~5.0 (small error)
- Raw Score: 50 + (57 - 5) = 102
- With Volatility: 102 * 1.5 = 153
- Point Change: 103
- Damping (at 500): 1.0
- Final Update: 103 * 1.0 * 0.20 = 20.6
- **New Score: 520.6**

### Scenario 2: Gambler - Wild Guess on Tech

```
Current Score: 500
Prediction: $250 → Actual: $155 (Initial: $100)
Days in advance: 90
Volatility: 1.5 (Technology)
```

**Calculation:**
- Direction: ✅ Correct (Bullish, price went up)
- Magnitude: 55% increase
- Error: Large (predicted 150% vs actual 55%)
- Directional Bonus: 52.0
- Time Bonus: ~1.0 (reduced by poor accuracy)
- Penalty: ~100.0 (large error)
- Raw Score: 50 + (53 - 100) = 3
- With Volatility: 3 * 1.5 = 4.5
- Point Change: -45.5
- Damping: 1.0
- Final Update: -45.5 * 1.0 * 0.20 = -9.1
- **New Score: 490.9**

### Scenario 3: Grinder - Tiny Safe Prediction

```
Current Score: 500
Prediction: $100.1 → Actual: $100.2 (Initial: $100.0)
Days in advance: 1
Volatility: 0.8 (Utilities)
```

**Calculation:**
- Direction: ✅ Correct
- Magnitude: 0.2% increase (tiny)
- Directional Bonus: 8.0 * (1 + 0.002 * 10) = 8.16
- Time Bonus: ~0.5 (only 1 day)
- Penalty: ~1.0
- Raw Score: 50 + (8.66 - 1) = 57.66
- With Volatility: 57.66 * 0.8 = 46.13
- Point Change: -3.87
- Damping: 1.0
- Final Update: -3.87 * 1.0 * 0.20 = -0.77
- **New Score: 499.23** (Lost points despite being correct!)

---

## Database Schema

### Users Table

```php
$table->decimal('reputation_score', 10, 2)->default(500.00);
```

**Default:** 500 (middle of 0-1000 range)

**Range:** 0.00 to 1000.00

### Predictions Table

```php
$table->decimal('accuracy', 5, 2)->nullable();  // 0-100 percentage
$table->boolean('is_active')->default(1);       // Becomes 0 when evaluated
```

---

## Running the System

### Setup

1. **Run migrations:**
```bash
php artisan migrate
```

2. **Start queue worker:**
```bash
php artisan queue:work
```

3. **Set up cron job:**
```bash
* * * * * cd /path/to/sovest && php artisan schedule:run >> /dev/null 2>&1
```

### Manual Commands

```bash
# Evaluate expired predictions (max 50)
php artisan predictions:evaluate

# Evaluate more predictions
php artisan predictions:evaluate --limit=100

# Force re-evaluation
php artisan predictions:evaluate --force

# Check scheduled tasks
php artisan schedule:list

# Test queue
php artisan queue:work --once
```

### Monitoring

```bash
# Check queue status
php artisan queue:monitor

# View failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Check logs
tail -f storage/logs/laravel.log
```

---

## Testing

### Unit Tests

Test the scoring algorithm:

```bash
php artisan test --filter=PredictionScoringTest
```

### Manual Testing

```php
use App\Jobs\EvaluatePredictionJob;

// Dispatch a specific prediction
EvaluatePredictionJob::dispatch(123);

// Process immediately (sync)
EvaluatePredictionJob::dispatchSync(123);
```

---

## Performance Considerations

### Optimization

1. **Batch Processing:** The command processes up to 50 predictions at once by default
2. **Queue Workers:** Use multiple workers for parallel processing
3. **Cache Clearing:** Leaderboard cache is cleared after score updates
4. **Database Indexing:** Ensure indexes on `end_date` and `is_active` columns

### Scaling

For high-volume production:

```bash
# Use supervisor to manage multiple workers
php artisan queue:work --queue=high,default --tries=3 --timeout=90

# Use Redis for better queue performance
QUEUE_CONNECTION=redis
```

---

## Configuration

### Environment Variables

```env
# Queue Configuration
QUEUE_CONNECTION=database  # or 'redis' for production

# Mail Configuration (for notifications)
MAIL_MAILER=smtp
MAIL_FROM_ADDRESS=noreply@sovest.com
```

### Adjusting Algorithm Parameters

Edit [app/Jobs/EvaluatePredictionJob.php](app/Jobs/EvaluatePredictionJob.php):

```php
// Make scoring more forgiving
const LEARNING_RATE = 0.30;  // Was 0.20

// Adjust volatility multipliers
protected function getStockVolatility($stock) {
    return [
        'Technology' => 2.0,  // Was 1.5 (more reward for risky predictions)
        'Utilities' => 1.0,   // Was 0.8 (equal to baseline)
    ][$stock->sector] ?? 1.0;
}
```

---

## Troubleshooting

### Common Issues

**1. Predictions not being evaluated**
- Check cron job is running: `php artisan schedule:list`
- Manually trigger: `php artisan predictions:evaluate`
- Check queue worker is running: `php artisan queue:work`

**2. Queue jobs failing**
- View failed jobs: `php artisan queue:failed`
- Check error logs: `storage/logs/laravel.log`
- Retry failed jobs: `php artisan queue:retry all`

**3. Missing stock prices**
- Verify StockPrice table has data
- Check Alpha Vantage API is working
- Manually fetch prices if needed

**4. Scores not updating**
- Check migration ran: `php artisan migrate:status`
- Verify users table has reputation_score column
- Check cache is being cleared

---

## Future Enhancements

### Potential Improvements

1. **Machine Learning Integration**
   - Train ML model on historical predictions
   - Adjust volatility factors based on actual stock behavior
   - Personalized difficulty adjustments

2. **Advanced Volatility Calculation**
   - Calculate real volatility from historical price data
   - Use VIX-style indicators
   - Sector-specific volatility indices

3. **Prediction Confidence Levels**
   - Allow users to specify confidence (low/medium/high)
   - Adjust scoring based on stated confidence
   - Penalize overconfidence

4. **Streak Bonuses**
   - Reward consecutive accurate predictions
   - Combo multipliers for winning streaks
   - Seasonal performance tracking

5. **Peer Comparison**
   - Score relative to other users' predictions on same stock
   - Consensus vs contrarian bonuses
   - Community accuracy benchmarks

---

## References

- Original Python Algorithm: Converted from V2 anti-gaming implementation
- Laravel Queue Documentation: https://laravel.com/docs/queues
- Laravel Task Scheduling: https://laravel.com/docs/scheduling
- Mathematical Reference: Logarithmic error calculation for asymmetric penalties

---

**Last Updated:** December 24, 2025
**Algorithm Version:** 2.0
**Implementation:** Laravel 12 Job System
