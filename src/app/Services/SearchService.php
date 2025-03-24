<?php
/**
 * SoVest - Search Service
 *
 * This service provides search functionality for the application including
 * searching for stocks, users, and predictions, managing search history,
 * and providing search suggestions for autocomplete.
 * 
 * @package Services
 */

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Capsule\Manager as DB;
use App\Services\Interfaces\SearchServiceInterface;
use App\Models\SearchHistory;
use App\Models\SavedSearch;
use App\Models\Stock;
use App\Models\User;
use App\Models\Prediction;
use Exception;

class SearchService implements SearchServiceInterface
{
    /**
     * Perform a search based on the query and type
     * 
     * @param string $query The search query
     * @param string $type The type of search (stocks, users, predictions, all)
     * @param string $prediction Optional prediction type filter
     * @param string $sort Sort order (relevance, date_desc, accuracy, votes)
     * @param int $limit The maximum number of results to return
     * @param int $offset The offset for pagination
     * @return array The search results
     * @throws Exception If an error occurs during the search
     */
    public function performSearch($query, $type = 'stocks', $prediction = '', $sort = 'relevance', $limit = 10, $offset = 0)
    {
        try {
            // Save the search to history if user is authenticated
            if (Auth::check()) {
                $this->saveToHistory($query, $type);
            }
            
            $searchParam = "%{$query}%";
            $searchResults = [];
            
            switch ($type) {
                case 'stocks':
                    // Search stocks using Eloquent
                    $stocksQuery = Stock::where('symbol', 'LIKE', $searchParam)
                        ->orWhere('company_name', 'LIKE', $searchParam)
                        ->orWhere('sector', 'LIKE', $searchParam)
                        ->select('stock_id', 'symbol', 'company_name', 'sector')
                        ->limit($limit)
                        ->offset($offset)
                        ->get();
                    
                    // Format results
                    $searchResults = $stocksQuery->map(function($stock) {
                        $stockArray = $stock->toArray();
                        $stockArray['result_type'] = 'stock';
                        return $stockArray;
                    })->toArray();
                    break;
                    
                case 'users':
                    // Search users using Eloquent
                    $usersQuery = User::where('email', 'LIKE', $searchParam)
                        ->orWhereRaw('CONCAT(first_name, " ", last_name) LIKE ?', [$searchParam])
                        ->select('id', 'email', 'first_name', 'last_name', 'reputation_score')
                        ->limit($limit)
                        ->offset($offset)
                        ->get();
                    
                    // Format results
                    $searchResults = $usersQuery->map(function($user) {
                        $userArray = $user->toArray();
                        $userArray['result_type'] = 'user';
                        return $userArray;
                    })->toArray();
                    break;
                    
                case 'predictions':
                    // Search predictions using Eloquent
                    $predictionsQuery = Prediction::whereHas('stock', function($q) use ($query) {
                        $q->where('symbol', 'LIKE', "%{$query}%");
                    })
                    ->with(['user:id,first_name,last_name', 'stock:stock_id,symbol'])
                    ->select('prediction_id', 'stock_id', 'user_id', 'prediction_type', 'target_price', 'accuracy', 'is_active');
                    
                    // Add filter for prediction type if specified
                    if (!empty($prediction)) {
                        $predictionsQuery->where('prediction_type', $prediction);
                    }
                    
                    // Apply limits
                    $predictionsQuery->limit($limit)->offset($offset);
                    
                    // Get predictions
                    $predictions = $predictionsQuery->get();
                    
                    // Count upvotes for each prediction
                    $searchResults = $predictions->map(function($pred) {
                        $votes = DB::table('prediction_votes')
                            ->where('prediction_id', $pred->prediction_id)
                            ->where('vote_type', 'upvote')
                            ->count();
                        
                        return [
                            'prediction_id' => $pred->prediction_id,
                            'symbol' => $pred->stock->symbol,
                            'prediction_type' => $pred->prediction_type,
                            'target_price' => $pred->target_price,
                            'first_name' => $pred->user->first_name,
                            'last_name' => $pred->user->last_name,
                            'accuracy' => $pred->accuracy,
                            'is_active' => $pred->is_active,
                            'votes' => $votes,
                            'result_type' => 'prediction'
                        ];
                    })->toArray();
                    break;
                    
                default: // Search all
                    // For combined searches, we'll do separate smaller queries for each type
                    $results = [];
                    
                    // Search stocks (limited to 5)
                    $stocksQuery = Stock::where('symbol', 'LIKE', $searchParam)
                        ->orWhere('company_name', 'LIKE', $searchParam)
                        ->limit(5)
                        ->get(['stock_id', 'symbol', 'company_name', 'sector']);
                    
                    foreach($stocksQuery as $stock) {
                        $stockArray = $stock->toArray();
                        $stockArray['result_type'] = 'stock';
                        $results[] = $stockArray;
                    }
                    
                    // Search users (limited to 5)
                    $usersQuery = User::where('email', 'LIKE', $searchParam)
                        ->orWhereRaw('CONCAT(first_name, " ", last_name) LIKE ?', [$searchParam])
                        ->limit(5)
                        ->get(['id', 'email', 'first_name', 'last_name', 'reputation_score']);
                    
                    foreach($usersQuery as $user) {
                        $userArray = $user->toArray();
                        $userArray['result_type'] = 'user';
                        $results[] = $userArray;
                    }
                    
                    // Search predictions (limited to 5)
                    $predictionsQuery = Prediction::whereHas('stock', function($q) use ($query) {
                        $q->where('symbol', 'LIKE', "%{$query}%");
                    })
                    ->with(['user:id,first_name,last_name', 'stock:stock_id,symbol'])
                    ->select('prediction_id', 'stock_id', 'user_id', 'prediction_type', 'target_price', 'accuracy', 'is_active');
                    
                    // Add filter for prediction type if specified
                    if (!empty($prediction)) {
                        $predictionsQuery->where('prediction_type', $prediction);
                    }
                    
                    $predictionsQuery->limit(5);
                    $predictions = $predictionsQuery->get();
                    
                    foreach($predictions as $pred) {
                        $votes = DB::table('prediction_votes')
                            ->where('prediction_id', $pred->prediction_id)
                            ->where('vote_type', 'upvote')
                            ->count();
                        
                        $results[] = [
                            'prediction_id' => $pred->prediction_id,
                            'symbol' => $pred->stock->symbol,
                            'prediction_type' => $pred->prediction_type,
                            'target_price' => $pred->target_price,
                            'first_name' => $pred->user->first_name,
                            'last_name' => $pred->user->last_name,
                            'accuracy' => $pred->accuracy,
                            'is_active' => $pred->is_active,
                            'votes' => $votes,
                            'result_type' => 'prediction'
                        ];
                    }
                    
                    // Set search results
                    $searchResults = $results;
                    break;
            }
            
            // Apply sorting
            if ($sort == 'date_desc' && $type == 'predictions') {
                usort($searchResults, function($a, $b) {
                    return $b['prediction_id'] - $a['prediction_id']; // Assuming higher IDs are newer
                });
            } elseif ($sort == 'accuracy' && $type == 'predictions') {
                usort($searchResults, function($a, $b) {
                    if (is_null($a['accuracy'])) return 1;
                    if (is_null($b['accuracy'])) return -1;
                    return $b['accuracy'] - $a['accuracy'];
                });
            } elseif ($sort == 'votes' && $type == 'predictions') {
                usort($searchResults, function($a, $b) {
                    return $b['votes'] - $a['votes'];
                });
            }
            
            return $searchResults;
        } catch (Exception $e) {
            // Log the error
            error_log("Search error: " . $e->getMessage());
            throw new Exception("An error occurred while performing the search: " . $e->getMessage());
        }
    }
    
    /**
     * Get search suggestions for autocomplete based on the query and type
     * 
     * @param string $query The search query
     * @param string $type The type of search (stocks, users, predictions, combined)
     * @param int $limit The maximum number of suggestions to return
     * @return array The search suggestions
     * @throws Exception If an error occurs while getting suggestions
     */
    public function getSuggestions($query, $type = 'combined', $limit = 10)
    {
        try {
            // Return empty suggestions if query is too short
            if (empty($query) || strlen($query) < 2) {
                return [];
            }
            
            switch ($type) {
                case 'stocks':
                    return $this->getStockSuggestions($query, $limit);
                case 'users':
                    return $this->getUserSuggestions($query, $limit);
                case 'predictions':
                    return $this->getPredictionSuggestions($query, $limit);
                case 'combined':
                default:
                    return $this->getCombinedSuggestions($query, $limit);
            }
        } catch (Exception $e) {
            error_log("Suggestion error: " . $e->getMessage());
            throw new Exception("An error occurred while getting suggestions: " . $e->getMessage());
        }
    }
    
    /**
     * Get stock suggestions for autocomplete
     * 
     * @param string $query The search query
     * @param int $limit The maximum number of suggestions to return
     * @return array The stock suggestions
     */
    public function getStockSuggestions($query, $limit = 10)
    {
        try {
            $searchParam = "%{$query}%";
            $stocks = Stock::where('symbol', 'like', $searchParam)
                ->orWhere('company_name', 'like', $searchParam)
                ->limit($limit)
                ->get(['symbol', 'company_name']);
            
            $suggestions = [];
            foreach ($stocks as $stock) {
                $suggestions[] = [
                    'text' => $stock->symbol . ' - ' . $stock->company_name,
                    'type' => 'stock'
                ];
            }
            
            return $suggestions;
        } catch (Exception $e) {
            error_log("Stock suggestion error: " . $e->getMessage());
            throw new Exception("An error occurred while getting stock suggestions: " . $e->getMessage());
        }
    }
    
    /**
     * Get user suggestions for autocomplete
     * 
     * @param string $query The search query
     * @param int $limit The maximum number of suggestions to return
     * @return array The user suggestions
     */
    public function getUserSuggestions($query, $limit = 10)
    {
        try {
            $searchParam = "%{$query}%";
            $users = User::where('email', 'like', $searchParam)
                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$searchParam])
                ->limit($limit)
                ->get(['email', 'first_name', 'last_name']);
            
            $suggestions = [];
            foreach ($users as $user) {
                $name = $user->first_name . ' ' . $user->last_name;
                $suggestions[] = [
                    'text' => $name . ' (' . $user->email . ')',
                    'type' => 'user'
                ];
            }
            
            return $suggestions;
        } catch (Exception $e) {
            error_log("User suggestion error: " . $e->getMessage());
            throw new Exception("An error occurred while getting user suggestions: " . $e->getMessage());
        }
    }
    
    /**
     * Get prediction suggestions for autocomplete
     * 
     * @param string $query The search query
     * @param int $limit The maximum number of suggestions to return
     * @return array The prediction suggestions
     */
    public function getPredictionSuggestions($query, $limit = 10)
    {
        try {
            $searchParam = "%{$query}%";
            $predictions = DB::table('predictions')
                ->join('stocks', 'predictions.stock_id', '=', 'stocks.stock_id')
                ->where('stocks.symbol', 'like', $searchParam)
                ->limit($limit)
                ->select(['stocks.symbol', 'predictions.prediction_type'])
                ->get();
            
            $suggestions = [];
            foreach ($predictions as $prediction) {
                $suggestions[] = [
                    'text' => $prediction->symbol . ' - ' . $prediction->prediction_type,
                    'type' => 'prediction'
                ];
            }
            
            return $suggestions;
        } catch (Exception $e) {
            error_log("Prediction suggestion error: " . $e->getMessage());
            throw new Exception("An error occurred while getting prediction suggestions: " . $e->getMessage());
        }
    }
    
    /**
     * Get combined suggestions for autocomplete
     * 
     * @param string $query The search query
     * @param int $limit The maximum number of suggestions to return
     * @return array The combined suggestions
     */
    public function getCombinedSuggestions($query, $limit = 10)
    {
        try {
            $searchParam = "%{$query}%";
            $suggestions = [];
            
            // Divide limits based on priority
            $stockLimit = ceil($limit / 2);
            $userLimit = floor(($limit - $stockLimit) / 2);
            $predictionLimit = $limit - $stockLimit - $userLimit;
            
            // Get stock symbols
            $stocks = Stock::where('symbol', 'like', $searchParam)
                ->orWhere('company_name', 'like', $searchParam)
                ->limit($stockLimit)
                ->get(['symbol', 'company_name']);
            
            foreach ($stocks as $stock) {
                $suggestions[] = [
                    'text' => $stock->symbol . ' - ' . $stock->company_name,
                    'type' => 'stock'
                ];
            }
            
            // Get usernames
            $users = User::where('email', 'like', $searchParam)
                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$searchParam])
                ->limit($userLimit)
                ->get(['email', 'first_name', 'last_name']);
            
            foreach ($users as $user) {
                $name = $user->first_name . ' ' . $user->last_name;
                $suggestions[] = [
                    'text' => $name . ' (' . $user->email . ')',
                    'type' => 'user'
                ];
            }
            
            // Get prediction symbols
            $predictions = DB::table('predictions')
                ->join('stocks', 'predictions.stock_id', '=', 'stocks.stock_id')
                ->where('stocks.symbol', 'like', $searchParam)
                ->select('stocks.symbol')
                ->distinct()
                ->limit($predictionLimit)
                ->get();
            
            foreach ($predictions as $prediction) {
                $suggestions[] = [
                    'text' => $prediction->symbol . ' predictions',
                    'type' => 'prediction'
                ];
            }
            
            return $suggestions;
        } catch (Exception $e) {
            error_log("Combined suggestion error: " . $e->getMessage());
            throw new Exception("An error occurred while getting combined suggestions: " . $e->getMessage());
        }
    }
    
    /**
     * Save a search to the user's favorites
     * 
     * @param string $query The search query
     * @param string $type The type of search
     * @return bool True if the search was saved successfully
     * @throws Exception If the user is not authenticated or if an error occurs
     */
    public function saveSearch($query, $type = 'stocks')
    {
        try {
            if (!Auth::check()) {
                throw new Exception("User must be authenticated to save a search");
            }
            
            $userId = Auth::id();
            
            // Check if the search already exists
            $existingSearch = SavedSearch::where('user_id', $userId)
                ->where('search_query', $query)
                ->where('search_type', $type)
                ->first();
                
            if ($existingSearch) {
                return true; // Already saved
            }
            
            // Create a new saved search
            $savedSearch = new SavedSearch();
            $savedSearch->user_id = $userId;
            $savedSearch->search_query = $query;
            $savedSearch->search_type = $type;
            
            return $savedSearch->save();
        } catch (Exception $e) {
            error_log("Save search error: " . $e->getMessage());
            throw new Exception("An error occurred while saving the search: " . $e->getMessage());
        }
    }
    
    /**
     * Get the user's search history
     * 
     * @param int $limit The maximum number of results to return
     * @param int $offset The offset for pagination
     * @return array The search history
     * @throws Exception If the user is not authenticated or if an error occurs
     */
    public function getSearchHistory($limit = 20, $offset = 0)
    {
        try {
            if (!Auth::check()) {
                throw new Exception("User must be authenticated to get search history");
            }
            
            $userId = Auth::id();
            
            return SearchHistory::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->offset($offset)
                ->get()
                ->toArray();
        } catch (Exception $e) {
            error_log("Get search history error: " . $e->getMessage());
            throw new Exception("An error occurred while getting search history: " . $e->getMessage());
        }
    }
    
    /**
     * Clear the user's search history
     * 
     * @return bool True if the history was cleared successfully
     * @throws Exception If the user is not authenticated or if an error occurs
     */
    public function clearSearchHistory()
    {
        try {
            if (!Auth::check()) {
                throw new Exception("User must be authenticated to clear search history");
            }
            
            $userId = Auth::id();
            
            return SearchHistory::where('user_id', $userId)->delete() > 0;
        } catch (Exception $e) {
            error_log("Clear search history error: " . $e->getMessage());
            throw new Exception("An error occurred while clearing search history: " . $e->getMessage());
        }
    }
    
    /**
     * Remove a specific saved search
     * 
     * @param int $savedSearchId The ID of the saved search to remove
     * @return bool True if the search was removed successfully
     * @throws Exception If the user is not authenticated or if an error occurs
     */
    public function removeSavedSearch($savedSearchId)
    {
        try {
            if (!Auth::check()) {
                throw new Exception("User must be authenticated to remove a saved search");
            }
            
            $userId = Auth::id();
            
            $deleted = SavedSearch::where('id', $savedSearchId)
                ->where('user_id', $userId)
                ->delete();
                
            return $deleted > 0;
        } catch (Exception $e) {
            error_log("Remove saved search error: " . $e->getMessage());
            throw new Exception("An error occurred while removing the saved search: " . $e->getMessage());
        }
    }
    
    /**
     * Save a search to the user's history
     * 
     * @param string $query The search query
     * @param string $type The type of search
     * @return bool True if the search was saved to history successfully
     */
    public function saveToHistory($query, $type = 'stocks')
    {
        try {
            if (!Auth::check()) {
                return false; // Silently fail if not authenticated
            }
            
            $userId = Auth::id();
            
            // Create a new search history entry
            $searchHistory = new SearchHistory();
            $searchHistory->user_id = $userId;
            $searchHistory->search_query = $query;
            $searchHistory->search_type = $type;
            
            return $searchHistory->save();
        } catch (Exception $e) {
            error_log("Save to history error: " . $e->getMessage());
            return false; // Silently fail if an error occurs
        }
    }
}