<?php
/**
 * SoVest Stock Data Service
 * 
 * This service is responsible for fetching, storing, and managing stock data
 */

// Include config file
require_once __DIR__ . '/../config/api_config.php';
require_once __DIR__ . '/../bootstrap/database.php';

use Database\Models\Stock;
use Database\Models\StockPrice;
use Illuminate\Database\Capsule\Manager as DB;

class StockDataService {
    private $lastApiCall = 0;
    
    /**
     * Constructor
     */
    public function __construct() {
        // No need to establish database connection as Eloquent handles it
    }
    
    /**
     * Add a stock to track
     * 
     * @param string $symbol Stock symbol
     * @param string $name Stock name
     * @param string $description Stock description
     * @param string $sector Stock sector
     * @return bool Success status
     */
    public function addStock($symbol, $name, $description = '', $sector = '') {
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
            
            // Fetch initial price data
            $this->fetchAndStoreStockData($symbol);
            return true;
        } catch (\Exception $e) {
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
    public function removeStock($symbol) {
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
        } catch (\Exception $e) {
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
    public function getStocks($activeOnly = true) {
        try {
            $query = Stock::query();
            
            if ($activeOnly) {
                $query->where('active', 1);
            }
            
            return $query->orderBy('symbol')->get()->toArray();
        } catch (\Exception $e) {
            writeApiLog("Error getting stocks: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Fetch stock data from API
     * 
     * @param string $symbol Stock symbol
     * @return array|bool Stock data or false on failure
     */
    public function fetchStockData($symbol) {
        // Respect rate limiting
        $this->respectRateLimit();
        
        $symbol = strtoupper($symbol);
        $apiKey = ALPHA_VANTAGE_API_KEY;
        $url = ALPHA_VANTAGE_BASE_URL . "?function=GLOBAL_QUOTE&symbol=$symbol&apikey=$apiKey";
        
        writeApiLog("Fetching stock data for $symbol");
        
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
            $quote = $data['Global Quote'];
            return [
                'symbol' => $symbol,
                'price' => isset($quote['05. price']) ? (float)$quote['05. price'] : 0,
                'change' => isset($quote['09. change']) ? (float)$quote['09. change'] : 0,
                'change_percent' => isset($quote['10. change percent']) ? 
                    (float)str_replace('%', '', $quote['10. change percent']) : 0,
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
        
        writeApiLog("No data returned for $symbol");
        return false;
    }
    
    /**
     * Fetch and store stock data
     * 
     * @param string $symbol Stock symbol
     * @return bool Success status
     */
    public function fetchAndStoreStockData($symbol) {
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
    public function storeStockPrice($symbol, $price, $timestamp = null) {
        try {
            $symbol = strtoupper($symbol);
            $price = (float)$price;
            $timestamp = $timestamp ?: date('Y-m-d H:i:s');
            
            // Get the stock by symbol
            $stock = Stock::where('symbol', $symbol)->first();
            
            if (!$stock) {
                writeApiLog("Error: Stock not found for symbol $symbol");
                return false;
            }
            
            // Create new stock price record
            $stockPrice = new StockPrice();
            $stockPrice->stock_id = $stock->stock_id;
            $stockPrice->price_date = $timestamp;
            $stockPrice->close_price = $price;
            $stockPrice->save();
            
            // Update last_updated in stocks table
            $stock->updated_at = $timestamp;
            $stock->save();
            
            return true;
        } catch (\Exception $e) {
            writeApiLog("Error storing price: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get latest price for a stock
     * 
     * @param string $symbol Stock symbol
     * @return float|bool Price or false if not found
     */
    public function getLatestPrice($symbol) {
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
                return (float)$latestPrice->close_price;
            }
            
            return false;
        } catch (\Exception $e) {
            writeApiLog("Error getting latest price: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get price history for a stock
     * 
     * @param string $symbol Stock symbol
     * @param int $days Number of days to look back
     * @return array Price history data
     */
    public function getPriceHistory($symbol, $days = 30) {
        try {
            $symbol = strtoupper($symbol);
            $days = (int)$days;
            
            // Get the stock by symbol
            $stock = Stock::where('symbol', $symbol)->first();
            
            if (!$stock) {
                return [];
            }
            
            // Calculate the start date
            $startDate = date('Y-m-d H:i:s', strtotime("-$days days"));
            
            // Get price history
            $prices = StockPrice::where('stock_id', $stock->stock_id)
                ->where('price_date', '>=', $startDate)
                ->orderBy('price_date')
                ->get();
            
            $history = [];
            foreach ($prices as $price) {
                $history[] = [
                    'price' => (float)$price->close_price,
                    'timestamp' => $price->price_date
                ];
            }
            
            return $history;
        } catch (\Exception $e) {
            writeApiLog("Error getting price history: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update all active stocks
     * 
     * @return array Results for each stock update
     */
    public function updateAllStocks() {
        $stocks = $this->getStocks(true);
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
     * Initialize default stocks
     * 
     * @return array Results of each stock addition
     */
    public function initializeDefaultStocks() {
        global $DEFAULT_STOCKS;
        $results = [];
        
        foreach ($DEFAULT_STOCKS as $symbol => $name) {
            $results[$symbol] = $this->addStock($symbol, $name);
        }
        
        return $results;
    }
    
    /**
     * Respect API rate limits
     */
    private function respectRateLimit() {
        if ($this->lastApiCall > 0) {
            $elapsed = time() - $this->lastApiCall;
            $waitTime = (60 / API_RATE_LIMIT) - $elapsed;
            
            if ($waitTime > 0) {
                writeApiLog("Rate limiting: waiting $waitTime seconds");
                sleep($waitTime);
            }
        }
    }
    
    /**
     * Destructor - no action needed as Eloquent manages connections
     */
    public function __destruct() {
        // No need to close connections with Eloquent
    }
}
?>