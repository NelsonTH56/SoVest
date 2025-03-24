<?php
    require_once 'bootstrap/database.php';
    require_once 'includes/db_config.php';
    
    // Import models
    use Database\Models\Prediction;
    use Database\Models\Stock;
    
    session_start();
    // Retrieve the userID cookie. If not set, redirect the user to the login page. If it is set, save it as $userID
    if(!isset($_COOKIE["userID"])){
        header("Location: login.php");
        exit();
    }
    else {
        $userID = $_COOKIE["userID"];
    }

    $predictions = [];
    
    try {
        // Get user's predictions using Eloquent ORM
        $predictions = Prediction::where('user_id', $userID)
            ->with('stock')  // Eager load stock data
            ->withCount(['votes as upvotes' => function($query) {
                $query->where('vote_type', 'upvote');
            }])
            ->orderBy('prediction_date', 'DESC')
            ->get();
    } catch (Exception $e) {
        // Handle any errors
        error_log("Error fetching predictions: " . $e->getMessage());
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Predictions - SoVest</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/prediction.css">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
    <style>
        body { background-color: #2c2c2c; color: #d4d4d4; }
        .navbar { background-color: #1f1f1f; }
        .predictions-container { max-width: 1000px; margin: auto; margin-top: 30px; }
        .prediction-card { background: #1f1f1f; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .prediction-header { display: flex; justify-content: space-between; align-items: center; }
        .bullish { color: #28a745; }
        .bearish { color: #dc3545; }
        .badge-inactive { background-color: #6c757d; }
        .prediction-actions { margin-top: 15px; }
        .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.75rem; }
        .empty-state { text-align: center; padding: 40px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">SoVest</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="search.php">Search</a></li>
                    <li class="nav-item"><a class="nav-link" href="trending.php">Trending</a></li>
                    <li class="nav-item"><a class="nav-link active" href="my_predictions.php">My Predictions</a></li>
                    <li class="nav-item"><a class="nav-link" href="account.php">My Account</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container predictions-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>My Predictions</h2>
            <a href="create_prediction.php" class="btn btn-primary">Create New Prediction</a>
        </div>
        
        <?php if ($predictions->isEmpty()): ?>
            <div class="empty-state prediction-card">
                <h4>No predictions yet</h4>
                <p>You haven't made any stock predictions yet. Create your first prediction to start building your reputation!</p>
                <a href="create_prediction.php" class="btn btn-primary mt-3">Create Your First Prediction</a>
            </div>
        <?php else: ?>
            <?php foreach ($predictions as $prediction): ?>
                <div class="prediction-card">
                    <div class="prediction-header">
                        <h4><?php echo htmlspecialchars($prediction->stock->symbol); ?> - <?php echo htmlspecialchars($prediction->stock->company_name); ?></h4>
                        <span class="badge <?php echo $prediction->prediction_type == 'Bullish' ? 'bg-success' : 'bg-danger'; ?>">
                            <?php echo htmlspecialchars($prediction->prediction_type); ?>
                        </span>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <p><strong>Created:</strong> <?php echo date('M j, Y', strtotime($prediction->prediction_date)); ?></p>
                            <p><strong>End Date:</strong> <?php echo date('M j, Y', strtotime($prediction->end_date)); ?></p>
                            <?php if ($prediction->target_price): ?>
                                <p><strong>Target Price:</strong> $<?php echo number_format($prediction->target_price, 2); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Status:</strong> 
                                <?php if (!$prediction->is_active): ?>
                                    <span class="badge badge-inactive">Inactive</span>
                                <?php elseif (strtotime($prediction->end_date) < time()): ?>
                                    <span class="badge bg-secondary">Expired</span>
                                <?php else: ?>
                                    <span class="badge bg-primary">Active</span>
                                <?php endif; ?>
                            </p>
                            <p><strong>Upvotes:</strong> <?php echo $prediction->upvotes; ?></p>
                            <?php if ($prediction->accuracy !== NULL): ?>
                                <p><strong>Accuracy:</strong> <?php echo $prediction->accuracy; ?>%</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <p><strong>Reasoning:</strong> <?php echo htmlspecialchars($prediction->reasoning); ?></p>
                    </div>
                    
                    <div class="prediction-actions">
                        <?php if ($prediction->is_active): ?>
                            <button class="btn btn-warning btn-sm edit-prediction" data-id="<?php echo $prediction->prediction_id; ?>">Edit</button>
                            <button class="btn btn-danger btn-sm delete-prediction" data-id="<?php echo $prediction->prediction_id; ?>">Delete</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Deletion</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this prediction? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/prediction/prediction.js"></script>
</body>
</html>