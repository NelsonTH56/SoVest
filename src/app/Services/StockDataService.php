<?php
/**
 * SoVest - Stock Data Service
 *
 * This service provides functionality for managing stock data and prices.
 * It centralizes all stock data operations for easier maintenance.
 */

namespace App\Services;

use App\Services\Interfaces\StockDataServiceInterface;
use App\Models\Stock;
use App\Models\StockPrice;
use App\Jobs\FetchHistoricalPricesJob;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class StockDataService implements StockDataServiceInterface
{
    private $lastApiCall = 0;

    // Cache TTL in seconds (24 hours = 86400 seconds)
    private const CACHE_TTL = 86400;

    // Cache key prefix for stock prices
    private const CACHE_PREFIX = 'stock_price_';

    // Cache key prefix for price history
    private const HISTORY_CACHE_PREFIX = 'stock_history_';

    // Cache TTL for price history (1 hour - charts need reasonably fresh data)
    private const HISTORY_CACHE_TTL = 3600;

    /**
     * Add a stock to track
     * 
     * @param string $symbol Stock symbol
     * @param string $name Stock name
     * @param string $description Stock description
     * @param string $sector Stock sector
     * @return bool Success status
     */
    public function addStock($symbol, $name, $description = '', $sector = '')
    {
        try {
            $symbol = strtoupper($symbol);

            // Check if stock already exists
            $stock = Stock::where('symbol', $symbol)->first();

            if ($stock) {
                // Update existing stock
                $stock->company_name = $name;
                $stock->sector = $sector;
                $stock->save();
            } else {
                // Insert new stock
                $stock = new Stock();
                $stock->symbol = $symbol;
                $stock->company_name = $name;
                $stock->sector = $sector;
                $stock->created_at = date('Y-m-d H:i:s');
                $stock->save();
            }
            return true;
        } catch (Exception $e) {
            writeApiLog("Error adding stock: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove a stock from tracking
     * 
     * @param string $symbol Stock symbol
     * @return bool Success status
     */
    public function removeStock($symbol)
    {
        try {
            $symbol = strtoupper($symbol);

            // Find the stock
            $stock = Stock::where('symbol', $symbol)->first();

            if ($stock) {
                // Soft delete by marking as inactive
                // We'll assume there's an 'active' column, if not we should add it
                // or implement another mechanism
                $stock->active = 0;
                $stock->save();
                return true;
            }

            return false;
        } catch (Exception $e) {
            writeApiLog("Error removing stock: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all tracked stocks
     * 
     * @param bool $activeOnly Only return active stocks
     * @return array Array of stocks
     */
    public function getStocks($activeOnly = true)
    {
        try {
            $query = Stock::query();

            if ($activeOnly) {
                $query->where('active', 1);
            }

            return $query->orderBy('symbol')->get()->toArray();
        } catch (Exception $e) {
            writeApiLog("Error getting stocks: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Fetch stock data from API (with caching)
     *
     * Checks in order: 1) Laravel cache, 2) Database (today's price), 3) API
     * Results are cached for 24 hours to minimize API hits.
     *
     * @param string $symbol Stock symbol
     * @param bool $forceRefresh Skip cache and fetch fresh data from API
     * @return array|bool Stock data or false on failure
     */
    public function fetchStockData($symbol, $forceRefresh = false)
    {
        $symbol = strtoupper($symbol);
        $cacheKey = self::CACHE_PREFIX . $symbol;

        // Layer 1: Check Laravel cache (unless forcing refresh)
        if (!$forceRefresh && Cache::has($cacheKey)) {
            writeApiLog("Cache hit for $symbol");
            return Cache::get($cacheKey);
        }

        // Layer 2: Check database for today's price (unless forcing refresh)
        if (!$forceRefresh) {
            $cachedData = $this->getCachedStockData($symbol);
            if ($cachedData !== false) {
                // Store in Laravel cache for faster subsequent lookups
                Cache::put($cacheKey, $cachedData, self::CACHE_TTL);
                writeApiLog("Database cache hit for $symbol");
                return $cachedData;
            }
        }

        // Layer 3: Fetch from API
        $this->respectRateLimit();

        $apiKey = Config::get("api_config.ALPHA_VANTAGE_API_KEY");
        $url = Config::get("api_config.ALPHA_VANTAGE_BASE_URL") . "?function=GLOBAL_QUOTE&symbol=$symbol&apikey=$apiKey";

        writeApiLog("Fetching stock data for $symbol from API");

        // Make API request
        $response = file_get_contents($url);

        if ($response === false) {
            writeApiLog("API request failed for $symbol");
            return false;
        }

        $this->lastApiCall = time();
        $data = json_decode($response, true);

        // Check for API errors
        if (isset($data['Error Message'])) {
            writeApiLog("API Error: " . $data['Error Message']);
            return false;
        }

        // Parse stock data
        if (isset($data['Global Quote']) && !empty($data['Global Quote'])) {
            error_log("Data: " . print_r($data, true));
            $quote = $data['Global Quote'];
            $stockData = [
                'symbol' => $symbol,
                'price' => isset($quote['05. price']) ? (float) $quote['05. price'] : 0,
                'change' => isset($quote['09. change']) ? (float) $quote['09. change'] : 0,
                'change_percent' => isset($quote['10. change percent']) ?
                    (float) str_replace('%', '', $quote['10. change percent']) : 0,
                'timestamp' => date('Y-m-d H:i:s')
            ];

            // Cache the result in Laravel cache
            Cache::put($cacheKey, $stockData, self::CACHE_TTL);

            return $stockData;
        }

        writeApiLog("No data returned for $symbol");
        return false;
    }

    /**
     * Get cached stock data from database (today's price)
     *
     * @param string $symbol Stock symbol
     * @return array|bool Stock data array or false if not found/stale
     */
    private function getCachedStockData($symbol)
    {
        try {
            $symbol = strtoupper($symbol);
            $today = date('Y-m-d');

            $stock = Stock::where('symbol', $symbol)->first();
            if (!$stock) {
                return false;
            }

            // Look for today's price in the database
            $todayPrice = StockPrice::where('stock_id', $stock->stock_id)
                ->where('price_date', $today)
                ->first();

            if ($todayPrice) {
                return [
                    'symbol' => $symbol,
                    'price' => (float) $todayPrice->close_price,
                    'change' => 0, // We don't store change in DB, would need yesterday's price
                    'change_percent' => 0,
                    'timestamp' => $todayPrice->price_date . ' 00:00:00',
                    'cached' => true
                ];
            }

            return false;
        } catch (Exception $e) {
            writeApiLog("Error checking cached data: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch and store stock data
     * 
     * @param string $symbol Stock symbol
     * @return bool Success status
     */
    public function fetchAndStoreStockData($symbol)
    {
        $data = $this->fetchStockData($symbol);

        if ($data === false) {
            return false;
        }

        return $this->storeStockPrice($data['symbol'], $data['price']);
    }

    /**
     * Store stock price in database
     * 
     * @param string $symbol Stock symbol
     * @param float $price Stock price
     * @param string $timestamp Timestamp (defaults to now)
     * @return bool Success status
     */
    public function storeStockPrice($symbol, $price, $timestamp = null)
    {
        try {
            $symbol = strtoupper($symbol);
            $price = (float) $price;
            $priceDate = $timestamp ? date('Y-m-d', strtotime($timestamp)) : date('Y-m-d');

            // Get the stock by symbol
            $stock = Stock::where('symbol', $symbol)->first();

            if (!$stock) {
                writeApiLog("Error: Stock not found for symbol $symbol");
                error_log("Error: Stock not found for symbol $symbol");
                return false;
            }

            // Check if price already exists for this date
            $existingPrice = StockPrice::where('stock_id', $stock->stock_id)
                ->where('price_date', $priceDate)
                ->first();

            if ($existingPrice) {
                // Update existing price
                $existingPrice->close_price = $price;
                $existingPrice->open_price = $price; // Use same price if no open price available
                $existingPrice->high_price = $price; // Use same price if no high price available
                $existingPrice->low_price = $price; // Use same price if no low price available
                $existingPrice->save();
                error_log("Updated existing price for $symbol on $priceDate: $price");
            } else {
                // Create new stock price record
                $stockPrice = new StockPrice();
                $stockPrice->stock_id = $stock->stock_id;
                $stockPrice->price_date = $priceDate;
                $stockPrice->close_price = $price;
                $stockPrice->open_price = $price; // Use same price if no open price available
                $stockPrice->high_price = $price; // Use same price if no high price available
                $stockPrice->low_price = $price; // Use same price if no low price available
                $stockPrice->save();
                error_log("Stored new price for $symbol on $priceDate: $price");
            }

            // Update Laravel cache with the new price
            $cacheKey = self::CACHE_PREFIX . $symbol;
            $cachedData = [
                'symbol' => $symbol,
                'price' => $price,
                'change' => 0,
                'change_percent' => 0,
                'timestamp' => $priceDate . ' 00:00:00',
                'cached' => true
            ];
            Cache::put($cacheKey, $cachedData, self::CACHE_TTL);

            // Clear price history cache since we have new data
            $this->clearPriceHistoryCache($symbol);

            return true;
        } catch (Exception $e) {
            writeApiLog("Error storing price: " . $e->getMessage());
            error_log("Error storing price for $symbol: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get latest price for a stock
     * 
     * @param string $symbol Stock symbol
     * @return float|bool Price or false if not found
     */
    public function getLatestPrice($symbol)
    {
        try {
            $symbol = strtoupper($symbol);

            // Get the stock by symbol
            $stock = Stock::where('symbol', $symbol)->first();

            if (!$stock) {
                return false;
            }

            // Get the latest price record
            $latestPrice = StockPrice::where('stock_id', $stock->stock_id)
                ->orderBy('price_date', 'desc')
                ->first();

            if ($latestPrice) {
                return (float) $latestPrice->close_price;
            }

            return false;
        } catch (Exception $e) {
            writeApiLog("Error getting latest price: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get price history for a stock (with caching)
     *
     * If insufficient data exists in database, automatically fetches from API.
     *
     * @param string $symbol Stock symbol
     * @param int $days Number of days to look back
     * @return array Price history data with OHLC values
     */
    public function getPriceHistory($symbol, $days = 30)
    {
        $symbol = strtoupper($symbol);
        $days = (int) $days;
        $cacheKey = self::HISTORY_CACHE_PREFIX . $symbol . '_' . $days;

        return Cache::remember($cacheKey, self::HISTORY_CACHE_TTL, function () use ($symbol, $days) {
            try {
                // Get the stock by symbol
                $stock = Stock::where('symbol', $symbol)->first();

                if (!$stock) {
                    return [];
                }

                // Calculate the start date
                $startDate = date('Y-m-d', strtotime("-$days days"));

                // Get price history with efficient query (only needed columns)
                $prices = StockPrice::where('stock_id', $stock->stock_id)
                    ->where('price_date', '>=', $startDate)
                    ->orderBy('price_date')
                    ->get(['price_date', 'close_price', 'open_price', 'high_price', 'low_price', 'volume']);

                $history = [];
                foreach ($prices as $price) {
                    $date = $price->price_date instanceof \Carbon\Carbon
                        ? $price->price_date
                        : \Carbon\Carbon::parse($price->price_date);

                    $history[] = [
                        'date' => $date->format('Y-m-d'),
                        'price' => (float) $price->close_price,
                        'open' => (float) $price->open_price,
                        'high' => (float) $price->high_price,
                        'low' => (float) $price->low_price,
                        'volume' => (int) $price->volume,
                        'timestamp' => $date->format('M j'), // For chart labels (e.g., "Jan 15")
                    ];
                }

                return $history;
            } catch (Exception $e) {
                writeApiLog("Error getting price history: " . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Clear price history cache for a symbol
     *
     * @param string $symbol Stock symbol
     * @return void
     */
    public function clearPriceHistoryCache($symbol)
    {
        $symbol = strtoupper($symbol);
        // Clear common cache durations
        foreach ([7, 30, 60, 90, 365] as $days) {
            Cache::forget(self::HISTORY_CACHE_PREFIX . $symbol . '_' . $days);
        }
    }

    /**
     * Fetch and store historical price data from Alpha Vantage TIME_SERIES_DAILY
     *
     * @param string $symbol Stock symbol
     * @param int $days Number of days of history to fetch (max ~100 with compact output)
     * @return bool Success status
     */
    public function fetchHistoricalPrices($symbol, $days = 30)
    {
        try {
            $symbol = strtoupper($symbol);
            $stock = Stock::where('symbol', $symbol)->first();

            if (!$stock) {
                writeApiLog("Stock not found for historical fetch: $symbol");
                return false;
            }

            // Respect rate limit
            $this->respectRateLimit();

            $apiKey = Config::get("api_config.ALPHA_VANTAGE_API_KEY");
            $baseUrl = Config::get("api_config.ALPHA_VANTAGE_BASE_URL");

            // Use TIME_SERIES_DAILY with compact output (last 100 data points)
            $url = $baseUrl . "?function=TIME_SERIES_DAILY&symbol=$symbol&outputsize=compact&apikey=$apiKey";

            writeApiLog("Fetching historical prices for $symbol");

            $response = file_get_contents($url);

            if ($response === false) {
                writeApiLog("Failed to fetch historical data for $symbol");
                return false;
            }

            $this->lastApiCall = time();
            $data = json_decode($response, true);

            if (isset($data['Error Message'])) {
                writeApiLog("API Error for $symbol: " . $data['Error Message']);
                return false;
            }

            if (isset($data['Note'])) {
                writeApiLog("API rate limit hit: " . $data['Note']);
                return false;
            }

            if (!isset($data['Time Series (Daily)'])) {
                writeApiLog("No time series data returned for $symbol");
                return false;
            }

            $timeSeries = $data['Time Series (Daily)'];
            $stored = 0;
            $startDate = date('Y-m-d', strtotime("-$days days"));

            foreach ($timeSeries as $date => $prices) {
                // Only store data within requested range
                if ($date < $startDate) {
                    continue;
                }

                $open = (float) $prices['1. open'];
                $high = (float) $prices['2. high'];
                $low = (float) $prices['3. low'];
                $close = (float) $prices['4. close'];
                $volume = (int) $prices['5. volume'];

                // Check if price already exists for this date
                $existing = StockPrice::where('stock_id', $stock->stock_id)
                    ->where('price_date', $date)
                    ->first();

                if ($existing) {
                    $existing->open_price = $open;
                    $existing->high_price = $high;
                    $existing->low_price = $low;
                    $existing->close_price = $close;
                    $existing->volume = $volume;
                    $existing->save();
                } else {
                    StockPrice::create([
                        'stock_id' => $stock->stock_id,
                        'price_date' => $date,
                        'open_price' => $open,
                        'high_price' => $high,
                        'low_price' => $low,
                        'close_price' => $close,
                        'volume' => $volume,
                    ]);
                }
                $stored++;
            }

            writeApiLog("Stored $stored historical prices for $symbol");

            // Clear cache so new data is used
            $this->clearPriceHistoryCache($symbol);

            return true;
        } catch (Exception $e) {
            writeApiLog("Error fetching historical prices: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update all active stocks
     * 
     * @return array Results for each stock update
     */
    public function updateAllStocks()
    {
        $stocks = $this->getStocks(activeOnly: true);
        $results = [];

        foreach ($stocks as $stock) {
            $symbol = $stock['symbol'];
            $success = $this->fetchAndStoreStockData($symbol);
            $results[$symbol] = $success;

            // Sleep to respect API rate limits
            sleep(12); // Assume 5 requests per minute = 12 seconds between requests
        }

        return $results;
    }

    /**
     * Updates the stocks table with all listed stocks
     * Populates stocks table from https://www.alphavantage.co/query?function=LISTING_STATUS&apikey=YOUR_API_KEY
     * @return array Results of the update operation for each stock
     */
    public function updateStockListings()
    {
        $results = [];

        try {
            // Respect rate limiting
            $this->respectRateLimit();

            // Get API configuration
            $apiKey = Config::get("api_config.ALPHA_VANTAGE_API_KEY");
            $baseUrl = Config::get("api_config.ALPHA_VANTAGE_BASE_URL");

            // Construct API URL for listing status
            $url = $baseUrl . "?function=LISTING_STATUS&apikey=" . $apiKey;

            echo "Fetching stock listings from Alpha Vantage\n";
            writeApiLog("Fetching stock listings from Alpha Vantage");

            // Fetch CSV data
            $csvData = file_get_contents($url);

            if ($csvData === false) {
                $error = "FAILED: Could not fetch stock listings from Alpha Vantage API";
                echo "\n*** WARNING ***\n$error\n***************\n";
                writeApiLog($error);
                throw new Exception($error);
            }

            $this->lastApiCall = time();

            // Parse CSV data
            $lines = explode("\n", $csvData);
            $headers = str_getcsv(array_shift($lines));

            // Create a map of symbols for faster lookup
            $lineCount = count($lines);
            echo "Build stocksToProcess array from $lineCount lines\n";
            $stocksToProcess = [];

            foreach ($lines as $line) {
                if (empty(trim($line)))
                    continue;

                $data = str_getcsv($line);
                if (count($data) < count($headers))
                    continue;

                $stockData = array_combine($headers, $data);

                $symbol = $stockData['symbol'];
                $name = $stockData['name'];
                $exchange = $stockData['exchange'];
                $assetType = $stockData['assetType'];
                $status = $stockData['status'];

                // Use exchange or assetType for sector
                $sector = !empty($exchange) ? $exchange : $assetType;

                // Determine if stock is active
                $isActive = ($status === 'Active') ? 1 : 0;

                $stocksToProcess[$symbol] = [
                    'name' => $name,
                    'sector' => $sector,
                    'active' => $isActive
                ];
            }

            // Get all existing stocks from database
            echo "Build existingStocks array from the existing stocks in the database\n";
            $existingStocks = Stock::all()->toArray();
            $existingSymbols = [];

            foreach ($existingStocks as $stock) {
                $existingSymbols[$stock['symbol']] = $stock;
            }

            // Process stocks in bulk using upsert for efficiency
            echo "Upserting " . count($stocksToProcess) . " stocks in bulk...\n";

            $upsertData = [];
            foreach ($stocksToProcess as $symbol => $data) {
                // Skip symbols longer than 10 chars (DB limit)
                if (strlen($symbol) > 10) {
                    continue;
                }
                $upsertData[] = [
                    'symbol' => $symbol,
                    'company_name' => mb_substr($data['name'], 0, 100), // Truncate to 100 chars
                    'sector' => mb_substr($data['sector'], 0, 50), // Truncate to 50 chars
                    'active' => $data['active'],
                    'created_at' => now(),
                ];
                $results[$symbol] = true;
            }

            // Bulk upsert in chunks of 1000 to avoid memory issues
            $chunks = array_chunk($upsertData, 1000);
            $totalChunks = count($chunks);
            $failedChunks = [];

            foreach ($chunks as $index => $chunk) {
                try {
                    Stock::upsert(
                        $chunk,
                        ['symbol'], // unique key
                        ['company_name', 'sector', 'active'] // columns to update on conflict
                    );
                    echo "  Processed chunk " . ($index + 1) . "/$totalChunks (" . count($chunk) . " stocks)\n";
                } catch (\Exception $e) {
                    $failedChunks[] = $index + 1;
                    echo "  *** ERROR in chunk " . ($index + 1) . ": " . $e->getMessage() . "\n";
                    writeApiLog("Upsert error in chunk " . ($index + 1) . ": " . $e->getMessage());
                }
                if (ob_get_level() > 0) ob_flush();
                flush();
            }

            // Warn if any chunks failed
            if (!empty($failedChunks)) {
                $warning = "WARNING: " . count($failedChunks) . " chunk(s) failed: " . implode(', ', $failedChunks);
                echo "\n*** $warning ***\n";
                writeApiLog($warning);
            }

            // Mark stocks as inactive if they're not in the listing (bulk update)
            $symbolsToDeactivate = [];
            foreach ($existingSymbols as $symbol => $stock) {
                if (!isset($stocksToProcess[$symbol]) && $stock['active']) {
                    $symbolsToDeactivate[] = $symbol;
                    $results[$symbol] = true;
                }
            }

            if (!empty($symbolsToDeactivate)) {
                echo "Marking " . count($symbolsToDeactivate) . " stocks as inactive...\n";
                Stock::whereIn('symbol', $symbolsToDeactivate)->update(['active' => 0]);
            }

            echo "\nStock listings update complete: " . count($results) . " stocks processed\n";
            writeApiLog("Stock listings update complete: " . count($results) . " stocks processed");
            return $results;
        } catch (Exception $e) {
            $error = "Error updating stock listings: " . $e->getMessage();
            echo "\n**********************************\n";
            echo "*** STOCK LISTINGS JOB FAILED ***\n";
            echo "**********************************\n";
            echo $error . "\n";
            writeApiLog($error);

            // Re-throw so the caller knows it failed
            throw $e;
        }
    }

    /**
     * Respect API rate limits
     */
    private function respectRateLimit()
    {
        if ($this->lastApiCall > 0) {
            $elapsed = time() - $this->lastApiCall;
            $waitTime = (60 / Config::get("api_config.API_RATE_LIMIT", 5)) - $elapsed;

            if ($waitTime > 0) {
                writeApiLog("Rate limiting: waiting $waitTime seconds");
                sleep($waitTime);
            }
        }
    }
}