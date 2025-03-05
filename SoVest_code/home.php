<?php
/**
 * SoVest - Home Page
 * 
 * This is the main home page for authenticated users.
 */

// Start session
session_start();

// Include auth functions
require_once __DIR__ . '/includes/auth.php';

// Require authentication
requireAuthentication();

// Get current user data
$user = getCurrentUser();
$userID = $user['id'];

// Page title
$pageTitle = 'Home';

// Include the header
require_once __DIR__ . '/includes/header.php';
?>

<div class="container text-center mt-5">
    <h1>Welcome to SoVest<?php echo isset($user['first_name']) ? ', ' . $user['first_name'] : ''; ?></h1>
    <p>Analyze, Predict, and Improve Your Market Insights</p>
    
    <div class="d-flex justify-content-center gap-3 mt-4">
        <a href="search.php" class="btn btn-primary">Search Stocks</a> 
        <a href="trending.php" class="btn btn-warning">Trending Predictions</a>
        <a href="create_prediction.php" class="btn btn-success">Create New Prediction</a>
    </div>
    
    <div class="row mt-5">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h4><i class="bi bi-graph-up"></i> Your Predictions</h4>
                </div>
                <div class="card-body">
                    <p>Track your prediction performance and see your accuracy rating.</p>
                    <a href="my_predictions.php" class="btn btn-outline-primary">View Your Predictions</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h4><i class="bi bi-trophy"></i> Leaderboard</h4>
                </div>
                <div class="card-body">
                    <p>See who has the highest REP score and learn from top predictors.</p>
                    <a href="leaderboard.php" class="btn btn-outline-warning">View Leaderboard</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h4><i class="bi bi-person-circle"></i> Your Profile</h4>
                </div>
                <div class="card-body">
                    <p>Manage your account settings and view your profile statistics.</p>
                    <a href="account.php" class="btn btn-outline-success">View Profile</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include the footer
require_once __DIR__ . '/includes/footer.php';
?>