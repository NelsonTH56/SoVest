<?php
/**
 * SoVest Admin Dashboard
 * 
 * System status dashboard for monitoring application health and performance
 */

session_start();

// Include required files
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../services/StockDataService.php';
require_once __DIR__ . '/../services/PredictionScoringService.php';

// Import models and DB facade
use Database\Models\User;
use Database\Models\Stock;
use Database\Models\Prediction;
use Database\Models\StockPrice;
use Illuminate\Database\Capsule\Manager as DB;

// Admin authentication
$isAdmin = false;
$user = getCurrentUser();

if ($user && isset($user['is_admin']) && $user['is_admin'] == 1) {
    $isAdmin = true;
} else {
    // Redirect non-admin users
    header("Location: ../login.php");
    exit();
}

// Initialize services
$stockService = new StockDataService();
$scoringService = new PredictionScoringService();

// System statistics
$stats = [
    'total_users' => 0,
    'active_users' => 0,
    'total_predictions' => 0,
    'active_predictions' => 0,
    'evaluated_predictions' => 0,
    'total_stocks' => 0,
    'last_stock_update' => null,
    'last_prediction_evaluation' => null,
    'database_size' => 0,
    'log_size' => 0
];

// Query statistics using Eloquent
try {
    // Total users
    $stats['total_users'] = User::count();
    
    // Active users (last 30 days)
    $stats['active_users'] = Prediction::where('prediction_date', '>=', DB::raw('DATE_SUB(NOW(), INTERVAL 30 DAY)'))
        ->distinct('user_id')
        ->count('user_id');
    
    // Total predictions
    $stats['total_predictions'] = Prediction::count();
    
    // Active predictions
    $stats['active_predictions'] = Prediction::where('is_active', 1)->count();
    
    // Evaluated predictions
    $stats['evaluated_predictions'] = Prediction::where('is_active', 0)
        ->whereNotNull('accuracy')
        ->count();
    
    // Total stocks
    $stats['total_stocks'] = Stock::count();
    
    // Last stock update
    $lastStockUpdate = Stock::max('last_updated');
    $stats['last_stock_update'] = $lastStockUpdate;
    
    // Last price update
    $lastPriceUpdate = StockPrice::max('price_date');
    $stats['last_price_update'] = $lastPriceUpdate;
    
    // Get database size using Eloquent query builder
    try {
        $dbSizeResult = DB::table('information_schema.TABLES')
            ->where('table_schema', '=', DB::connection()->getDatabaseName())
            ->selectRaw('SUM(data_length + index_length) / 1024 / 1024 AS size_mb')
            ->first();
        
        if ($dbSizeResult && isset($dbSizeResult->size_mb)) {
            $stats['database_size'] = round($dbSizeResult->size_mb, 2);
        } else {
            $stats['database_size'] = 0;
        }
    } catch (Exception $e) {
        error_log("Error calculating database size: " . $e->getMessage());
        $stats['database_size'] = 0;
    }
} catch (Exception $e) {
    error_log("Error fetching dashboard statistics: " . $e->getMessage());
}

// Get log file size
$logPath = __DIR__ . '/../logs';
$stats['log_size'] = 0;
if (file_exists($logPath)) {
    $logFiles = glob($logPath . '/*.log');
    foreach ($logFiles as $file) {
        $stats['log_size'] += filesize($file);
    }
    $stats['log_size'] = round($stats['log_size'] / 1024 / 1024, 2);
}

// Get system errors from log
$errors = [];
$errorLogPath = __DIR__ . '/../logs/error.log';
if (file_exists($errorLogPath)) {
    $errorLines = file($errorLogPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $errors = array_slice($errorLines, -20); // Get last 20 errors
}

// Get PHP and server information
try {
    $connection = DB::connection();
    $mysqlVersion = $connection->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);
    
    $systemInfo = [
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'],
        'mysql_version' => $mysqlVersion,
        'operating_system' => PHP_OS,
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size')
    ];
} catch (Exception $e) {
    error_log("Error getting system info: " . $e->getMessage());
    $systemInfo = [
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'],
        'mysql_version' => 'Unknown',
        'operating_system' => PHP_OS,
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size')
    ];
}

// Check for system warnings
$warnings = [];

// Check for outdated stock data
if (isset($stats['last_stock_update']) && strtotime($stats['last_stock_update']) < strtotime('-24 hours')) {
    $warnings[] = 'Stock data has not been updated in the last 24 hours';
}

// Check for database size warning
if ($stats['database_size'] > 100) {
    $warnings[] = 'Database size is over 100MB, consider optimization';
}

// Check for log size warning
if ($stats['log_size'] > 50) {
    $warnings[] = 'Log files are over 50MB, consider cleanup';
}

// Check stock API status
$apiStatus = 'OK';
$apiStatusClass = 'success';
try {
    $testResult = $stockService->fetchStockData('AAPL');
    if ($testResult === false) {
        $apiStatus = 'Error fetching stock data';
        $apiStatusClass = 'danger';
    }
} catch (Exception $e) {
    $apiStatus = 'API Error: ' . $e->getMessage();
    $apiStatusClass = 'danger';
}

// Page title for header
$pageTitle = 'Admin Dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - SoVest</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" sizes="16x16" href="../images/favicon-16x16.png">
    <style>
        body { background-color: #2c2c2c; color: #d4d4d4; }
        .navbar { background-color: #1f1f1f; }
        .card { background-color: #1f1f1f; border: none; margin-bottom: 20px; }
        .stat-card { text-align: center; padding: 20px; }
        .stat-value { font-size: 2.5rem; font-weight: bold; }
        .stat-label { color: #b0b0b0; }
        .system-info { list-style: none; padding: 0; }
        .system-info li { padding: 8px 0; border-bottom: 1px solid #3c3c3c; }
        .warning-list { background-color: #332701; border-left: 4px solid #f0ad4e; }
        .error-log { background-color: #2d0000; max-height: 300px; overflow-y: auto; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">SoVest Admin</a>
            <img src="../images/logo.png" width="50px" alt="SoVest Logo">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_stocks.php">Manage Stocks</a></li>
                    <li class="nav-item"><a class="nav-link" href="../home.php">Main Site</a></li>
                    <li class="nav-item"><a class="nav-link" href="../logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>System Status Dashboard</h1>
        
        <div class="row">
            <div class="col-md-6">
                <h2 class="mt-4">Quick Actions</h2>
                <div class="card">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="run_stock_update.php" class="btn btn-success">Update Stock Prices</a>
                            <a href="run_prediction_eval.php" class="btn btn-warning">Evaluate Predictions</a>
                            <a href="run_maintenance.php" class="btn btn-info">Run Database Maintenance</a>
                            <a href="clear_logs.php" class="btn btn-danger">Clear Error Logs</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <h2 class="mt-4">System Status</h2>
                <div class="card">
                    <div class="card-body">
                        <table class="table table-dark">
                            <tr>
                                <td>Stock API Status:</td>
                                <td><span class="badge bg-<?php echo $apiStatusClass; ?>"><?php echo $apiStatus; ?></span></td>
                            </tr>
                            <tr>
                                <td>Database Status:</td>
                                <td><span class="badge bg-success">Connected</span></td>
                            </tr>
                            <tr>
                                <td>Last Stock Update:</td>
                                <td><?php echo $stats['last_stock_update'] ?? 'Never'; ?></td>
                            </tr>
                            <tr>
                                <td>Last Price Update:</td>
                                <td><?php echo $stats['last_price_update'] ?? 'Never'; ?></td>
                            </tr>
                            <tr>
                                <td>Database Size:</td>
                                <td><?php echo $stats['database_size']; ?> MB</td>
                            </tr>
                            <tr>
                                <td>Log Files Size:</td>
                                <td><?php echo $stats['log_size']; ?> MB</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <h2 class="mt-4">Application Statistics</h2>
        <div class="row">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="stat-value text-info"><?php echo $stats['total_users']; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="stat-value text-success"><?php echo $stats['active_users']; ?></div>
                    <div class="stat-label">Active Users (30d)</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="stat-value text-warning"><?php echo $stats['total_predictions']; ?></div>
                    <div class="stat-label">Total Predictions</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="stat-value text-primary"><?php echo $stats['total_stocks']; ?></div>
                    <div class="stat-label">Tracked Stocks</div>
                </div>
            </div>
        </div>
        
        <?php if (!empty($warnings)): ?>
        <div class="card mt-4 warning-list">
            <div class="card-body">
                <h4><i class="bi bi-exclamation-triangle-fill text-warning"></i> System Warnings</h4>
                <ul>
                    <?php foreach ($warnings as $warning): ?>
                    <li><?php echo $warning; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <h2>System Information</h2>
                <div class="card">
                    <div class="card-body">
                        <ul class="system-info">
                            <li><strong>PHP Version:</strong> <?php echo $systemInfo['php_version']; ?></li>
                            <li><strong>Web Server:</strong> <?php echo $systemInfo['server_software']; ?></li>
                            <li><strong>MySQL Version:</strong> <?php echo $systemInfo['mysql_version']; ?></li>
                            <li><strong>Operating System:</strong> <?php echo $systemInfo['operating_system']; ?></li>
                            <li><strong>Memory Limit:</strong> <?php echo $systemInfo['memory_limit']; ?></li>
                            <li><strong>Max Execution Time:</strong> <?php echo $systemInfo['max_execution_time']; ?>s</li>
                            <li><strong>Upload Max Filesize:</strong> <?php echo $systemInfo['upload_max_filesize']; ?></li>
                            <li><strong>Post Max Size:</strong> <?php echo $systemInfo['post_max_size']; ?></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <h2>Recent Error Logs</h2>
                <div class="card">
                    <div class="card-body error-log">
                        <?php if (empty($errors)): ?>
                            <p class="text-success">No errors in log file.</p>
                        <?php else: ?>
                            <pre><?php echo implode("\n", $errors); ?></pre>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</body>
</html>