<?php

namespace App\Providers;

use App\Services\Interfaces\ResponseFormatterInterface;
use App\Services\ResponseFormatter;
use Illuminate\Support\ServiceProvider;
use App\Services\Interfaces\PredictionScoringServiceInterface;
use App\Services\PredictionScoringService;
use App\Services\Interfaces\SearchServiceInterface;
use App\Services\SearchService;
use App\Services\Interfaces\StockDataServiceInterface;
use App\Services\StockDataService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PredictionScoringServiceInterface::class, PredictionScoringService::class);
        $this->app->bind(SearchServiceInterface::class, SearchService::class);
        $this->app->bind(StockDataServiceInterface::class, StockDataService::class);
        $this->app->bind(ResponseFormatterInterface::class, ResponseFormatter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
