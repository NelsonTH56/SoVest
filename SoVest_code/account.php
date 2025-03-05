<?php
/**
 * SoVest - User Account Page
 * 
 * This page displays the user's profile, statistics, and recent predictions.
 */

// Start session
session_start();

// Include required files
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db_config.php';
require_once __DIR__ . '/includes/prediction_score_display.php';
require_once __DIR__ . '/services/PredictionScoringService.php';
require_once __DIR__ . '/bootstrap/database.php';  // Include Eloquent bootstrap

// Import model classes
use Database\Models\Prediction;
use Exception;

// Require authentication
requireAuthentication();

// Get current user data
$userData = getCurrentUser();
$userID = $userData['id'];

// Get database connection
$conn = getDbConnection();

// Initialize scoring service to get user stats
$scoringService = new PredictionScoringService();
$userStats = $scoringService->getUserPredictionStats($userID);

// Get user's predictions using Eloquent ORM
try {
    // Get user predictions with related stock data
    $predictionModels = Prediction::with('stock')
        ->where('user_id', $userID)
        ->orderBy('prediction_date', 'DESC')
        ->limit(5)
        ->get();
    
    $predictions = [];
    
    if ($predictionModels->count() > 0) {
        foreach ($predictionModels as $prediction) {
            $row = [
                'prediction_id' => $prediction->prediction_id,
                'symbol' => $prediction->stock->symbol,
                'prediction' => $prediction->prediction_type,
                'accuracy' => $prediction->accuracy,
                'target_price' => $prediction->target_price,
                'end_date' => $prediction->end_date,
                'is_active' => $prediction->is_active
            ];
            
            // Keep the raw accuracy value for styling
            $row['raw_accuracy'] = $row['accuracy'];
            
            // Format accuracy as percentage if not null
            if ($row['accuracy'] !== null) {
                $row['accuracy'] = number_format($row['accuracy'], 0) . '%';
            } else {
                $row['accuracy'] = 'Pending';
            }
            
            $predictions[] = $row;
        }
    }
} catch (Exception $e) {
    // Error handling
    error_log('Error fetching predictions: ' . $e->getMessage());
    $predictions = [];
}

// Prepare user data for display
$user = [
    'username' => $userData['email'],
    'full_name' => ($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? ''),
    'bio' => $userData['major'] ? $userData['major'] . ' | ' . $userData['year'] : 'Stock enthusiast',
    'profile_picture' => 'images/logo.png',
    'reputation_score' => isset($userData['reputation_score']) ? $userData['reputation_score'] : 0,
    'avg_accuracy' => $userStats['avg_accuracy'],
    'predictions' => $predictions
];

// Page title
$pageTitle = 'My Account';
$pageCss = 'css/prediction.css';

// Include the header
require_once __DIR__ . '/includes/header.php';
?>

<div class="container profile-header">
    <img src="<?php echo $user['profile_picture']; ?>" class="profile-picture" alt="Profile Picture">
    <h2><?php echo $user['full_name']; ?></h2>
    <p class="bio">@<?php echo $user['username']; ?> | <?php echo $user['bio']; ?></p>
</div>

<div class="container reputation-section">
    <?php echo renderReputationScore($user['reputation_score'], $user['avg_accuracy']); ?>
    
    <div class="reputation-progress" data-reputation="<?php echo $user['reputation_score']; ?>" data-max-rep="50">
        <div class="progress-bar" style="width: <?php echo min(100, max(0, ($user['reputation_score'] / 50) * 100)); ?>%"></div>
    </div>
    
    <div class="row mt-3">
        <div class="col-4 text-center">
            <h5><?php echo $userStats['total']; ?></h5>
            <p class="small">Total Predictions</p>
        </div>
        <div class="col-4 text-center">
            <h5 class="text-success"><?php echo $userStats['accurate']; ?></h5>
            <p class="small">Accurate</p>
        </div>
        <div class="col-4 text-center">
            <h5 class="text-secondary"><?php echo $userStats['pending']; ?></h5>
            <p class="small">Pending</p>
        </div>
    </div>
</div>

<div class="container predictions-list">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>My Predictions</h3>
        <a href="create_prediction.php" class="btn btn-primary">Create New Prediction</a>
    </div>
    
    <div class="row">
        <?php foreach ($user['predictions'] as $prediction): ?>
            <div class="col-md-4">
                <div class="prediction-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5><?php echo $prediction['symbol']; ?></h5>
                        <?php echo renderPredictionBadge($prediction['raw_accuracy']); ?>
                    </div>
                    <p>
                        Prediction: 
                        <strong class="<?php echo $prediction['prediction'] == 'Bullish' ? 'text-success' : 'text-danger'; ?>">
                            <?php echo $prediction['prediction']; ?>
                        </strong>
                    </p>
                    <?php if (isset($prediction['target_price'])): ?>
                        <p>Target: $<?php echo number_format($prediction['target_price'], 2); ?></p>
                    <?php endif; ?>
                    
                    <div class="prediction-visual" data-accuracy="<?php echo $prediction['raw_accuracy'] ?? 0; ?>"></div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (empty($user['predictions'])): ?>
            <div class="col-12 text-center">
                <p>You haven't made any predictions yet.</p>
                <a href="create_prediction.php" class="btn btn-success mt-2">Make Your First Prediction</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Add page-specific scripts
$pageJs = 'js/scoring.js';

// Include the footer
require_once __DIR__ . '/includes/footer.php';
?>