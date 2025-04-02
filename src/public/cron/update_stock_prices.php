<?php
/**
 * SoVest Stock Price Update Cron Job
 * 
 * This script should be run regularly via cron to update stock prices
 * Example cron entry (every hour):
 * 0 * * * * php /path/to/SoVest/SoVest_code/cron/update_stock_prices.php
 */

// Include the StockDataService
require_once __DIR__ . '/../services/StockDataService.php';

// Log script start
writeApiLog("Starting scheduled stock price update");

try {
    // Create service instance
    $stockService = new StockDataService();
    
    // Update all active stocks
    $results = $stockService->updateAllStocks();
    
    // Log results
    $successCount = count(array_filter($results));
    $totalCount = count($results);
    writeApiLog("Stock update completed: $successCount/$totalCount stocks updated successfully");
    
    // Output results if run manually
    if (php_sapi_name() === 'cli') {
        echo "Stock update completed: $successCount/$totalCount stocks updated successfully\n";
        
        foreach ($results as $symbol => $success) {
            echo "$symbol: " . ($success ? "Updated" : "Failed") . "\n";
        }
    }
    
} catch (Exception $e) {
    // Log error
    writeApiLog("Error in scheduled update: " . $e->getMessage());
    
    // Output error if run manually
    if (php_sapi_name() === 'cli') {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>