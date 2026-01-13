# Search Stock Prices - Implementation & Fix

## Problem
When searching for stocks, the cards showed **"Data unavailable"** instead of displaying current stock prices.

## Root Causes

### 1. Missing `id` Field
- The `SearchService` was returning stocks with `stock_id` field
- The view template was checking for `id` field
- This mismatch caused the "Data unavailable" message

### 2. No Price Data
- Stock listings were populated but no price data existed
- The `stock_prices` table was empty
- Current prices need to be fetched from Alpha Vantage API

## Solutions Applied

### ✅ Fixed Field Naming Issue

**Modified: [SearchService.php](app/Services/SearchService.php)**

1. **Added `id` alias** (Lines 67, 145):
   ```php
   $stockArray['id'] = $stock->stock_id; // Add 'id' alias for consistency
   ```

2. **Added StockDataService dependency injection**:
   ```php
   protected $stockDataService;

   public function __construct(StockDataServiceInterface $stockDataService)
   {
       $this->stockDataService = $stockDataService;
   }
   ```

3. **Fetch and include current prices** (Lines 70-71, 148-149):
   ```php
   $latestPrice = $this->stockDataService->getLatestPrice($stock->symbol);
   $stockArray['current_price'] = $latestPrice !== false ? $latestPrice : null;
   ```

### ✅ Enhanced Stock Cards Display

**Modified: [search/index.blade.php](resources/views/search/index.blade.php)**

Added price badge display (Lines 93-101):
```blade
@if(isset($result['current_price']) && $result['current_price'] !== null)
    <span class="badge bg-info ms-2">
        <i class="bi bi-currency-dollar"></i> {{ number_format($result['current_price'], 2) }}
    </span>
@else
    <span class="badge bg-warning ms-2" title="No price data available">
        <i class="bi bi-exclamation-circle"></i> Price N/A
    </span>
@endif
```

### ✅ Created Price Fetching Script

**Created: [fetch_popular_stock_prices.php](fetch_popular_stock_prices.php)**

This script fetches prices for 15 popular stocks:
- AAPL (Apple)
- MSFT (Microsoft)
- GOOGL (Alphabet/Google)
- AMZN (Amazon)
- TSLA (Tesla)
- META (Meta/Facebook)
- NVDA (NVIDIA)
- AMD (AMD)
- NFLX (Netflix)
- DIS (Disney)
- BA (Boeing)
- JPM (JP Morgan)
- V (Visa)
- WMT (Walmart)
- INTC (Intel)

## How to Populate Stock Prices

### Option 1: Fetch Popular Stocks (Recommended)
```bash
cd c:\Sovest\src
php fetch_popular_stock_prices.php
```

**Time required**: ~3 minutes (12 seconds between each stock)
**Result**: Prices for 15 most popular stocks

### Option 2: Fetch Specific Stock
```bash
php artisan tinker
```
```php
$service = app(App\Services\StockDataService::class);
$service->fetchAndStoreStockData('AAPL');
```

### Option 3: Fetch Multiple Stocks with Script

Create `fetch_custom_stocks.php`:
```php
<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$stocks = ['AAPL', 'TSLA', 'GOOGL']; // Your custom list
$service = app(App\Services\StockDataService::class);

foreach ($stocks as $symbol) {
    echo "Fetching $symbol...\n";
    $service->fetchAndStoreStockData($symbol);
    sleep(12); // API rate limit
}
```

## Current Behavior

### With Price Data:
```
┌─────────────────────────────────────────┐
│ AAPL                                    │
│ Apple Inc                               │
│ [NASDAQ] [$145.32]                      │
│                [Create Prediction] ──>  │
└─────────────────────────────────────────┘
```

### Without Price Data:
```
┌─────────────────────────────────────────┐
│ AAPL                                    │
│ Apple Inc                               │
│ [NASDAQ] [⚠ Price N/A]                  │
│                [Create Prediction] ──>  │
└─────────────────────────────────────────┘
```

## Database Schema

### stocks table:
- `stock_id` (primary key)
- `symbol` (unique)
- `company_name`
- `sector`
- `active`

### stock_prices table:
- `price_id` (primary key)
- `stock_id` (foreign key)
- `price_date` (timestamp)
- `open_price`, `close_price`, `high_price`, `low_price`
- `volume`

## API Rate Limits

**Alpha Vantage Free Tier**:
- 5 API requests per minute
- 500 requests per day
- Script automatically waits 12 seconds between requests

## Quick Commands

```bash
# Fetch popular stock prices
php fetch_popular_stock_prices.php

# Check if prices exist
php db_console.php "SELECT COUNT(*) FROM stock_prices"

# View stock prices
php db_console.php "SELECT s.symbol, sp.close_price, sp.price_date FROM stock_prices sp JOIN stocks s ON sp.stock_id = s.stock_id ORDER BY sp.price_date DESC LIMIT 10"

# Search for Apple
# Visit: http://localhost:8000/search?query=AAPL&type=stocks
```

## Next Steps

1. ✅ Run `php fetch_popular_stock_prices.php` to populate prices
2. ✅ Test search functionality with price display
3. Consider setting up a scheduled task to update prices daily:
   ```bash
   # In crontab or Task Scheduler
   0 16 * * 1-5 cd /path/to/project && php fetch_popular_stock_prices.php
   ```
   (Runs at 4 PM EST weekdays after market close)

## Files Modified

1. ✅ **app/Services/SearchService.php** - Added price fetching logic
2. ✅ **resources/views/search/index.blade.php** - Enhanced stock card display
3. ✅ **fetch_popular_stock_prices.php** - Created price fetching script

## Summary

- ✅ Fixed "Data unavailable" error by adding `id` alias
- ✅ Integrated stock price display in search results
- ✅ Created convenient script to populate popular stock prices
- ✅ Shows informative badge when price data isn't available
- ⏳ Prices need to be fetched (run the script!)

**Status**: Ready to use! Just run the price fetching script to populate data.
