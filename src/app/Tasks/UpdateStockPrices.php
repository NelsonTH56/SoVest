<?php

namespace App\Tasks;

use App\Services\Interfaces\StockDataServiceInterface;
use Illuminate\Support\Facades\Log;

/**
 * UpdateStockPrices
 * 
 * Invocable class for updating stock prices
 */
class UpdateStockPrices
{
    /**
     * The stock data service instance
     * 
     * @var StockDataServiceInterface
     */
    protected $stockService;
    
    /**
     * Create a new instance of the UpdateStockPrices task
     * 
     * @param StockDataServiceInterface $stockService
     * @return void
     */
    public function __construct(StockDataServiceInterface $stockService)
    {
        $this->stockService = $stockService;
    }
    
    /**
     * Execute the task
     * 
     * @return void
     */
    public function __invoke()
    {
        // Log task start
        Log::info("Starting scheduled stock price update");
        
        try {
            // Update all active stocks
            $results = $this->stockService->updateAllStocks();
            
            // Log results
            $successCount = count(array_filter($results));
            $totalCount = count($results);
            Log::info("Stock update completed: $successCount/$totalCount stocks updated successfully");
            
            // Output results if run manually
            if (php_sapi_name() === 'cli') {
                echo "Stock update completed: $successCount/$totalCount stocks updated successfully\n";
                
                foreach ($results as $symbol => $success) {
                    echo "$symbol: " . ($success ? "Updated" : "Failed") . "\n";
                }
            }
        } catch (\Exception $e) {
            // Log error
            Log::error("Error in scheduled update: " . $e->getMessage());
            
            // Output error if run manually
            if (php_sapi_name() === 'cli') {
                echo "Error: " . $e->getMessage() . "\n";
            }
        }
    }
}