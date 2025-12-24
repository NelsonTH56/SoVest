# Prediction Scoring System - Deployment Guide

## Quick Start

### 1. Run Database Migration

```bash
php artisan migrate
```

This adds the `reputation_score` field to the users table with a default of 500.

### 2. Start Queue Worker

```bash
# Development (foreground)
php artisan queue:work

# Production (use supervisor - see below)
```

### 3. Set Up Cron Job

Add to your crontab:

```bash
crontab -e
```

Add this line:

```bash
* * * * * cd /path/to/sovest && php artisan schedule:run >> /dev/null 2>&1
```

### 4. Test the System

```bash
# Manually evaluate expired predictions
php artisan predictions:evaluate

# Process the queue
php artisan queue:work --once

# Check logs
tail -f storage/logs/laravel.log
```

---

## Files Created/Modified

### New Files

1. **[app/Jobs/EvaluatePredictionJob.php](app/Jobs/EvaluatePredictionJob.php)**
   - Main scoring algorithm implementation
   - Handles individual prediction evaluation
   - Sends email notifications

2. **[app/Console/Commands/EvaluateExpiredPredictions.php](app/Console/Commands/EvaluateExpiredPredictions.php)**
   - Command to find and queue expired predictions
   - Progress bar and logging
   - Force and limit options

3. **[database/migrations/2025_12_24_000001_add_reputation_score_to_users_table.php](database/migrations/2025_12_24_000001_add_reputation_score_to_users_table.php)**
   - Adds/updates reputation_score field
   - Sets default to 500 (new algorithm center)

4. **[PREDICTION_SCORING_ALGORITHM.md](PREDICTION_SCORING_ALGORITHM.md)**
   - Complete algorithm documentation
   - Example scenarios
   - Configuration guide

### Modified Files

5. **[app/Console/Kernel.php](app/Console/Kernel.php)**
   - Scheduled task to run predictions:evaluate hourly
   - Optional market hours schedule included

---

## Production Deployment

### Step 1: Update Environment

```env
# .env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
```

### Step 2: Install Redis (Optional but Recommended)

```bash
# Ubuntu/Debian
sudo apt-get install redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server

# Verify
redis-cli ping  # Should return PONG
```

### Step 3: Set Up Supervisor

Create `/etc/supervisor/conf.d/sovest-worker.conf`:

```ini
[program:sovest-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/sovest/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/sovest/storage/logs/worker.log
stopwaitsecs=3600
```

Start supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start sovest-worker:*
```

### Step 4: Optimize Laravel

```bash
# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

### Step 5: Verify Cron Job

```bash
# Test schedule
php artisan schedule:test

# View scheduled tasks
php artisan schedule:list

# Manually run scheduler
php artisan schedule:run
```

---

## Usage Commands

### Evaluate Predictions

```bash
# Evaluate up to 50 expired predictions (default)
php artisan predictions:evaluate

# Evaluate specific number
php artisan predictions:evaluate --limit=100

# Force re-evaluation (re-scores already evaluated predictions)
php artisan predictions:evaluate --force

# Get help
php artisan predictions:evaluate --help
```

### Queue Management

```bash
# Start queue worker
php artisan queue:work

# Process one job then exit
php artisan queue:work --once

# Restart all workers
php artisan queue:restart

# View failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry <job-id>

# Retry all failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

### Monitoring

```bash
# Monitor queue in real-time
php artisan queue:monitor redis:default --max-jobs=100

# Check queue length
php artisan queue:monitor

# View logs
tail -f storage/logs/laravel.log

# View worker logs (if using supervisor)
tail -f storage/logs/worker.log
```

---

## Configuration Options

### Adjust Evaluation Schedule

Edit `app/Console/Kernel.php`:

```php
// Every hour (default)
$schedule->command('predictions:evaluate')->hourly();

// Every 30 minutes
$schedule->command('predictions:evaluate')->everyThirtyMinutes();

// Daily at 6 PM
$schedule->command('predictions:evaluate')->dailyAt('18:00');

// Market hours only (9 AM - 4 PM EST, Mon-Fri)
$schedule->command('predictions:evaluate')
         ->cron('0 9-16 * * 1-5')
         ->timezone('America/New_York');

// Multiple times per day
$schedule->command('predictions:evaluate')->twiceDaily(9, 18);
```

### Adjust Processing Limits

Edit command signature in `app/Console/Commands/EvaluateExpiredPredictions.php`:

```php
protected $signature = 'predictions:evaluate
                        {--limit=100 : Maximum number of predictions}';  // Changed from 50
```

### Adjust Queue Priority

```php
// High priority for recent predictions
EvaluatePredictionJob::dispatch($predictionId)->onQueue('high');

// Low priority for old predictions
EvaluatePredictionJob::dispatch($predictionId)->onQueue('low');
```

Start workers with priorities:

```bash
php artisan queue:work --queue=high,default,low
```

---

## Monitoring & Alerts

### Laravel Horizon (Recommended for Production)

```bash
composer require laravel/horizon
php artisan horizon:install
php artisan migrate

# Start Horizon
php artisan horizon

# Dashboard: http://yourapp.com/horizon
```

### Database Queue Table

Check pending jobs:

```sql
SELECT * FROM jobs ORDER BY created_at DESC LIMIT 10;
```

Check failed jobs:

```sql
SELECT * FROM failed_jobs ORDER BY failed_at DESC LIMIT 10;
```

### Log Monitoring

```bash
# Install log viewer
composer require rap2hpoutre/laravel-log-viewer

# View logs at: http://yourapp.com/logs
```

---

## Troubleshooting

### Issue: Predictions Not Being Evaluated

**Check 1: Cron is Running**
```bash
# View cron logs
grep CRON /var/log/syslog

# Test schedule manually
php artisan schedule:run
```

**Check 2: Queue Worker is Running**
```bash
# Check supervisor status
sudo supervisorctl status

# Restart workers
sudo supervisorctl restart sovest-worker:*
```

**Check 3: Jobs Are Being Created**
```bash
# Check jobs table
SELECT COUNT(*) FROM jobs;

# Check failed jobs
SELECT COUNT(*) FROM failed_jobs;
```

### Issue: Jobs Failing

**View Failed Jobs:**
```bash
php artisan queue:failed
```

**View Error Details:**
```bash
php artisan queue:failed-table
php artisan migrate

# Then check failed_jobs table
SELECT * FROM failed_jobs ORDER BY failed_at DESC;
```

**Retry with Debugging:**
```bash
php artisan queue:work --once --verbose
```

### Issue: Missing Stock Prices

**Check Stock Prices Table:**
```sql
SELECT symbol, COUNT(*) as price_count
FROM stock_prices sp
JOIN stocks s ON sp.stock_id = s.stock_id
GROUP BY symbol;
```

**Fetch Missing Prices:**
```php
// Add to StockDataService or create command
php artisan stocks:fetch-prices AAPL
```

### Issue: Scores Not Updating

**Check Migration Status:**
```bash
php artisan migrate:status

# If pending, run
php artisan migrate
```

**Check User Scores:**
```sql
SELECT id, email, reputation_score FROM users LIMIT 10;
```

**Clear Cache:**
```bash
php artisan cache:clear
php artisan config:clear
```

---

## Performance Tuning

### Database Indexing

```sql
-- Add index on predictions for faster queries
CREATE INDEX idx_predictions_active_end_date
ON predictions(is_active, end_date);

-- Add index on stock_prices for faster lookups
CREATE INDEX idx_stock_prices_symbol_date
ON stock_prices(stock_id, price_date);
```

### Queue Workers

```bash
# Multiple workers for parallel processing
php artisan queue:work --queue=default --sleep=3 --tries=3 &
php artisan queue:work --queue=default --sleep=3 --tries=3 &
php artisan queue:work --queue=default --sleep=3 --tries=3 &
```

Or use supervisor with `numprocs=4`.

### Redis Configuration

Edit `config/database.php`:

```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    'options' => [
        'cluster' => env('REDIS_CLUSTER', 'redis'),
        'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
    ],
    'default' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_DB', '0'),
        'read_write_timeout' => 60, // Increase for long-running jobs
    ],
],
```

---

## Rollback Plan

If you need to rollback:

### Step 1: Stop Processing

```bash
# Stop cron
crontab -e  # Comment out the schedule line

# Stop workers
sudo supervisorctl stop sovest-worker:*
```

### Step 2: Rollback Migration (Optional)

```bash
php artisan migrate:rollback --step=1
```

This will restore the old default for reputation_score.

### Step 3: Clear Queue

```bash
php artisan queue:flush
php artisan queue:clear
```

---

## Testing Checklist

- [ ] Migration ran successfully
- [ ] Cron job is scheduled
- [ ] Queue worker is running
- [ ] Manual evaluation works: `php artisan predictions:evaluate`
- [ ] Jobs are being created in `jobs` table
- [ ] Jobs are being processed (check logs)
- [ ] User scores are updating
- [ ] Email notifications are sending
- [ ] Leaderboard cache is clearing
- [ ] No errors in `storage/logs/laravel.log`
- [ ] Failed jobs table is empty or retry works

---

## Support

For issues or questions:

1. Check logs: `storage/logs/laravel.log`
2. Review failed jobs: `php artisan queue:failed`
3. Read documentation: [PREDICTION_SCORING_ALGORITHM.md](PREDICTION_SCORING_ALGORITHM.md)
4. Test manually: `php artisan predictions:evaluate --limit=1`

---

**Deployment Date:** December 24, 2025
**Algorithm Version:** 2.0
**System:** Laravel 12 Queue System
