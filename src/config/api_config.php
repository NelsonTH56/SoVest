<?php
/**
 * SoVest Stock API Configuration
 * 
 * This file contains configuration settings for stock data APIs
 * Updated to use Laravel's configuration format
 */

// Import the ApiLogHelper class
use App\Helpers\ApiLogHelper;

// Create a configuration array that Laravel can use
$config = [
    // Alpha Vantage API settings
    'ALPHA_VANTAGE_API_KEY' => env('ALPHA_VANTAGE_API_KEY', ''),
    'ALPHA_VANTAGE_BASE_URL' => 'https://www.alphavantage.co/query',
    
    // Maximum number of API requests per minute
    'API_RATE_LIMIT' => 5,
    
    // Logging settings
    'STOCK_API_LOG_FILE' => __DIR__ . '/../logs/stock_api.log',
    
    // Stock update interval (in seconds)
    'STOCK_UPDATE_INTERVAL' => 3600, // 1 hour
    
    // List of default stocks to track
    'DEFAULT_STOCKS' => [
        'AAPL' => 'Apple Inc.',
        'MSFT' => 'Microsoft Corporation', 
        'GOOGL' => 'Alphabet Inc.'
    ],
];

// Define constants for backward compatibility
// This allows existing code to continue working without changes
foreach ($config as $key => $value) {
    if (!defined($key) && !is_array($value)) {
        define($key, $value);
    }
}

// Global function for backward compatibility
if (!function_exists('writeApiLog')) {
    /**
     * Write to the API log file (global function for backward compatibility)
     * 
     * @param string $message Message to write to log
     * @return void
     */
    function writeApiLog($message) {
        ApiLogHelper::writeApiLog($message);
    }
}

// Validate API key is not empty
if ($config['ALPHA_VANTAGE_API_KEY'] === '') {
    error_log("Warning: ALPHA_VANTAGE_API_KEY is not set in the .env file");
    writeApiLog("API key missing - stock data functions will not work properly");
}

// Return the configuration array for Laravel to use
return $config;