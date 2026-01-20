<?php

namespace App\Http\Controllers;

use App\Services\Interfaces\ResponseFormatterInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\SavedSearch;
use App\Models\Stock;
use Exception;
use App\Services\Interfaces\SearchServiceInterface;
use App\Services\Interfaces\StockDataServiceInterface;


class SearchController extends Controller
{
    /**
     * @var SearchServiceInterface Search service instance
     */
    protected $searchService;

    /**
     * @var StockDataServiceInterface Stock data service instance
     */
    protected $stockDataService;

    public function __construct(ResponseFormatterInterface $responseFormatter, SearchServiceInterface $searchService, StockDataServiceInterface $stockDataService)
    {
        parent::__construct($responseFormatter);
        $this->searchService = $searchService;
        $this->stockDataService = $stockDataService;
    }

    public function index(Request $request)
    {
        $userID = Auth::id();

        // Get search parameters
        $query = $request->input('query', '');
        $type = $request->input('type', 'all');
        $prediction = $request->input('prediction', '');
        $sort = $request->input('sort', 'relevance');
        $page = (int) $request->input('page', 1);
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $Curruser = Auth::user();

        // Detect prediction intent in search query
        $hasPredictionIntent = $this->detectPredictionIntent($query);

        // Store original type for UI and prioritize stocks if prediction intent is detected
        $originalType = $type;
        if ($hasPredictionIntent && ($type === 'all' || empty($type))) {
            // Only override if user hasn't explicitly chosen a different type
            $type = 'stocks';
        }

        // Load user's saved searches
        $savedSearches = [];
        try {
            $savedSearches = SavedSearch::where('user_id', $userID)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(['search_query', 'search_type', 'created_at', 'id'])
                ->toArray();
        } catch (Exception $e) {
            error_log('Error loading saved searches: ' . $e->getMessage());
        }

        // Perform search if query is provided
        $searchResults = [];
        $totalResults = 0;

        if (!empty($query)) {
            try {
                // Perform search using SearchService
                $searchResults = $this->searchService->performSearch(
                    $query,
                    $type,
                    $prediction,
                    $sort,
                    $limit,
                    $offset
                );
                $totalResults = count($searchResults);
            } catch (Exception $e) {
                error_log('Search error: ' . $e->getMessage());
            }
        }

        // Get user's search history
        $searchHistory = [];
        try {
            $searchHistory = $this->searchService->getSearchHistory(10);
        } catch (Exception $e) {
            error_log('Error loading search history: ' . $e->getMessage());
        }

        // If AJAX request, return JSON
        if ($this->isAjaxRequest()) {
            return response()->json([
                'success' => true,
                'results' => $searchResults,
                'total' => $totalResults
            ]);
        }

        // Set page title and render view
        $pageTitle = "Search";

        // Render the view
        return view('search/index', [
            'query' => $query,
            'type' => $originalType, // Use original type for UI consistency
            'prediction' => $prediction,
            'sort' => $sort,
            'page' => $page,
            'results' => $searchResults,
            'totalResults' => $totalResults,
            'savedSearches' => $savedSearches,
            'searchHistory' => $searchHistory,
            'pageTitle' => $pageTitle,
            'hasPredictionIntent' => $hasPredictionIntent,
            'predictionIntentDetected' => $hasPredictionIntent && $originalType === 'all',
            'Curruser' => $Curruser
        ]);
    }

    /**
     * Detect prediction intent in search query
     * 
     * @param string $query Search query
     * @return bool True if prediction intent detected
     */
    private function detectPredictionIntent($query)
    {
        if (empty($query)) {
            return false;
        }

        // Keywords that indicate prediction intent
        $predictionKeywords = [
            'predict',
            'prediction',
            'forecast',
            'bullish',
            'bearish',
            'stock prediction',
            'market prediction',
            'price target',
            'will rise',
            'will fall',
            'going up',
            'going down',
            'target price',
            'stock forecast',
            'future price'
        ];

        // Case-insensitive check for keywords
        $lowercaseQuery = strtolower($query);

        foreach ($predictionKeywords as $keyword) {
            if (strpos($lowercaseQuery, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get search suggestions for autocomplete
     * 
     * @return void
     */
    public function suggestions(Request $request)
    {
        // Require authentication
        if (!Auth::check()) {
            return $this->jsonError('Authentication required', [], 401);
        }

        $query = $request->input('query', '');
        $type = $request->input('type', 'all');

        // Return empty suggestions if query is too short
        if (empty($query) || strlen($query) < 2) {
            return response()->json(['suggestions' => []]);
        }

        try {
            // Get suggestions using SearchService
            $suggestions = $this->searchService->getSuggestions($query, $type);
            return response()->json(['suggestions' => $suggestions]);
        } catch (Exception $e) {
            return $this->jsonError('Failed to get suggestions: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Save a search to favorites
     * 
     * @return void
     */
    public function saveSearch(Request $request)
    {
        // Require authentication
        if (!Auth::check()) {
            return $this->jsonError('Authentication required', [], 401);
        }

        $query = $request->input('query', '');
        $type = $request->input('type', 'all');

        if (empty($query)) {
            return $this->jsonError('Search query is required');
        }

        try {
            // Use SearchService to save the search
            $result = $this->searchService->saveSearch($query, $type);

            if ($result) {
                // Successfully saved
                $savedSearch = SavedSearch::where('user_id', Auth::id())
                    ->where('search_query', $query)
                    ->where('search_type', $type)
                    ->first();

                return $this->jsonSuccess('Search saved successfully', [
                    'search_id' => $savedSearch ? $savedSearch->id : null
                ]);
            } else {
                return $this->jsonError('Failed to save search');
            }
        } catch (Exception $e) {
            return $this->jsonError('Failed to save search: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Remove a saved search
     * 
     * @return void
     */
    public function removeSavedSearch(Request $request)
    {
        // Require authentication
        if (!Auth::check()) {
            return $this->jsonError('Authentication required', [], 401);
        }

        $searchId = (int) $request->input('search_id', 0);

        if ($searchId <= 0) {
            return $this->jsonError('Invalid search ID');
        }

        try {
            // Use SearchService to remove the saved search
            $result = $this->searchService->removeSavedSearch($searchId);

            if ($result) {
                return $this->jsonSuccess('Saved search removed');
            } else {
                return $this->jsonError('Failed to remove saved search: Search not found');
            }
        } catch (Exception $e) {
            return $this->jsonError('Failed to remove saved search: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Display search history
     * 
     * @return void
     */
    public function history(Request $request)
    {
        // Require authentication
        if (!Auth::check()) {
            return $this->jsonError('Authentication required', [], 401);
        }

        try {
            // Use SearchService to get search history
            $history = $this->searchService->getSearchHistory(20);

            return response()->json([
                'success' => true,
                'history' => $history
            ]);
        } catch (Exception $e) {
            return $this->jsonError('Failed to get search history: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Clear search history
     * 
     * @return void
     */
    public function clearHistory(Request $request)
    {
        // Require authentication
        if (!Auth::check()) {
            return $this->jsonError('Authentication required', [], 401);
        }

        try {
            // Use SearchService to clear search history
            $result = $this->searchService->clearSearchHistory();

            if ($result) {
                return $this->jsonSuccess('Search history cleared');
            } else {
                return $this->jsonSuccess('No search history to clear');
            }
        } catch (Exception $e) {
            return $this->jsonError('Failed to clear search history: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Check if request is AJAX
     * 
     * @return bool True if request is AJAX
     */
    private function isAjaxRequest()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    /**
     * Search for stocks by term
     *
     * Prioritizes:
     * 1. Exact ticker symbol match
     * 2. Ticker starts with search term
     * 3. Company name starts with search term
     * 4. Partial matches
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchStocks(Request $request)
    {
        // Validate the request
        $term = $request->input('term');

        if (empty($term)) {
            return $this->jsonError('Search term is required');
        }

        $term = trim($term);
        $upperTerm = strtoupper($term);

        try {
            // Query with relevance-based ordering
            // Priority 1: Exact symbol match
            // Priority 2: Symbol starts with term
            // Priority 3: Company name starts with term
            // Priority 4: Symbol contains term
            // Priority 5: Company name contains term
            $stocks = Stock::where('active', true)
                ->where(function ($query) use ($term, $upperTerm) {
                    $query->where('symbol', 'LIKE', "%{$term}%")
                        ->orWhere('company_name', 'LIKE', "%{$term}%");
                })
                ->orderByRaw("
                    CASE
                        WHEN UPPER(symbol) = ? THEN 1
                        WHEN UPPER(symbol) LIKE ? THEN 2
                        WHEN UPPER(company_name) LIKE ? THEN 3
                        WHEN UPPER(symbol) LIKE ? THEN 4
                        ELSE 5
                    END
                ", [$upperTerm, $upperTerm . '%', $upperTerm . '%', '%' . $upperTerm . '%'])
                ->orderBy('symbol', 'asc')
                ->limit(10)
                ->get(['stock_id', 'symbol', 'company_name']);

            $formattedResults = [];

            // Format database results
            foreach ($stocks as $stock) {
                $formattedResults[] = [
                    'id' => $stock->stock_id,
                    'symbol' => $stock->symbol,
                    'name' => $stock->company_name
                ];
            }

            return $this->jsonSuccess('Stocks found successfully', $formattedResults);
        } catch (\Exception $e) {
            return $this->jsonError('Error searching for stocks: ' . $e->getMessage());
        }
    }

    /**
     * Fetch stock price via AJAX (POST)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetchStockPrice(Request $request)
    {
        $symbol = $request->input('symbol');

        if (empty($symbol)) {
            return $this->jsonError('Stock symbol is required');
        }

        try {
            // Try to get from database first
            $latestPrice = $this->stockDataService->getLatestPrice($symbol);

            // If not in database, fetch from API
            if ($latestPrice === false) {
                $stockData = $this->stockDataService->fetchStockData($symbol);
                if ($stockData && isset($stockData['price'])) {
                    $latestPrice = $stockData['price'];
                    // Store it for future use
                    $this->stockDataService->storeStockPrice($symbol, $latestPrice);
                }
            }

            if ($latestPrice !== false) {
                return $this->jsonSuccess('Price fetched successfully', [
                    'symbol' => strtoupper($symbol),
                    'price' => (float)$latestPrice
                ]);
            } else {
                return $this->jsonError('Price not available for this stock');
            }
        } catch (\Exception $e) {
            return $this->jsonError('Error fetching stock price: ' . $e->getMessage());
        }
    }

    /**
     * Get stock price by symbol (GET endpoint)
     *
     * @param string $symbol
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStockPrice($symbol)
    {
        if (empty($symbol)) {
            return $this->jsonError('Stock symbol is required');
        }

        try {
            // Try to get from database first
            $latestPrice = $this->stockDataService->getLatestPrice($symbol);

            // If not in database, fetch from API
            if ($latestPrice === false) {
                $stockData = $this->stockDataService->fetchStockData($symbol);
                if ($stockData && isset($stockData['price'])) {
                    $latestPrice = $stockData['price'];
                    // Store it for future use
                    $this->stockDataService->storeStockPrice($symbol, $latestPrice);
                }
            }

            if ($latestPrice !== false) {
                return $this->jsonSuccess('Price fetched successfully', [
                    'symbol' => strtoupper($symbol),
                    'price' => (float)$latestPrice
                ]);
            } else {
                return $this->jsonError('Price not available for this stock');
            }
        } catch (\Exception $e) {
            return $this->jsonError('Error fetching stock price: ' . $e->getMessage());
        }
    }

    /**
     * Get stock details by symbol
     *
     * @param string $symbol
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStock($symbol)
    {
        if (empty($symbol)) {
            return $this->jsonError('Stock symbol is required');
        }

        try {
            $stock = Stock::where('symbol', strtoupper($symbol))
                ->where('active', true)
                ->first(['stock_id', 'symbol', 'company_name', 'sector', 'industry']);

            if ($stock) {
                return $this->jsonSuccess('Stock found', [
                    'id' => $stock->stock_id,
                    'symbol' => $stock->symbol,
                    'name' => $stock->company_name,
                    'sector' => $stock->sector,
                    'industry' => $stock->industry
                ]);
            } else {
                return $this->jsonError('Stock not found');
            }
        } catch (\Exception $e) {
            return $this->jsonError('Error fetching stock: ' . $e->getMessage());
        }
    }

    /**
     * List all available stocks
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function stocks()
    {
        try {
            $stocks = Stock::where('active', true)
                ->orderBy('symbol', 'asc')
                ->limit(100)
                ->get(['stock_id', 'symbol', 'company_name']);

            $formattedResults = [];
            foreach ($stocks as $stock) {
                $formattedResults[] = [
                    'id' => $stock->stock_id,
                    'symbol' => $stock->symbol,
                    'name' => $stock->company_name
                ];
            }

            return $this->jsonSuccess('Stocks retrieved successfully', $formattedResults);
        } catch (\Exception $e) {
            return $this->jsonError('Error fetching stocks: ' . $e->getMessage());
        }
    }
}