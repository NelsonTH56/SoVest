<?php
    session_start();
    // Redirect to login if not authenticated
    if(!isset($_COOKIE["userID"])){
        header("Location: login.php");
        exit;
    }
    else {
        $userID = $_COOKIE["userID"];
    }

    // Include Eloquent setup
    require_once('bootstrap/database.php');
    
    // Import the models
    use Database\Models\User;
    use Database\Models\Stock;
    use Database\Models\Prediction;
    use Database\Models\SearchHistory;
    use Database\Models\SavedSearch;
    use Illuminate\Database\Capsule\Manager as DB;
    
    // Get search parameters
    $query = isset($_GET['query']) ? trim($_GET['query']) : '';
    $type = isset($_GET['type']) ? $_GET['type'] : 'all';
    $prediction = isset($_GET['prediction']) ? $_GET['prediction'] : '';
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'relevance';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    // Load user's saved searches using Eloquent
    $savedSearches = [];
    if ($userID) {
        try {
            $savedSearches = SavedSearch::where('user_id', $userID)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(['search_query', 'search_type', 'created_at', 'id'])
                ->toArray();
        } catch (\Exception $e) {
            // Handle any errors
            error_log("Error loading saved searches: " . $e->getMessage());
        }
    }
    
    // Perform search if query is provided
    $searchResults = [];
    $totalResults = 0;
    
    if (!empty($query)) {
        try {
            // Build search based on type
            switch ($type) {
                case 'stocks':
                    // Search stocks using Eloquent
                    $searchParam = "%{$query}%";
                    $stocksQuery = Stock::where('symbol', 'LIKE', $searchParam)
                        ->orWhere('company_name', 'LIKE', $searchParam)
                        ->select('stock_id', 'symbol', 'company_name', 'sector')
                        ->get();
                    
                    // Format results
                    $searchResults = $stocksQuery->map(function($stock) {
                        $stockArray = $stock->toArray();
                        $stockArray['result_type'] = 'stock';
                        return $stockArray;
                    })->toArray();
                    
                    $totalResults = count($searchResults);
                    break;
                    
                case 'users':
                    // Search users using Eloquent
                    $searchParam = "%{$query}%";
                    $usersQuery = User::where('email', 'LIKE', $searchParam)
                        ->orWhereRaw('CONCAT(first_name, " ", last_name) LIKE ?', [$searchParam])
                        ->select('id', 'email', 'first_name', 'last_name', 'reputation_score')
                        ->get();
                    
                    // Format results
                    $searchResults = $usersQuery->map(function($user) {
                        $userArray = $user->toArray();
                        $userArray['result_type'] = 'user';
                        return $userArray;
                    })->toArray();
                    
                    $totalResults = count($searchResults);
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
                    
                    $totalResults = count($searchResults);
                    break;
                    
                default: // Search all
                    // For combined searches, we'll do separate smaller queries for each type
                    $results = [];
                    
                    // Search stocks (limited to 5)
                    $searchParam = "%{$query}%";
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
                    
                    // Assign to search results
                    $searchResults = $results;
                    $totalResults = count($results);
                    break;
            }
            
            // Save search to history using Eloquent
            if (!empty($query) && $userID) {
                SearchHistory::create([
                    'user_id' => $userID,
                    'search_query' => $query,
                    'search_type' => $type
                ]);
            }
        } catch (\Exception $e) {
            // Handle any errors
            error_log("Search error: " . $e->getMessage());
        }
    }
    
    // Get user's search history using Eloquent
    $searchHistory = [];
    if ($userID) {
        try {
            $searchHistory = SearchHistory::where('user_id', $userID)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(['search_query', 'search_type', 'created_at'])
                ->toArray();
        } catch (\Exception $e) {
            // Handle any errors
            error_log("Error loading search history: " . $e->getMessage());
        }
    }
    
    // Load common header
    $pageTitle = "Search";
    include_once('includes/search_bar.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search - SoVest</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/search.css">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
    <style>
        body { background-color: #2c2c2c; color: #d4d4d4; }
        .navbar { background-color: #1f1f1f; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">SoVest</a>
            <img src="./images/logo.png" width="50px">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="search.php">Search</a></li>
                    <li class="nav-item"><a class="nav-link" href="trending.php">Trending</a></li>
                    <?php if (isset($_SESSION['user_id']) || isset($_COOKIE["userID"])): ?>
                        <li class="nav-item"><a class="nav-link" href="my_predictions.php">My Predictions</a></li>
                        <li class="nav-item"><a class="nav-link" href="leaderboard.php">Leaderboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="account.php">My Account</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container search-container mt-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <h2 class="text-center mb-4">Search SoVest</h2>
                
                <!-- Main Search Form -->
                <form action="search.php" method="GET" class="mb-4">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control form-control-lg" 
                               name="query" placeholder="Search for stocks, users, or predictions..." 
                               value="<?php echo htmlspecialchars($query); ?>"
                               id="searchInput" autocomplete="off">
                        <button class="btn btn-success" type="submit">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                    
                    <!-- Search suggestions container -->
                    <div id="searchSuggestions" class="search-suggestions"></div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <select name="type" class="form-select">
                                <option value="all" <?php echo $type == 'all' ? 'selected' : ''; ?>>All Types</option>
                                <option value="stocks" <?php echo $type == 'stocks' ? 'selected' : ''; ?>>Stocks</option>
                                <option value="predictions" <?php echo $type == 'predictions' ? 'selected' : ''; ?>>Predictions</option>
                                <option value="users" <?php echo $type == 'users' ? 'selected' : ''; ?>>Users</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-2">
                            <select name="prediction" class="form-select">
                                <option value="">Any Prediction</option>
                                <option value="Bullish" <?php echo $prediction == 'Bullish' ? 'selected' : ''; ?>>Bullish</option>
                                <option value="Bearish" <?php echo $prediction == 'Bearish' ? 'selected' : ''; ?>>Bearish</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-2">
                            <select name="sort" class="form-select">
                                <option value="relevance" <?php echo $sort == 'relevance' ? 'selected' : ''; ?>>Relevance</option>
                                <option value="date_desc" <?php echo $sort == 'date_desc' ? 'selected' : ''; ?>>Latest</option>
                                <option value="accuracy" <?php echo $sort == 'accuracy' ? 'selected' : ''; ?>>Highest Accuracy</option>
                                <option value="votes" <?php echo $sort == 'votes' ? 'selected' : ''; ?>>Most Votes</option>
                            </select>
                        </div>
                    </div>
                </form>
                
                <?php if (!empty($query) && empty($searchResults)): ?>
                    <div class="alert alert-info">
                        No results found for "<?php echo htmlspecialchars($query); ?>". Try adjusting your search.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row">
            <!-- Search Results -->
            <div class="col-md-8">
                <?php if (!empty($searchResults)): ?>
                    <div class="search-results-header">
                        <h3>Search Results</h3>
                        <p><?php echo $totalResults; ?> result(s) for "<?php echo htmlspecialchars($query); ?>"</p>
                    </div>
                    
                    <div class="search-results">
                        <?php foreach($searchResults as $result): ?>
                            <div class="search-result-card">
                                <?php if($result['result_type'] == 'stock'): ?>
                                    <div class="d-flex align-items-center">
                                        <div class="stock-icon">
                                            <i class="bi bi-graph-up-arrow"></i>
                                        </div>
                                        <div class="result-content">
                                            <h4><?php echo htmlspecialchars($result['symbol']); ?></h4>
                                            <p><?php echo htmlspecialchars($result['company_name']); ?></p>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($result['sector']); ?></span>
                                        </div>
                                    </div>
                                    
                                <?php elseif($result['result_type'] == 'user'): ?>
                                    <div class="d-flex align-items-center">
                                        <div class="user-icon">
                                            <i class="bi bi-person-circle"></i>
                                        </div>
                                        <div class="result-content">
                                            <h4><?php echo htmlspecialchars($result['first_name'] . ' ' . $result['last_name']); ?></h4>
                                            <p><?php echo htmlspecialchars($result['email']); ?></p>
                                            <?php if(isset($result['reputation_score'])): ?>
                                                <span class="badge bg-success">REP: <?php echo $result['reputation_score']; ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                <?php elseif($result['result_type'] == 'prediction'): ?>
                                    <div class="d-flex align-items-center">
                                        <div class="prediction-icon">
                                            <i class="bi bi-lightning-charge"></i>
                                        </div>
                                        <div class="result-content">
                                            <h4><?php echo htmlspecialchars($result['symbol']); ?> - 
                                                <span class="<?php echo $result['prediction_type'] == 'Bullish' ? 'text-success' : 'text-danger'; ?>">
                                                    <?php echo htmlspecialchars($result['prediction_type']); ?>
                                                </span>
                                            </h4>
                                            <p>By <?php echo htmlspecialchars($result['first_name'] . ' ' . $result['last_name']); ?></p>
                                            <div class="d-flex align-items-center mt-2">
                                                <?php if(isset($result['accuracy'])): ?>
                                                    <span class="badge me-2 <?php echo $result['accuracy'] >= 70 ? 'bg-success' : 'bg-warning'; ?>">
                                                        Accuracy: <?php echo $result['accuracy']; ?>%
                                                    </span>
                                                <?php endif; ?>
                                                <span class="badge bg-info me-2">
                                                    <i class="bi bi-arrow-up"></i> <?php echo $result['votes'] ?? 0; ?>
                                                </span>
                                                <?php if(isset($result['target_price'])): ?>
                                                    <span class="badge bg-secondary">
                                                        Target: $<?php echo number_format($result['target_price'], 2); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if($totalResults > $limit): ?>
                        <!-- Pagination -->
                        <nav aria-label="Search results pagination">
                            <ul class="pagination justify-content-center">
                                <?php 
                                    $totalPages = ceil($totalResults / $limit);
                                    for($i = 1; $i <= $totalPages; $i++):
                                ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?query=<?php echo urlencode($query); ?>&type=<?php echo $type; ?>&prediction=<?php echo $prediction; ?>&sort=<?php echo $sort; ?>&page=<?php echo $i; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php elseif(empty($query)): ?>
                    <div class="empty-search-message text-center py-5">
                        <i class="bi bi-search" style="font-size: 3rem;"></i>
                        <h3 class="mt-3">Start your search above</h3>
                        <p>Search for stocks, predictions, or other users</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Search History & Saved Searches Sidebar -->
            <div class="col-md-4">
                <?php if (!empty($searchHistory)): ?>
                    <div class="card bg-dark mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Search History</h5>
                            <button class="btn btn-sm btn-outline-secondary" id="clearHistory">Clear</button>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush bg-transparent">
                                <?php foreach($searchHistory as $history): ?>
                                    <li class="list-group-item bg-transparent border-light">
                                        <a href="?query=<?php echo urlencode($history['search_query']); ?>&type=<?php echo $history['search_type']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($history['search_query']); ?>
                                            <small class="text-muted d-block">
                                                <?php echo ucfirst($history['search_type']); ?> • 
                                                <?php echo date("M j, g:i a", strtotime($history['created_at'])); ?>
                                            </small>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($savedSearches)): ?>
                    <div class="card bg-dark">
                        <div class="card-header">
                            <h5 class="mb-0">Saved Searches</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush bg-transparent">
                                <?php foreach($savedSearches as $saved): ?>
                                    <li class="list-group-item bg-transparent border-light d-flex justify-content-between align-items-center">
                                        <a href="?query=<?php echo urlencode($saved['search_query']); ?>&type=<?php echo $saved['search_type']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($saved['search_query']); ?>
                                            <small class="text-muted d-block">
                                                <?php echo ucfirst($saved['search_type']); ?>
                                            </small>
                                        </a>
                                        <button class="btn btn-sm btn-danger remove-saved" data-id="<?php echo $saved['id']; ?>">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if(!empty($query)): ?>
                    <div class="mt-4 text-center">
                        <button id="saveSearch" class="btn btn-outline-success" data-query="<?php echo htmlspecialchars($query); ?>" data-type="<?php echo $type; ?>">
                            <i class="bi bi-bookmark-plus"></i> Save This Search
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/search.js"></script>
</body>
</html>