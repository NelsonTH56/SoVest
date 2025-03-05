<?php
/**
 * Stock search API endpoint
 * 
 * This endpoint allows searching for stocks by symbol or name.
 */

// Include Eloquent configuration
require_once '../bootstrap/database.php';

// Import the Stock model
use Database\Models\Stock;

header('Content-Type: application/json');

// Check for search term
if (!isset($_GET['term']) || empty($_GET['term'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Search term is required',
        'data' => []
    ]);
    exit;
}

$searchTerm = $_GET['term'];

try {
    // Search for stocks using Eloquent
    $stocks = Stock::where('symbol', 'LIKE', "%$searchTerm%")
        ->orWhere('company_name', 'LIKE', "%$searchTerm%")
        ->orderByRaw("CASE 
            WHEN symbol = ? THEN 1
            WHEN symbol LIKE ? THEN 2
            WHEN company_name LIKE ? THEN 3
            ELSE 4
          END, symbol ASC", [$searchTerm, "$searchTerm%", "$searchTerm%"])
        ->limit(10)
        ->get(['stock_id', 'symbol', 'company_name']);
    
    $formattedStocks = [];
    foreach ($stocks as $stock) {
        $formattedStocks[] = [
            'id' => $stock->stock_id,
            'symbol' => $stock->symbol,
            'name' => $stock->company_name,
            'display' => "{$stock->symbol} - {$stock->company_name}"
        ];
    }
    
    // Return results
    echo json_encode([
        'success' => true,
        'message' => '',
        'data' => $formattedStocks
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error searching for stocks: ' . $e->getMessage(),
        'data' => []
    ]);
}
?>