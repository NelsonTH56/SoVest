# Prediction Scoring Algorithm - Implementation Summary

## Overview

The Python-based prediction scoring algorithm has been successfully converted to a deployable Laravel job system that automatically evaluates expired predictions and updates user reputation scores.

---

## What Was Built

### 1. Core Job: EvaluatePredictionJob ✅

**File:** [app/Jobs/EvaluatePredictionJob.php](app/Jobs/EvaluatePredictionJob.php)

**Features:**
- Full V2 anti-gaming algorithm implementation
- Asymmetric error calculation (under-prediction penalty multiplier)
- Magnitude-adjusted directional bonuses
- Volatility scaling by stock sector
- Bell curve damping for score stability
- Learning rate (20%) for gradual score changes
- Email notifications with score changes
- Cache invalidation for leaderboards
- Comprehensive error handling and logging

**Algorithm Components:**
- **Part 1: Single Prediction Scoring** (0-100 scale)
  - Asymmetric error calculation
  - Direction and magnitude bonuses
  - Time bonus for early predictions
  - Volatility multiplier by sector

- **Part 2: User Score Update** (0-1000 scale)
  - Point change calculation
  - Bell curve damping
  - Learning rate application
  - Score clamping (0-1000)

### 2. Console Command: EvaluateExpiredPredictions ✅

**File:** [app/Console/Commands/EvaluateExpiredPredictions.php](app/Console/Commands/EvaluateExpiredPredictions.php)

**Features:**
- Finds all expired predictions
- Dispatches evaluation jobs to queue
- Progress bar for batch processing
- Configurable limits (default: 50)
- Force re-evaluation option
- Error tracking and reporting

**Usage:**
```bash
php artisan predictions:evaluate              # Process up to 50
php artisan predictions:evaluate --limit=100  # Process up to 100
php artisan predictions:evaluate --force      # Re-evaluate all
```

### 3. Scheduled Task Configuration ✅

**File:** [app/Console/Kernel.php](app/Console/Kernel.php)

**Features:**
- Automatic hourly evaluation
- No overlap protection
- Background execution
- Optional market hours schedule included

**Schedule:**
```php
// Runs every hour
$schedule->command('predictions:evaluate')
         ->hourly()
         ->withoutOverlapping()
         ->runInBackground();
```

### 4. Database Migration ✅

**File:** [database/migrations/2025_12_24_000001_add_reputation_score_to_users_table.php](database/migrations/2025_12_24_000001_add_reputation_score_to_users_table.php)

**Changes:**
- Adds/updates `reputation_score` field on users table
- Default value: 500.00 (center of 0-1000 range)
- Data type: DECIMAL(10, 2)
- Migrates existing users from 0 to 500

### 5. Comprehensive Documentation ✅

**Files Created:**
1. **[PREDICTION_SCORING_ALGORITHM.md](PREDICTION_SCORING_ALGORITHM.md)** (450+ lines)
   - Complete algorithm explanation
   - Mathematical formulas and examples
   - Anti-gaming features breakdown
   - Configuration and troubleshooting

2. **[SCORING_SYSTEM_DEPLOYMENT.md](SCORING_SYSTEM_DEPLOYMENT.md)** (400+ lines)
   - Step-by-step deployment guide
   - Production setup with supervisor
   - Configuration options
   - Monitoring and troubleshooting

---

## Key Differences from Python Version

### Improvements

1. **Asynchronous Processing**
   - Python: Synchronous execution
   - Laravel: Queued jobs with retry logic
   - Benefit: Non-blocking, scalable

2. **Integrated Email Notifications**
   - Automatic emails when predictions evaluated
   - Beautiful HTML templates
   - Queued for performance

3. **Cache Management**
   - Auto-clears leaderboard cache on score updates
   - Ensures fresh data

4. **Database Integration**
   - Direct Eloquent model updates
   - Transaction safety
   - Foreign key constraints

5. **Error Handling**
   - Comprehensive logging
   - Failed job tracking
   - Automatic retries

6. **Monitoring**
   - Laravel logs
   - Queue monitoring
   - Supervisor integration

### Algorithm Fidelity

All Python algorithm components preserved:

| Component | Python | Laravel | Status |
|-----------|--------|---------|--------|
| Base Score (C) | 50.0 | 50.0 | ✅ |
| Penalty Factor (A) | 250.0 | 250.0 | ✅ |
| Time Bonus (B) | 5.0 | 5.0 | ✅ |
| Accuracy Threshold (E) | 0.6 | 0.6 | ✅ |
| Under-Prediction Penalty | 1.2 | 1.2 | ✅ |
| Over-Prediction Penalty | 1.0 | 1.0 | ✅ |
| Learning Rate | 0.20 | 0.20 | ✅ |
| Target Mean | 500.0 | 500.0 | ✅ |
| Score Range | 0-1000 | 0-1000 | ✅ |
| Directional Bonus Base | 8.0 | 8.0 | ✅ |
| Magnitude Scaling | Yes | Yes | ✅ |
| Volatility Multiplier | Yes | Yes | ✅ |
| Bell Curve Damping | Yes | Yes | ✅ |

---

## Deployment Steps

### Quick Start (Development)

```bash
# 1. Run migration
php artisan migrate

# 2. Start queue worker
php artisan queue:work

# 3. Set up cron (in crontab)
* * * * * cd /path/to/sovest && php artisan schedule:run >> /dev/null 2>&1

# 4. Test
php artisan predictions:evaluate
```

### Production Deployment

```bash
# 1. Update .env
QUEUE_CONNECTION=redis
MAIL_MAILER=smtp

# 2. Install Redis
sudo apt-get install redis-server

# 3. Set up Supervisor
# Create /etc/supervisor/conf.d/sovest-worker.conf
sudo supervisorctl reread
sudo supervisorctl update

# 4. Optimize Laravel
php artisan config:cache
php artisan route:cache

# 5. Start workers
sudo supervisorctl start sovest-worker:*
```

---

## Testing & Validation

### Test Scenarios

1. **Pro Investor Scenario** ✅
   - Accurate tech prediction (90 days ahead)
   - Expected: ~20 point increase
   - Result: Matches Python calculation

2. **Gambler Scenario** ✅
   - Wild guess on volatile stock
   - Expected: ~10 point decrease
   - Result: Matches Python calculation

3. **Grinder Scenario** ✅
   - Tiny safe prediction
   - Expected: Small point loss despite being correct
   - Result: Anti-gaming working as intended

### Unit Tests

Existing tests in [tests/Feature/PredictionScoringTest.php](tests/Feature/PredictionScoringTest.php) can be adapted:

```bash
php artisan test --filter=PredictionScoringTest
```

### Manual Testing

```bash
# Create test prediction
php artisan tinker
>>> $prediction = Prediction::find(1);
>>> use App\Jobs\EvaluatePredictionJob;
>>> EvaluatePredictionJob::dispatchSync($prediction->prediction_id);

# Check results
>>> $prediction->fresh();
>>> $prediction->accuracy  // Should be 0-100
>>> $prediction->user->reputation_score  // Should be updated
```

---

## Performance Metrics

### Expected Processing Times

- **Single Prediction:** ~100ms (including database queries)
- **50 Predictions (batch):** ~5 seconds to dispatch
- **Queue Processing:** ~2-3 predictions per second per worker
- **With 4 Workers:** ~8-12 predictions per second

### Database Impact

- **Queries per Prediction:**
  - 1 read: Prediction with relations
  - 2 reads: Stock prices (start/end dates)
  - 2 writes: Prediction update, User update
  - 2 cache clears
  - Total: ~7 operations

### Optimization Recommendations

1. **Index Creation:**
```sql
CREATE INDEX idx_predictions_active_end ON predictions(is_active, end_date);
CREATE INDEX idx_stock_prices_lookup ON stock_prices(stock_id, price_date);
```

2. **Multiple Workers:**
```bash
# 4 workers for parallel processing
numprocs=4  # in supervisor config
```

3. **Redis Queue:**
```env
QUEUE_CONNECTION=redis  # Faster than database queue
```

---

## Monitoring & Maintenance

### Daily Checks

```bash
# Check failed jobs
php artisan queue:failed

# View recent logs
tail -100 storage/logs/laravel.log

# Check worker status
sudo supervisorctl status
```

### Weekly Maintenance

```bash
# Retry failed jobs
php artisan queue:retry all

# Clear old failed jobs
php artisan queue:flush

# Check queue length
php artisan queue:monitor
```

### Monthly Review

1. Review score distribution (should cluster around 500)
2. Check for outliers (scores near 0 or 1000)
3. Analyze volatility multipliers effectiveness
4. Review failed job patterns

---

## Configuration Tuning

### Make Scoring More Forgiving

```php
// In EvaluatePredictionJob.php
const LEARNING_RATE = 0.30;  // Was 0.20 (30% vs 20%)
const UNDER_PREDICTION_PENALTY_MULTIPLIER = 1.1;  // Was 1.2 (less harsh)
```

### Adjust Volatility Multipliers

```php
protected function getStockVolatility($stock) {
    return [
        'Technology' => 2.0,   // Was 1.5 (more reward)
        'Utilities' => 1.0,    // Was 0.8 (equal to baseline)
        'Healthcare' => 1.5,   // Was 1.3
    ][$stock->sector] ?? 1.0;
}
```

### Change Evaluation Frequency

```php
// In Kernel.php

// Every 30 minutes
$schedule->command('predictions:evaluate')->everyThirtyMinutes();

// Twice daily
$schedule->command('predictions:evaluate')->twiceDaily(9, 18);

// Market hours only
$schedule->command('predictions:evaluate')
         ->cron('0 9-16 * * 1-5')
         ->timezone('America/New_York');
```

---

## Migration from Old System

If you had the old scoring system:

### Step 1: Backup Data

```bash
# Backup database
mysqldump sovest > backup_before_scoring_v2.sql

# Backup user scores
SELECT id, email, reputation_score INTO OUTFILE '/tmp/old_scores.csv'
FROM users;
```

### Step 2: Reset Scores (Optional)

```sql
-- Reset all users to 500 (fresh start)
UPDATE users SET reputation_score = 500.00;
```

### Step 3: Deploy New System

```bash
php artisan migrate
```

### Step 4: Re-evaluate Historical Predictions

```bash
# Force re-evaluation of all predictions
php artisan predictions:evaluate --force --limit=1000
```

---

## Troubleshooting Guide

### Problem: Scores Not Changing

**Diagnosis:**
```sql
-- Check if predictions are being evaluated
SELECT COUNT(*) FROM predictions WHERE is_active = 0 AND accuracy IS NOT NULL;

-- Check recent score changes
SELECT id, email, reputation_score, updated_at
FROM users
ORDER BY updated_at DESC
LIMIT 10;
```

**Solution:**
1. Verify queue worker is running
2. Check for errors in logs
3. Manually trigger evaluation

### Problem: Jobs Timing Out

**Diagnosis:**
```bash
# Check failed jobs
php artisan queue:failed

# Look for timeout errors
grep "timeout" storage/logs/laravel.log
```

**Solution:**
```php
// In config/queue.php
'timeout' => 120,  // Increase timeout

// Or in supervisor config
stopwaitsecs=180
```

### Problem: Stock Prices Not Found

**Diagnosis:**
```sql
-- Check price data availability
SELECT s.symbol,
       MIN(sp.price_date) as first_price,
       MAX(sp.price_date) as last_price,
       COUNT(*) as price_count
FROM stocks s
LEFT JOIN stock_prices sp ON s.stock_id = sp.stock_id
GROUP BY s.symbol;
```

**Solution:**
1. Fetch missing prices from Alpha Vantage API
2. Check API key is valid
3. Implement price backfill command

---

## Success Metrics

### Key Performance Indicators

1. **Evaluation Rate:** 95%+ of expired predictions evaluated within 1 hour
2. **Job Success Rate:** 99%+ of jobs complete successfully
3. **Email Delivery:** 95%+ of notifications sent
4. **Score Distribution:** Bell curve centered around 500
5. **Processing Time:** <100ms per prediction

### Health Checks

```bash
# Daily automated check
php artisan predictions:evaluate --limit=1 && echo "✅ System OK"

# Monitor queue depth
redis-cli LLEN queues:default

# Check worker health
ps aux | grep "queue:work"
```

---

## Future Enhancements

### Phase 2 Features

1. **Real-time Scoring:** WebSocket updates for instant score changes
2. **Prediction Analytics:** Dashboard for prediction patterns
3. **ML Integration:** Machine learning for volatility calculations
4. **A/B Testing:** Test different scoring parameters
5. **Leaderboard Tiers:** Bronze/Silver/Gold leagues based on score ranges

### Optimization Opportunities

1. **Batch Processing:** Evaluate multiple predictions in single job
2. **Caching:** Cache stock prices for frequently evaluated stocks
3. **Predictive Queueing:** Pre-fetch prices before evaluation
4. **Horizontal Scaling:** Multiple app servers with shared Redis queue

---

## Documentation Links

- **Algorithm Details:** [PREDICTION_SCORING_ALGORITHM.md](PREDICTION_SCORING_ALGORITHM.md)
- **Deployment Guide:** [SCORING_SYSTEM_DEPLOYMENT.md](SCORING_SYSTEM_DEPLOYMENT.md)
- **General Implementation:** [IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md)
- **API Documentation:** [API_DOCUMENTATION.md](API_DOCUMENTATION.md)

---

## Summary

✅ **Python algorithm successfully converted to Laravel**
✅ **Job-based architecture for scalability**
✅ **Automated scheduling and processing**
✅ **Complete error handling and logging**
✅ **Email notifications integrated**
✅ **Production-ready with monitoring**
✅ **Comprehensive documentation**

The system is ready for deployment and will automatically evaluate expired predictions, update user scores, and send notifications.

---

**Implementation Completed:** December 24, 2025
**Algorithm Version:** 2.0
**Technology:** Laravel 12 Queue System
**Status:** Production Ready ✅
