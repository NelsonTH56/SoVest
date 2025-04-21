@extends('layouts.app')

<?php /*
     Include Eloquent ORM initialization
    require_once 'bootstrap/database.php';
    require_once 'includes/db_config.php';

    // Import User model
    use Database\Models\User;

    session_start();
    // Retrieve the userID cookie. If not set, redirect the user to the login page. If it is set, save it as $userID
    if(!isset($_COOKIE["userID"])){header("Location: login.php");}
    else {$userID = $_COOKIE["userID"];}

    // Include the PredictionScoringService
    require_once __DIR__ . '/services/PredictionScoringService.php';
    require_once __DIR__ . '/includes/prediction_score_display.php';

    // Create service instance
    $scoringService = new PredictionScoringService();

    try {
        // Get top users by reputation
        $topUsers = $scoringService->getTopUsers(20);

        // Get current user's rank
        $userRank = 0;
        foreach ($topUsers as $index => $user) {
            if ($user['id'] == $userID) {
                $userRank = $index + 1;
                break;
            }
        }

        // If user not in top 20, get their stats separately
        $userStats = null;
        $userInfo = null;
        if ($userRank == 0) {
            $userStats = $scoringService->getUserPredictionStats($userID);
            
            // Get user info using Eloquent instead of direct SQL
            try {
                $userModel = User::find($userID);
                if ($userModel) {
                    $userInfo = [
                        'id' => $userModel->id,
                        'first_name' => $userModel->first_name,
                        'last_name' => $userModel->last_name,
                        'email' => $userModel->email,
                        'reputation_score' => $userModel->reputation_score
                    ];
                    $userInfo['avg_accuracy'] = $userStats['avg_accuracy'];
                    $userInfo['prediction_count'] = $userStats['total'];
                }
            } catch (Exception $e) {
                // Handle error if needed
            }
        }
    } catch (Exception $e) {
        // Handle errors
    } */
?> 
@section('content')

    <div class="container leaderboard-container">
        <h2 class="mb-4 text-center">Top Predictors Leaderboard</h2>
        
        <div class="leaderboard-table">
            <table class="table table-striped table-dark">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>User</th>
                        <th class="text-center">REP Score</th>
                        <th class="text-center">Accuracy</th>
                        <th class="text-center">Predictions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topUsers as $index => $user): ?>
                        <?php $isCurrentUser = ($user['id'] == $userID); ?>
                        <tr class="<?php echo $isCurrentUser ? 'highlight-row' : ''; ?>">
                            <td>
                                <span class="rank-badge rank-<?php echo $index + 1; ?>">
                                    <?php echo $index + 1; ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                    $displayName = $user['first_name'] . ' ' . $user['last_name'];
                                    echo htmlspecialchars($displayName);
                                    if ($isCurrentUser) echo ' <i class="bi bi-person-check-fill text-success"></i>';
                                ?>
                            </td>
                            <td class="text-center">
                                <span class="<?php echo $user['reputation_score'] >= 20 ? 'text-success' : 
                                            ($user['reputation_score'] >= 10 ? 'text-info' : 
                                            ($user['reputation_score'] >= 0 ? 'text-warning' : 'text-danger')); ?>">
                                    <?php echo $user['reputation_score']; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="<?php echo getAccuracyClass($user['avg_accuracy']); ?>">
                                    <?php echo formatAccuracy($user['avg_accuracy']); ?>
                                </span>
                            </td>
                            <td class="text-center"><?php echo $user['prediction_count']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($userRank == 0 && isset($userInfo)): ?>
        <div class="user-card">
            <h4>Your Ranking</h4>
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p>You're not in the top 20 yet. Keep making accurate predictions to climb the leaderboard!</p>
                    <p>
                        <strong>Your REP Score:</strong> 
                        <span class="<?php echo $userInfo['reputation_score'] >= 20 ? 'text-success' : 
                                    ($userInfo['reputation_score'] >= 10 ? 'text-info' : 
                                    ($userInfo['reputation_score'] >= 0 ? 'text-warning' : 'text-danger')); ?>">
                            <?php echo $userInfo['reputation_score']; ?>
                        </span>
                    </p>
                    <p>
                        <strong>Average Accuracy:</strong> 
                        <span class="<?php echo getAccuracyClass($userInfo['avg_accuracy']); ?>">
                            <?php echo formatAccuracy($userInfo['avg_accuracy']); ?>
                        </span>
                    </p>
                </div>
                <div class="text-center">
                    <div class="mb-2">
                        <a href="create_prediction.php" class="btn btn-primary">Make Prediction</a>
                    </div>
                    <div>
                        <a href="my_predictions.php" class="btn btn-outline-light">My Predictions</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>