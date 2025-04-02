<?php
    // Include Eloquent ORM initialization
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
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - SoVest</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
    <style>
        body { background-color: #2c2c2c; color: #d4d4d4; }
        .navbar { background-color: #1f1f1f; }
        .leaderboard-container { max-width: 800px; margin: 30px auto; }
        .leaderboard-table { background: #1f1f1f; border-radius: 10px; overflow: hidden; }
        .table { color: #d4d4d4; margin-bottom: 0; }
        .table th { border-top: none; }
        .highlight-row { background-color: rgba(40, 167, 69, 0.2); }
        .rank-badge { 
            display: inline-block;
            width: 30px;
            height: 30px;
            line-height: 30px;
            text-align: center;
            border-radius: 50%;
            background-color: #343a40;
        }
        .rank-1 { background-color: gold; color: #000; }
        .rank-2 { background-color: silver; color: #000; }
        .rank-3 { background-color: #cd7f32; color: #000; } /* Bronze */
        .user-card { background: #1f1f1f; border-radius: 10px; padding: 15px; margin-top: 20px; }
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
                    <li class="nav-item"><a class="nav-link" href="search.php">Search</a></li>
                    <li class="nav-item"><a class="nav-link" href="trending.php">Trending</a></li>
                    <?php if (isset($_SESSION['user_id']) || isset($_COOKIE["userID"])): ?>
                        <li class="nav-item"><a class="nav-link" href="my_predictions.php">My Predictions</a></li>
                        <li class="nav-item"><a class="nav-link active" href="leaderboard.php">Leaderboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="account.php">My Account</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

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