<?php

namespace App\Tasks;

use App\Services\Interfaces\StockDataServiceInterface;
use Illuminate\Support\Facades\Log;

/**
 * UpdateStockListings
 * 
 * Invocable class for updating stock listings
 */
class UpdateStockListings
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
        Log::info("Starting scheduled stock listings update");
        
        try {
            // Update all active stocks
            $results = $this->stockService->updateStockListings();
            
            Log::info("Stock update completed: Stock listings updated successfully");
            
            // Output results if run manually
            if (php_sapi_name() === 'cli') {
                echo "Stock update completed: Stock listings updated successfully\n";
                echo ($results ? "Updated" : "Failed") . "\n";
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