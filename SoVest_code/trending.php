<?php
/**
 * SoVest - Trending Predictions Page
 * 
 * This page displays trending stock predictions ranked by votes and accuracy.
 */

// Start session
session_start();

// Include auth functions and database
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/bootstrap/database.php';

// Use Eloquent models
use Database\Models\Prediction;
use Database\Models\User;
use Database\Models\Stock;
use Illuminate\Database\Capsule\Manager as DB;

// Require authentication
requireAuthentication();

// Get current user data
$user = getCurrentUser();
$userID = $user['id'];

// Include prediction score display component
require_once __DIR__ . '/includes/prediction_score_display.php';

// Include prediction scoring service
require_once __DIR__ . '/services/PredictionScoringService.php';

// Get trending predictions using Eloquent ORM
try {
    $trending_predictions = Prediction::select([
            'predictions.prediction_id',
            'users.id as user_id',
            DB::raw("CONCAT(users.first_name, ' ', users.last_name) as username"),
            'users.reputation_score',
            'stocks.symbol',
            'predictions.prediction_type as prediction',
            'predictions.accuracy',
            'predictions.target_price',
            'predictions.end_date',
            'predictions.is_active'
        ])
        ->join('users', 'predictions.user_id', '=', 'users.id')
        ->join('stocks', 'predictions.stock_id', '=', 'stocks.stock_id')
        ->leftJoin('prediction_votes', 'predictions.prediction_id', '=', 'prediction_votes.prediction_id')
        ->select([
            'predictions.prediction_id',
            'users.id as user_id',
            DB::raw("CONCAT(users.first_name, ' ', users.last_name) as username"),
            'users.reputation_score',
            'stocks.symbol',
            'predictions.prediction_type as prediction',
            'predictions.accuracy',
            'predictions.target_price',
            'predictions.end_date',
            'predictions.is_active',
            DB::raw('(SELECT COUNT(*) FROM prediction_votes WHERE prediction_id = predictions.prediction_id AND vote_type = "upvote") as votes')
        ])
        ->where(function($query) {
            $query->where('predictions.is_active', 1)
                  ->orWhere(function($query) {
                      $query->whereNotNull('predictions.accuracy')
                            ->where('predictions.accuracy', '>=', 70);
                  });
        })
        ->orderBy('votes', 'desc')
        ->orderBy('predictions.accuracy', 'desc')
        ->orderBy('predictions.prediction_date', 'desc')
        ->limit(15)
        ->get()
        ->toArray();

    // If no predictions found, use dummy data
    if (empty($trending_predictions)) {
        $trending_predictions = [
            ['username' => 'Investor123', 'symbol' => 'AAPL', 'prediction' => 'Bullish', 'votes' => 120, 'accuracy' => 92],
            ['username' => 'MarketGuru', 'symbol' => 'TSLA', 'prediction' => 'Bearish', 'votes' => 95, 'accuracy' => 85],
            ['username' => 'StockSavvy', 'symbol' => 'AMZN', 'prediction' => 'Bullish', 'votes' => 75, 'accuracy' => null],
        ];
    }
} catch (\Exception $e) {
    // Log error
    error_log("Error retrieving trending predictions: " . $e->getMessage());
    
    // Fallback to dummy data if an error occurs
    $trending_predictions = [
        ['username' => 'Investor123', 'symbol' => 'AAPL', 'prediction' => 'Bullish', 'votes' => 120, 'accuracy' => 92],
        ['username' => 'MarketGuru', 'symbol' => 'TSLA', 'prediction' => 'Bearish', 'votes' => 95, 'accuracy' => 85],
        ['username' => 'StockSavvy', 'symbol' => 'AMZN', 'prediction' => 'Bullish', 'votes' => 75, 'accuracy' => null],
    ];
}

// Page title and CSS
$pageTitle = 'Trending Predictions';
$pageCss = 'css/prediction.css';

// Include the header
require_once __DIR__ . '/includes/header.php';
?>

<div class="container trending-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Trending Predictions</h2>
        <div>
            <a href="leaderboard.php" class="btn btn-outline-light me-2">Leaderboard</a>
            <a href="create_prediction.php" class="btn btn-primary">Create New Prediction</a>
        </div>
    </div>
    
    <?php foreach ($trending_predictions as $post): ?>
        <div class="post-card">
            <div class="card-header border-0 bg-transparent">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">
                            <?php echo $post['symbol']; ?> - 
                            <span class="<?php echo $post['prediction'] == 'Bullish' ? 'text-success' : 'text-danger'; ?>">
                                <?php echo $post['prediction']; ?>
                            </span>
                        </h5>
                        
                        <?php if (isset($post['target_price'])): ?>
                            <small class="text-muted">Target: $<?php echo number_format($post['target_price'], 2); ?></small>
                        <?php endif; ?>
                    </div>
                    <?php echo renderPredictionBadge($post['accuracy']); ?>
                </div>
            </div>
            
            <div class="prediction-info mt-2">
                <div class="user-info">
                    <span>Posted by <strong><?php echo $post['username']; ?></strong></span>
                    <?php if (isset($post['reputation_score']) && $post['reputation_score'] > 0): ?>
                        <span class="badge bg-success reputation-badge">REP: <?php echo $post['reputation_score']; ?></span>
                    <?php endif; ?>
                </div>
                <div class="vote-section">
                    <button class="vote-btn" data-prediction-id="<?php echo $post['prediction_id'] ?? 0; ?>">&#9650;</button>
                    <span class="vote-count"><?php echo $post['votes']; ?></span>
                </div>
            </div>
            
            <div class="prediction-visual" data-accuracy="<?php echo $post['accuracy'] ?? 0; ?>"></div>
        </div>
    <?php endforeach; ?>
</div>

<?php
// Add page-specific scripts
$pageJs = 'js/scoring.js';

// Include the footer
require_once __DIR__ . '/includes/footer.php';
?>