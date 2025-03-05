<?php
    require_once 'includes/db_config.php';
    require_once __DIR__ . '/bootstrap/database.php';

    use Database\Models\Prediction;
    use Database\Models\Stock;
    use Services\DatabaseService;

    session_start();
    // Retrieve the userID cookie. If not set, redirect the user to the login page. If it is set, save it as $userID
    if(!isset($_COOKIE["userID"])){
        header("Location: login.php");
        exit();
    }
    else {
        $userID = $_COOKIE["userID"];
    }

    // Include the StockDataService
    require_once __DIR__ . '/services/StockDataService.php';
    $stockService = new StockDataService();

    // Get all active stocks for the dropdown
    $stocks = $stockService->getStocks(true);
    
    // Check if we're editing an existing prediction
    $isEditing = false;
    $prediction = null;
    
    if (isset($_GET['edit']) && !empty($_GET['edit'])) {
        $prediction_id = $_GET['edit'];
        
        try {
            // Fetch the prediction with its related stock using Eloquent
            $predictionModel = Prediction::with('stock')
                ->where('prediction_id', $prediction_id)
                ->where('user_id', $userID)
                ->first();
            
            if ($predictionModel) {
                // Convert to array format to maintain compatibility with the view
                $prediction = $predictionModel->toArray();
                // Add stock attributes that were previously fetched by JOIN
                $prediction['symbol'] = $predictionModel->stock->symbol;
                $prediction['company_name'] = $predictionModel->stock->company_name;
                $isEditing = true;
            }
        } catch (Exception $e) {
            error_log("Error fetching prediction: " . $e->getMessage());
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Prediction - SoVest</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/prediction.css">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
    <style>
        body { background-color: #2c2c2c; color: #d4d4d4; }
        .navbar { background-color: #1f1f1f; }
        .form-container { max-width: 800px; margin: auto; margin-top: 30px; }
        .prediction-form { background: #1f1f1f; padding: 25px; border-radius: 10px; margin-bottom: 30px; }
        .form-control, .form-select { background-color: #333; color: #d4d4d4; border-color: #444; }
        .form-control:focus, .form-select:focus { background-color: #333; color: #d4d4d4; }
        .btn-primary { background-color: #28a745; border-color: #28a745; }
        .btn-primary:hover { background-color: #218838; border-color: #1e7e34; }
        .datepicker { background-color: #333 !important; color: #d4d4d4 !important; }
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
                    <li class="nav-item"><a class="nav-link" href="my_predictions.php">My Predictions</a></li>
                    <li class="nav-item"><a class="nav-link" href="account.php">My Account</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container form-container">
        <h2 class="text-center mb-4"><?php echo $isEditing ? 'Edit' : 'Create New'; ?> Stock Prediction</h2>
        
        <div class="prediction-form">
            <form id="prediction-form" action="api/prediction_operations.php" method="post">
                <input type="hidden" name="action" value="<?php echo $isEditing ? 'update' : 'create'; ?>">
                <?php if ($isEditing): ?>
                    <input type="hidden" name="prediction_id" value="<?php echo $prediction['prediction_id']; ?>">
                <?php endif; ?>
                
                <div class="mb-3">
                    <label for="stock-search" class="form-label">Stock Symbol</label>
                    <?php if ($isEditing): ?>
                        <input type="text" class="form-control" id="stock-search" value="<?php echo htmlspecialchars($prediction['symbol'] . ' - ' . $prediction['company_name']); ?>" <?php echo $isEditing ? 'readonly' : ''; ?>>
                        <input type="hidden" id="stock_id" name="stock_id" value="<?php echo $prediction['stock_id']; ?>" required>
                    <?php else: ?>
                        <input type="text" class="form-control" id="stock-search" placeholder="Search for a stock symbol or name...">
                        <div id="stock-suggestions" class="mt-2"></div>
                        <input type="hidden" id="stock_id" name="stock_id" required>
                    <?php endif; ?>
                </div>
                
                <div class="mb-3">
                    <label for="prediction_type" class="form-label">Prediction Type</label>
                    <select class="form-select" id="prediction_type" name="prediction_type" required>
                        <option value="" <?php echo !$isEditing ? 'selected' : ''; ?> disabled>Select prediction type</option>
                        <option value="Bullish" <?php echo $isEditing && $prediction['prediction_type'] == 'Bullish' ? 'selected' : ''; ?>>Bullish (Stock will rise)</option>
                        <option value="Bearish" <?php echo $isEditing && $prediction['prediction_type'] == 'Bearish' ? 'selected' : ''; ?>>Bearish (Stock will fall)</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="target_price" class="form-label">Target Price (optional)</label>
                    <input type="number" class="form-control" id="target_price" name="target_price" step="0.01" min="0" 
                           value="<?php echo $isEditing && $prediction['target_price'] ? htmlspecialchars($prediction['target_price']) : ''; ?>">
                    <small class="form-text text-muted">Your predicted price target for this stock</small>
                </div>
                
                <div class="mb-3">
                    <label for="end_date" class="form-label">Timeframe (End Date)</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" required
                           value="<?php echo $isEditing ? date('Y-m-d', strtotime($prediction['end_date'])) : ''; ?>">
                    <small class="form-text text-muted">When do you expect your prediction to be fulfilled?</small>
                </div>
                
                <div class="mb-3">
                    <label for="reasoning" class="form-label">Reasoning</label>
                    <textarea class="form-control" id="reasoning" name="reasoning" rows="4" required><?php echo $isEditing ? htmlspecialchars($prediction['reasoning']) : ''; ?></textarea>
                    <small class="form-text text-muted">Explain why you believe this prediction will come true</small>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $isEditing ? 'Update' : 'Create'; ?> Prediction
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/prediction/prediction.js"></script>
</body>
</html>