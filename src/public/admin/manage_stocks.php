<?php
/**
 * SoVest Stock Management Interface
 * 
 * Admin interface to manage tracked stocks
 */
session_start();

// Include the StockDataService
require_once __DIR__ . '/../services/StockDataService.php';

// Check if user is logged in and is admin
// TODO: Implement proper admin check
$isAdmin = isset($_COOKIE['userID']); // Temporary: just check if logged in

if (!$isAdmin) {
    header("Location: ../login.php");
    exit;
}

// Initialize stock service
$stockService = new StockDataService();

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new stock
    if (isset($_POST['add_stock'])) {
        $symbol = trim($_POST['symbol']);
        $name = trim($_POST['name']);
        $description = trim($_POST['description'] ?? '');
        $sector = trim($_POST['sector'] ?? '');
        
        if (empty($symbol) || empty($name)) {
            $error = "Symbol and Name are required";
        } else {
            if ($stockService->addStock($symbol, $name, $description, $sector)) {
                $message = "Stock added successfully";
            } else {
                $error = "Failed to add stock";
            }
        }
    }
    
    // Remove stock
    if (isset($_POST['remove_stock'])) {
        $symbol = trim($_POST['symbol']);
        
        if (empty($symbol)) {
            $error = "Symbol is required";
        } else {
            if ($stockService->removeStock($symbol)) {
                $message = "Stock removed successfully";
            } else {
                $error = "Failed to remove stock";
            }
        }
    }
    
    // Update all stocks
    if (isset($_POST['update_all'])) {
        $results = $stockService->updateAllStocks();
        $successCount = count(array_filter($results));
        $totalCount = count($results);
        $message = "Stock update completed: $successCount/$totalCount stocks updated successfully";
    }
    
    // Initialize default stocks
    if (isset($_POST['init_defaults'])) {
        $results = $stockService->initializeDefaultStocks();
        $successCount = count(array_filter($results));
        $totalCount = count($results);
        $message = "Default stocks initialized: $successCount/$totalCount added successfully";
    }
}

// Get all stocks
$stocks = $stockService->getStocks(false);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SoVest - Manage Stocks</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h1 class="my-4">Manage Stocks</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">Add New Stock</div>
            <div class="card-body">
                <form method="post">
                    <div class="form-group">
                        <label for="symbol">Stock Symbol:</label>
                        <input type="text" class="form-control" id="symbol" name="symbol" required>
                    </div>
                    <div class="form-group">
                        <label for="name">Company Name:</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea class="form-control" id="description" name="description"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="sector">Sector:</label>
                        <input type="text" class="form-control" id="sector" name="sector">
                    </div>
                    <button type="submit" name="add_stock" class="btn btn-primary">Add Stock</button>
                </form>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">Manage Tracked Stocks</div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Symbol</th>
                            <th>Name</th>
                            <th>Sector</th>
                            <th>Last Updated</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stocks as $stock): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($stock['symbol']); ?></td>
                                <td><?php echo htmlspecialchars($stock['name']); ?></td>
                                <td><?php echo htmlspecialchars($stock['sector']); ?></td>
                                <td><?php echo $stock['last_updated']; ?></td>
                                <td>
                                    <?php if ($stock['active']): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="symbol" value="<?php echo htmlspecialchars($stock['symbol']); ?>">
                                        <?php if ($stock['active']): ?>
                                            <button type="submit" name="remove_stock" class="btn btn-sm btn-danger">Deactivate</button>
                                        <?php else: ?>
                                            <button type="submit" name="add_stock" class="btn btn-sm btn-success">Activate</button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($stocks)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No stocks found. Add stocks or initialize defaults.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <div class="mt-3">
                    <form method="post" style="display: inline;">
                        <button type="submit" name="update_all" class="btn btn-info">Update All Stocks</button>
                    </form>
                    
                    <form method="post" style="display: inline;">
                        <button type="submit" name="init_defaults" class="btn btn-warning">Initialize Default Stocks</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="mb-4">
            <a href="../index.php" class="btn btn-secondary">Back to Home</a>
        </div>
    </div>
    
    <script src="../js/jquery-3.3.1.slim.min.js"></script>
    <script src="../js/popper.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
</body>
</html>