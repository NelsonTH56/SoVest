<?php

namespace App\Jobs;

use App\Services\StockDataService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchHistoricalPricesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $symbol;
    public int $days;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(string $symbol, int $days = 30)
    {
        $this->symbol = strtoupper($symbol);
        $this->days = $days;
    }

    /**
     * Execute the job.
     */
    public function handle(StockDataService $stockDataService): void
    {
        Log::info("FetchHistoricalPricesJob: Fetching {$this->days} days of history for {$this->symbol}");

        $result = $stockDataService->fetchHistoricalPrices($this->symbol, $this->days);

        if ($result) {
            Log::info("FetchHistoricalPricesJob: Successfully fetched prices for {$this->symbol}");
        } else {
            Log::warning("FetchHistoricalPricesJob: Failed to fetch prices for {$this->symbol}");
        }
    }

    /**
     * Get the unique ID for the job (prevents duplicate jobs for same stock).
     */
    public function uniqueId(): string
    {
        return $this->symbol;
    }
}
