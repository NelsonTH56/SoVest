# Prediction Scoring System - Quick Reference

## Setup (One-Time)

```bash
# 1. Run migration
php artisan migrate

# 2. Add to crontab
* * * * * cd /path/to/sovest && php artisan schedule:run >> /dev/null 2>&1

# 3. Start queue worker (or use supervisor)
php artisan queue:work
```

---

## Daily Commands

```bash
# Evaluate expired predictions manually
php artisan predictions:evaluate

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

## Algorithm Constants

| Constant | Value | Purpose |
|----------|-------|---------|
| C (Base Score) | 50.0 | Starting point for each prediction |
| A (Penalty Factor) | 250.0 | Scales error penalties |
| B (Time Bonus) | 5.0 | Rewards early predictions |
| E (Accuracy Threshold) | 0.6 | Strictness for time bonus |
| Under-Prediction Penalty | 1.2 | Multiplier for under-predicting |
| Over-Prediction Penalty | 1.0 | Multiplier for over-predicting |
| Learning Rate | 0.20 | 20% of score change applied |
| Target Mean Score | 500.0 | Center of bell curve |
| Score Range | 0-1000 | Min and max user scores |

---

## Volatility Multipliers

| Sector | Multiplier | Effect |
|--------|------------|--------|
| Technology | 1.5 | +50% reward/penalty |
| Healthcare | 1.3 | +30% reward/penalty |
| Energy | 1.4 | +40% reward/penalty |
| Finance | 1.2 | +20% reward/penalty |
| Industrial | 1.1 | +10% reward/penalty |
| Consumer | 1.0 | Baseline |
| Utilities | 0.8 | -20% reward/penalty |

---

## Score Change Examples

### Accurate Tech Prediction
- Current: 500 → New: ~520
- Change: +20 points

### Wild Guess on Tech
- Current: 500 → New: ~490
- Change: -10 points

### Tiny Safe Prediction
- Current: 500 → New: ~499
- Change: -1 point (anti-gaming)

---

## Troubleshooting

| Problem | Quick Fix |
|---------|-----------|
| Predictions not evaluating | `php artisan predictions:evaluate` |
| Queue not processing | Restart `php artisan queue:work` |
| Scores not updating | Check migration: `php artisan migrate:status` |
| Jobs failing | View errors: `php artisan queue:failed` |
| Missing stock prices | Check StockPrice table has data |

---

## File Locations

- **Job:** `app/Jobs/EvaluatePredictionJob.php`
- **Command:** `app/Console/Commands/EvaluateExpiredPredictions.php`
- **Schedule:** `app/Console/Kernel.php`
- **Migration:** `database/migrations/2025_12_24_*_add_reputation_score_to_users_table.php`

---

## Monitoring Commands

```bash
# Worker status (if using supervisor)
sudo supervisorctl status

# Redis queue length
redis-cli LLEN queues:default

# Recent evaluations
tail -100 storage/logs/laravel.log | grep "Evaluated prediction"

# User scores
mysql -e "SELECT id, email, reputation_score FROM users ORDER BY reputation_score DESC LIMIT 10;"
```

---

## Production Checklist

- [ ] Migration ran successfully
- [ ] Cron job configured
- [ ] Supervisor configured (or queue worker running)
- [ ] Redis installed (optional but recommended)
- [ ] Environment variables set (QUEUE_CONNECTION, MAIL_*)
- [ ] Laravel optimized (config:cache, route:cache)
- [ ] Logs monitored
- [ ] Test evaluation works

---

## Emergency Commands

```bash
# Stop all processing
sudo supervisorctl stop sovest-worker:*

# Clear queue
php artisan queue:flush

# Restart everything
sudo supervisorctl restart sovest-worker:*

# Rollback migration
php artisan migrate:rollback --step=1
```

---

## Documentation

- Full Algorithm: [PREDICTION_SCORING_ALGORITHM.md](PREDICTION_SCORING_ALGORITHM.md)
- Deployment: [SCORING_SYSTEM_DEPLOYMENT.md](SCORING_SYSTEM_DEPLOYMENT.md)
- Summary: [SCORING_ALGORITHM_SUMMARY.md](SCORING_ALGORITHM_SUMMARY.md)
