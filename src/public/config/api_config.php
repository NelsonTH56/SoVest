<?php
/**
 * SoVest Stock API Configuration
 * 
 * This file contains configuration settings for stock data APIs
 * Updated to load API key from .env file instead of hardcoded value
 */

// Include the database configuration to reuse .env loading functionality
require_once dirname(__DIR__) . '/includes/db_config.php';

// Define API settings
// Get API key from environment variable with error handling
try {
    define('ALPHA_VANTAGE_API_KEY', getEnvVar('ALPHA_VANTAGE_API_KEY', ''));
    
    // Validate API key is not empty
    if (ALPHA_VANTAGE_API_KEY === '') {
        error_log("Warning: ALPHA_VANTAGE_API_KEY is not set in the .env file");
        writeApiLog("API key missing - stock data functions will not work properly");
    }
} catch (Exception $e) {
    error_log("Error loading Alpha Vantage API key: " . $e->getMessage());
    define('ALPHA_VANTAGE_API_KEY', '');
}

define('ALPHA_VANTAGE_BASE_URL', 'https://www.alphavantage.co/query');

// Maximum number of API requests per minute
define('API_RATE_LIMIT', 5);

// Logging settings
define('STOCK_API_LOG_FILE', __DIR__ . '/../logs/stock_api.log');

// Stock update interval (in seconds)
define('STOCK_UPDATE_INTERVAL', 3600); // 1 hour

// List of default stocks to track
$DEFAULT_STOCKS = [
    'AAPL' => 'Apple Inc.',
    'MSFT' => 'Microsoft Corporation', 
    'GOOGL' => 'Alphabet Inc.'
];

// Function to write to the API log
function writeApiLog($message) {
    $logDir = dirname(STOCK_API_LOG_FILE);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message" . PHP_EOL;
    file_put_contents(STOCK_API_LOG_FILE, $logEntry, FILE_APPEND);
}
?>