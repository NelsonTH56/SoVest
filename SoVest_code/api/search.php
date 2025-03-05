<?php
/**
 * Search API Endpoint
 * 
 * This file provides API endpoints for real-time search suggestions and saving searches
 * Updated to use Eloquent ORM for database operations
 */

// Include Eloquent ORM initialization
require_once '../bootstrap/database.php';

// Include centralized database configuration for constants
require_once '../includes/db_config.php';

// Import Eloquent models
use Database\Models\User;
use Database\Models\Stock;
use Database\Models\Prediction;
use Database\Models\SearchHistory;
use Database\Models\SavedSearch;
use Illuminate\Database\Capsule\Manager as DB;

header('Content-Type: application/json');
session_start();

// Only allow authenticated requests
if (!isset($_COOKIE["userID"])) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

$userID = $_COOKIE["userID"];

// Handle different API actions
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

switch ($action) {
    case 'suggestions':
        // Get real-time search suggestions as user types
        $query = isset($_GET['query']) ? trim($_GET['query']) : '';
        $type = isset($_GET['type']) ? $_GET['type'] : 'all';
        
        if (empty($query) || strlen($query) < 2) {
            echo json_encode(['suggestions' => []]);
            exit;
        }
        
        getSuggestions($query, $type);
        break;
        
    case 'save_search':
        // Save a search to favorites
        $query = isset($_POST['query']) ? trim($_POST['query']) : '';
        $type = isset($_POST['type']) ? $_POST['type'] : 'all';
        
        if (empty($query)) {
            http_response_code(400);
            echo json_encode(['error' => 'Search query is required']);
            exit;
        }
        
        saveSearch($userID, $query, $type);
        break;
        
    case 'clear_history':
        // Clear user's search history
        clearSearchHistory($userID);
        break;
        
    case 'remove_saved':
        // Remove a saved search
        $searchId = isset($_POST['search_id']) ? (int)$_POST['search_id'] : 0;
        
        if ($searchId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid search ID']);
            exit;
        }
        
        removeSavedSearch($userID, $searchId);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        exit;
}

/**
 * Get search suggestions for autocomplete using Eloquent
 */
function getSuggestions($query, $type) {
    $suggestions = [];
    $searchParam = "%$query%";
    
    try {
        // Different suggestion queries based on search type
        switch ($type) {
            case 'stocks':
                $stocks = Stock::where('symbol', 'like', $searchParam)
                            ->orWhere('company_name', 'like', $searchParam)
                            ->limit(10)
                            ->get(['symbol', 'company_name']);
                
                foreach ($stocks as $stock) {
                    $suggestions[] = [
                        'text' => $stock->symbol . ' - ' . $stock->company_name,
                        'type' => 'stock'
                    ];
                }
                break;
                
            case 'users':
                $users = User::where('email', 'like', $searchParam)
                           ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$searchParam])
                           ->limit(10)
                           ->get(['email', 'first_name', 'last_name']);
                
                foreach ($users as $user) {
                    $name = $user->first_name . ' ' . $user->last_name;
                    $suggestions[] = [
                        'text' => $name . ' (' . $user->email . ')',
                        'type' => 'user'
                    ];
                }
                break;
                
            case 'predictions':
                $predictions = DB::table('predictions')
                                ->join('stocks', 'predictions.stock_id', '=', 'stocks.stock_id')
                                ->where('stocks.symbol', 'like', $searchParam)
                                ->limit(10)
                                ->select(['stocks.symbol', 'predictions.prediction_type'])
                                ->get();
                
                foreach ($predictions as $prediction) {
                    $suggestions[] = [
                        'text' => $prediction->symbol . ' - ' . $prediction->prediction_type,
                        'type' => 'prediction'
                    ];
                }
                break;
                
            default: // Combined results
                // Get stock symbols (limited to 5)
                $stocks = Stock::where('symbol', 'like', $searchParam)
                            ->orWhere('company_name', 'like', $searchParam)
                            ->limit(5)
                            ->get(['symbol', 'company_name']);
                
                foreach ($stocks as $stock) {
                    $suggestions[] = [
                        'text' => $stock->symbol . ' - ' . $stock->company_name,
                        'type' => 'stock'
                    ];
                }
                
                // Get usernames (limited to 3)
                $users = User::where('email', 'like', $searchParam)
                           ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$searchParam])
                           ->limit(3)
                           ->get(['email', 'first_name', 'last_name']);
                
                foreach ($users as $user) {
                    $name = $user->first_name . ' ' . $user->last_name;
                    $suggestions[] = [
                        'text' => $name . ' (' . $user->email . ')',
                        'type' => 'user'
                    ];
                }
                
                // Get prediction symbols (limited to 5)
                $predictions = DB::table('predictions')
                                ->join('stocks', 'predictions.stock_id', '=', 'stocks.stock_id')
                                ->where('stocks.symbol', 'like', $searchParam)
                                ->select('stocks.symbol')
                                ->distinct()
                                ->limit(5)
                                ->get();
                
                foreach ($predictions as $prediction) {
                    $suggestions[] = [
                        'text' => $prediction->symbol . ' predictions',
                        'type' => 'prediction'
                    ];
                }
        }
        
        echo json_encode(['suggestions' => $suggestions]);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to get suggestions: ' . $e->getMessage()]);
    }
}

/**
 * Save a search to user's favorites using Eloquent
 */
function saveSearch($userID, $query, $type) {
    try {
        // Check if this search is already saved
        $existingSearch = SavedSearch::where('user_id', $userID)
                                    ->where('search_query', $query)
                                    ->where('search_type', $type)
                                    ->first();
        
        if ($existingSearch) {
            // Search already saved
            echo json_encode(['success' => true, 'message' => 'Search already saved']);
            return;
        }
        
        // Save new search
        $savedSearch = new SavedSearch();
        $savedSearch->user_id = $userID;
        $savedSearch->search_query = $query;
        $savedSearch->search_type = $type;
        $savedSearch->save();
        
        echo json_encode([
            'success' => true,
            'message' => 'Search saved successfully',
            'search_id' => $savedSearch->id
        ]);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save search: ' . $e->getMessage()]);
    }
}

/**
 * Clear search history for a user using Eloquent
 */
function clearSearchHistory($userID) {
    try {
        SearchHistory::where('user_id', $userID)->delete();
        
        echo json_encode([
            'success' => true,
            'message' => 'Search history cleared'
        ]);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to clear search history: ' . $e->getMessage()]);
    }
}

/**
 * Remove a saved search using Eloquent
 */
function removeSavedSearch($userID, $searchId) {
    try {
        $deleted = SavedSearch::where('id', $searchId)
                             ->where('user_id', $userID)
                             ->delete();
        
        echo json_encode([
            'success' => true,
            'message' => 'Saved search removed'
        ]);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to remove saved search: ' . $e->getMessage()]);
    }
}
?>