# Stock Database Setup Guide

## ✅ Issue Fixed: writeApiLog Function

### Problem
When running `app(App\Services\StockDataService::class)->updateStockListings()` in Tinker, you got:
```
Error: Call to undefined function App\Services\writeApiLog()
```

### Solution Applied
1. Created global helper function wrapper in **[app/Helpers/helpers.php](app/Helpers/helpers.php)**
2. Registered helper file in **[composer.json](composer.json)** autoload section
3. Ran `composer dump-autoload` to regenerate autoloader

### Verification
✅ Function now works correctly
✅ Stock database successfully populated with **12,471 stocks**

---

## 📊 Stock Database Status

### Current Stats:
- **Total Stocks**: 12,471
- **Source**: Alpha Vantage Listing API
- **Popular Stocks Verified**:
  - ✅ AAPL (Apple Inc)
  - ✅ TSLA (Tesla Inc)
  - ✅ GOOGL (Alphabet Inc - Class A)
  - ✅ MSFT (Microsoft Corporation)
  - ✅ AMZN (Amazon.com Inc)

---

## 🚀 How to Update Stock Listings

### Method 1: Using Test Script (Recommended)
```bash
php test_stock_update.php
```
This script:
- Tests if writeApiLog is working
- Fetches all stock listings from Alpha Vantage
- Updates the database with new/changed stocks
- Shows progress and results

### Method 2: Using Tinker
```bash
php artisan tinker
```
Then run:
```php
app(App\Services\StockDataService::class)->updateStockListings();
```

### Method 3: Create Artisan Command (Future Enhancement)
```bash
php artisan stocks:update
```
*(Not yet implemented, but recommended for production)*

---

## 📝 API Configuration

Your Alpha Vantage API Key is configured in `.env`:
```
ALPHA_VANTAGE_API_KEY=8F2GAZY2YKER91WJ
```

### API Rate Limits:
- **Free Tier**: 5 API requests per minute, 500 per day
- **Stock Listings API**: Uses 1 request (returns all stocks in one call)
- **Individual Stock Quotes**: Uses 1 request per stock

### Log File Location:
API calls are logged to: `storage/logs/stock_api.log`

To view recent API logs:
```bash
tail -f storage/logs/stock_api.log
```

---

## 🔍 Query Stock Database

### View All Stocks
```bash
php db_console.php "SELECT * FROM stocks LIMIT 10"
```

### Search for Specific Stock
```bash
php db_console.php "SELECT * FROM stocks WHERE symbol = 'AAPL'"
```

### Search by Company Name
```bash
php db_console.php "SELECT * FROM stocks WHERE company_name LIKE '%Apple%'"
```

### Count Active Stocks
```bash
php db_console.php "SELECT COUNT(*) FROM stocks WHERE active = 1"
```

### View Stocks by Exchange
```bash
php db_console.php "SELECT sector, COUNT(*) as count FROM stocks GROUP BY sector ORDER BY count DESC LIMIT 10"
```

---

## 📈 Fetch Individual Stock Prices

### Using Tinker
```bash
php artisan tinker
```
```php
$service = app(App\Services\StockDataService::class);

// Get latest price
$price = $service->getLatestPrice('AAPL');
echo "AAPL Price: $" . $price;

// Fetch and store current price
$service->fetchAndStoreStockData('AAPL');

// Get price history (last 30 days)
$history = $service->getPriceHistory('AAPL', 30);
print_r($history);
```

### Create a Price Update Script
```php
// update_stock_price.php
$symbols = ['AAPL', 'TSLA', 'GOOGL', 'MSFT', 'AMZN'];
$service = app(App\Services\StockDataService::class);

foreach ($symbols as $symbol) {
    echo "Updating $symbol...\n";
    $service->fetchAndStoreStockData($symbol);
    sleep(12); // Respect API rate limits (5 per minute = 12 sec between)
}
```

---

## 🛠️ Files Modified/Created

### Created Files:
1. **[app/Helpers/helpers.php](app/Helpers/helpers.php)**
   - Global helper function wrapper for writeApiLog

2. **[test_stock_update.php](test_stock_update.php)**
   - Test script for updating stock listings

3. **[STOCK_DATABASE_SETUP.md](STOCK_DATABASE_SETUP.md)**
   - This documentation file

### Modified Files:
1. **[composer.json](composer.json)**
   - Added helpers.php to autoload files section

---

## ⚠️ Important Notes

### Rate Limiting
- The `StockDataService` automatically respects API rate limits
- 12-second delay between requests when updating multiple stocks
- Use wisely to avoid hitting daily limits

### Database Tables

**stocks** table structure:
- `stock_id` (primary key)
- `symbol` (unique, e.g., "AAPL")
- `company_name` (e.g., "Apple Inc")
- `sector` (exchange/category)
- `active` (1 = active, 0 = delisted)
- `created_at`, `updated_at`

**stock_prices** table structure:
- `price_id` (primary key)
- `stock_id` (foreign key to stocks)
- `price_date` (timestamp)
- `open_price`, `close_price`, `high_price`, `low_price`
- `volume`

### Backup Recommendation
Before major updates:
```bash
mysqldump -u root -p"#nEllyjElly56" sovest stocks stock_prices > stocks_backup.sql
```

---

## 🎯 Quick Commands Reference

```bash
# Update stock listings
php test_stock_update.php

# View stocks in database
php db_console.php "SELECT COUNT(*) FROM stocks"

# Search for a stock
php db_console.php "SELECT * FROM stocks WHERE symbol = 'AAPL'"

# View recent price data
php db_console.php "SELECT * FROM stock_prices ORDER BY price_date DESC LIMIT 10"

# View API logs
tail -20 storage/logs/stock_api.log
```

---

## ✅ Setup Complete!

Your stock database is now populated with 12,471 stocks and ready to use for predictions!

Next steps:
1. Users can now search for stocks when creating predictions
2. Stock prices can be fetched and displayed on prediction cards
3. Consider setting up a scheduled job to update popular stock prices daily
