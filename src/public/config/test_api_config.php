<?php
// Test script to verify the API configuration is loading correctly

// Include the API configuration file
require_once '../SoVest_code/config/api_config.php';

// Check if the API key is defined
echo "ALPHA_VANTAGE_API_KEY defined: " . (defined('ALPHA_VANTAGE_API_KEY') ? 'Yes' : 'No') . "\n";
echo "API key value: " . (ALPHA_VANTAGE_API_KEY ? 'Not empty' : 'Empty') . "\n";

// Check other constants
echo "ALPHA_VANTAGE_BASE_URL: " . ALPHA_VANTAGE_BASE_URL . "\n";
echo "API_RATE_LIMIT: " . API_RATE_LIMIT . "\n";
echo "STOCK_API_LOG_FILE: " . STOCK_API_LOG_FILE . "\n";

// Test the writeApiLog function
echo "Testing writeApiLog function...\n";
writeApiLog("API configuration test executed");
echo "Log entry written. Check logs/stock_api.log\n";
?>