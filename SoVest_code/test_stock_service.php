<?php
/**
 * Test script for the Stock Data Service
 */

// Include the StockDataService
require_once __DIR__ . '/services/StockDataService.php';

echo "Initializing StockDataService...\n";

try {
    // Create service instance
    $stockService = new StockDataService();
    
    echo "Successfully created StockDataService\n";
    echo "Initializing default stocks...\n";
    
    // Initialize default stocks
    $results = $stockService->initializeDefaultStocks();
    
    // Display results
    $successCount = count(array_filter($results));
    $totalCount = count($results);
    echo "Default stocks initialized: $successCount/$totalCount added successfully\n";
    
    // Display all stocks
    $stocks = $stockService->getStocks(false);
    echo "\nCurrent stocks in database:\n";
    
    if (empty($stocks)) {
        echo "No stocks found in database.\n";
    } else {
        foreach ($stocks as $stock) {
            echo "Symbol: {$stock['symbol']}, Name: {$stock['name']}, ";
            echo "Active: " . ($stock['active'] ? 'Yes' : 'No') . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>