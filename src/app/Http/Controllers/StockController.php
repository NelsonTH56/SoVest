<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\Prediction;
use App\Services\StockDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockController extends Controller
{
    protected $stockDataService;

    public function __construct(StockDataService $stockDataService)
    {
        $this->stockDataService = $stockDataService;
    }

    /**
     * Display detailed information about a specific stock
     *
     * @param string $symbol Stock symbol (e.g., AAPL, MSFT)
     * @return \Illuminate\View\View
     */
    public function show($symbol)
    {
        // Find the stock by symbol
        $stock = Stock::where('symbol', strtoupper($symbol))->first();

        if (!$stock) {
            return redirect()->route('search')
                ->with('error', 'Stock not found: ' . $symbol);
        }

        // Try to get current price from database first
        $currentPrice = $this->stockDataService->getLatestPrice($stock->symbol);
        $latestPriceDate = null;

        // If price exists in database, get the date
        if ($currentPrice !== false) {
            $latestPriceRecord = \App\Models\StockPrice::where('stock_id', $stock->stock_id)
                ->orderBy('price_date', 'desc')
                ->first();

            if ($latestPriceRecord) {
                $latestPriceDate = $latestPriceRecord->price_date;
            }
        } else {
            // If no cached price, fetch from API
            try {
                $stockData = $this->stockDataService->fetchStockData($stock->symbol);
                if ($stockData && isset($stockData['price'])) {
                    $currentPrice = $stockData['price'];
                    $this->stockDataService->storeStockPrice($stock->symbol, $currentPrice);
                    $latestPriceDate = date('Y-m-d');
                }
            } catch (\Exception $e) {
                // Log error but continue without price
                \Log::error("Failed to fetch price for {$stock->symbol}: " . $e->getMessage());
                $currentPrice = null;
            }
        }

        // Get related predictions for this stock
        $predictions = Prediction::where('stock_id', $stock->stock_id)
            ->where('is_active', true)
            ->with(['user', 'votes'])
            ->orderBy('prediction_date', 'desc')
            ->limit(10)
            ->get();

        // Get 30-day price history for chart (cached)
        $priceHistory = $this->stockDataService->getPriceHistory($stock->symbol, 30);

        // Calculate price change stats for display
        $priceStats = $this->calculatePriceStats($priceHistory);

        // Get current user for layout
        $Curruser = Auth::user();

        return view('stocks.show', [
            'stock' => $stock,
            'currentPrice' => $currentPrice !== false ? $currentPrice : null,
            'latestPriceDate' => $latestPriceDate,
            'predictions' => $predictions,
            'Curruser' => $Curruser,
            'priceHistory' => $priceHistory,
            'priceStats' => $priceStats,
        ]);
    }

    /**
     * Calculate price statistics from history for display
     *
     * @param array $priceHistory
     * @return array
     */
    private function calculatePriceStats(array $priceHistory): array
    {
        if (empty($priceHistory) || count($priceHistory) < 2) {
            return [
                'change' => 0,
                'changePercent' => 0,
                'high' => 0,
                'low' => 0,
                'hasData' => false,
            ];
        }

        $firstPrice = $priceHistory[0]['price'];
        $lastPrice = end($priceHistory)['price'];
        $change = $lastPrice - $firstPrice;
        $changePercent = $firstPrice > 0 ? ($change / $firstPrice) * 100 : 0;

        $allPrices = array_column($priceHistory, 'price');

        return [
            'change' => round($change, 2),
            'changePercent' => round($changePercent, 2),
            'high' => max($allPrices),
            'low' => min($allPrices),
            'hasData' => true,
        ];
    }
}
