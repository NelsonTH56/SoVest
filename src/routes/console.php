<?php

use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;
use App\Tasks\UpdateStockListings;
use App\Tasks\UpdateStockPrices;
use App\Tasks\EvaluatePredictions;

Artisan::command('inspire', function () {
    /** @var ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Manual execution of UpdateStockPrices task
Artisan::command('stocks:listings', function () {
    /** @var ClosureCommand $this */
    $this->info('Starting stock listings update...');
    
    try {
        // Use container to make and invoke the class with DI
        $task = app()->make(UpdateStockListings::class);
        $task();
        
        $this->info('Stock listings updated successfully.');
    } catch (\Exception $e) {
        $this->error('Failed to update stock listings: ' . $e->getMessage());
        Log::error('Failed to update stock listings: ' . $e->getMessage());
    }
})->purpose('Update stock prices manually');

// Manual execution of UpdateStockPrices task
Artisan::command('stocks:update', function () {
    /** @var ClosureCommand $this */
    $this->info('Starting stock price update...');
    
    try {
        // Use container to make and invoke the class with DI
        $task = app()->make(UpdateStockPrices::class);
        $task();
        
        $this->info('Stock prices updated successfully.');
    } catch (\Exception $e) {
        $this->error('Failed to update stock prices: ' . $e->getMessage());
        Log::error('Failed to update stock prices: ' . $e->getMessage());
    }
})->purpose('Update stock prices manually');

// Manual execution of EvaluatePredictions task
Artisan::command('predictions:evaluate', function () {
    /** @var ClosureCommand $this */
    $this->info('Starting prediction evaluation...');
    
    try {
        // Use container to make and invoke the class with DI
        $task = app()->make(EvaluatePredictions::class);
        $task();
        
        $this->info('Predictions evaluated successfully.');
    } catch (\Exception $e) {
        $this->error('Failed to evaluate predictions: ' . $e->getMessage());
        Log::error('Failed to evaluate predictions: ' . $e->getMessage());
    }
})->purpose('Evaluate predictions manually');

// Schedule the UpdateStockListings task to run weekly
Schedule::call(function () {
    $task = app()->make(UpdateStockListings::class);
    $task();
})
    ->weekly()
    ->appendOutputTo(storage_path('logs/stock-listings.log'));

// Schedule the UpdateStockPrices task to run hourly
Schedule::call(function () {
    $task = app()->make(UpdateStockPrices::class);
    $task();
})
    ->hourly()
    ->appendOutputTo(storage_path('logs/stock-updates.log'));

// Schedule the EvaluatePredictions task to run daily at midnight
Schedule::call(function () {
    $task = app()->make(EvaluatePredictions::class);
    $task();
})
    ->dailyAt('00:00')
    ->appendOutputTo(storage_path('logs/prediction-evaluations.log'));