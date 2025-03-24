<?php
/**
 * SoVest Integration Test Script
 * 
 * This file tests the integration of the standardized components
 * across the application and checks for common issues.
 */

// Start timer
$startTime = microtime(true);

// Test results storage
$results = [
    'header_footer' => [
        'status' => 'Unknown',
        'details' => []
    ],
    'database' => [
        'status' => 'Unknown',
        'details' => []
    ],
    'authentication' => [
        'status' => 'Unknown',
        'details' => []
    ],
    'services' => [
        'status' => 'Unknown',
        'details' => []
    ],
    'includes' => [
        'status' => 'Unknown',
        'details' => []
    ]
];

// Output function for readable results
function outputResults($results) {
    echo "<h1>SoVest Integration Test Results</h1>";
    
    foreach ($results as $category => $data) {
        $statusClass = 'warning';
        if ($data['status'] === 'Pass') {
            $statusClass = 'success';
        } elseif ($data['status'] === 'Fail') {
            $statusClass = 'danger';
        }
        
        echo "<div style='margin-bottom: 20px;'>";
        echo "<h3>$category <span class='badge bg-$statusClass'>{$data['status']}</span></h3>";
        echo "<ul>";
        foreach ($data['details'] as $detail) {
            $itemClass = isset($detail['status']) && $detail['status'] === 'Pass' ? 'text-success' : 
                        (isset($detail['status']) && $detail['status'] === 'Fail' ? 'text-danger' : 'text-warning');
            echo "<li class='$itemClass'>" . $detail['message'] . "</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    
    // Overall result
    $allPassed = true;
    foreach ($results as $category => $data) {
        if ($data['status'] !== 'Pass') {
            $allPassed = false;
            break;
        }
    }
    
    $overallClass = $allPassed ? 'success' : 'warning';
    $overallStatus = $allPassed ? 'PASS' : 'ISSUES DETECTED';
    
    echo "<div class='alert alert-$overallClass'>";
    echo "<h2>Overall Result: $overallStatus</h2>";
    echo "<p>Test completed in " . round(microtime(true) - $GLOBALS['startTime'], 2) . " seconds</p>";
    echo "</div>";
}

// 1. Test header and footer
try {
    // Check if header.php and footer.php exist
    if (!file_exists(__DIR__ . '/includes/header.php')) {
        throw new Exception("header.php not found");
    }
    $results['header_footer']['details'][] = ['status' => 'Pass', 'message' => 'header.php exists'];
    
    if (!file_exists(__DIR__ . '/includes/footer.php')) {
        throw new Exception("footer.php not found");
    }
    $results['header_footer']['details'][] = ['status' => 'Pass', 'message' => 'footer.php exists'];
    
    // Verify header content
    $headerContent = file_get_contents(__DIR__ . '/includes/header.php');
    if (strpos($headerContent, '<nav class="navbar') === false) {
        throw new Exception("Navigation not found in header.php");
    }
    $results['header_footer']['details'][] = ['status' => 'Pass', 'message' => 'Navigation exists in header'];
    
    // Verify footer content
    $footerContent = file_get_contents(__DIR__ . '/includes/footer.php');
    if (strpos($footerContent, '<footer') === false) {
        throw new Exception("Footer tag not found in footer.php");
    }
    $results['header_footer']['details'][] = ['status' => 'Pass', 'message' => 'Footer tag exists in footer'];
    
    $results['header_footer']['status'] = 'Pass';
} catch (Exception $e) {
    $results['header_footer']['status'] = 'Fail';
    $results['header_footer']['details'][] = ['status' => 'Fail', 'message' => 'Error: ' . $e->getMessage()];
}

// 2. Test database connection
try {
    if (!file_exists(__DIR__ . '/includes/db_config.php')) {
        throw new Exception("db_config.php not found");
    }
    $results['database']['details'][] = ['status' => 'Pass', 'message' => 'db_config.php exists'];
    
    // Include db_config
    require_once __DIR__ . '/includes/db_config.php';
    $results['database']['details'][] = ['status' => 'Pass', 'message' => 'db_config.php included successfully'];
    
    // Test connection using legacy method
    $conn = getDbConnection();
    if (!$conn) {
        throw new Exception("Could not establish database connection");
    }
    $results['database']['details'][] = ['status' => 'Pass', 'message' => 'Database connection successful'];
    
    // Test query execution
    $result = executeQuery("SELECT 1 as test", $conn);
    if (!$result) {
        throw new Exception("Query execution failed");
    }
    $row = mysqli_fetch_assoc($result);
    if (!isset($row['test']) || $row['test'] != 1) {
        throw new Exception("Query result verification failed");
    }
    $results['database']['details'][] = ['status' => 'Pass', 'message' => 'Query execution successful'];
    
    $results['database']['status'] = 'Pass';
} catch (Exception $e) {
    $results['database']['status'] = 'Fail';
    $results['database']['details'][] = ['status' => 'Fail', 'message' => 'Error: ' . $e->getMessage()];
}

// 3. Test authentication functions
try {
    if (!file_exists(__DIR__ . '/includes/auth.php')) {
        throw new Exception("auth.php not found");
    }
    $results['authentication']['details'][] = ['status' => 'Pass', 'message' => 'auth.php exists'];
    
    // Include auth.php
    require_once __DIR__ . '/includes/auth.php';
    $results['authentication']['details'][] = ['status' => 'Pass', 'message' => 'auth.php included successfully'];
    
    // Test functions existence
    if (!function_exists('isAuthenticated')) {
        throw new Exception("isAuthenticated function not found");
    }
    $results['authentication']['details'][] = ['status' => 'Pass', 'message' => 'isAuthenticated function exists'];
    
    if (!function_exists('getCurrentUserId')) {
        throw new Exception("getCurrentUserId function not found");
    }
    $results['authentication']['details'][] = ['status' => 'Pass', 'message' => 'getCurrentUserId function exists'];
    
    if (!function_exists('authenticateUser')) {
        throw new Exception("authenticateUser function not found");
    }
    $results['authentication']['details'][] = ['status' => 'Pass', 'message' => 'authenticateUser function exists'];
    
    $results['authentication']['status'] = 'Pass';
} catch (Exception $e) {
    $results['authentication']['status'] = 'Fail';
    $results['authentication']['details'][] = ['status' => 'Fail', 'message' => 'Error: ' . $e->getMessage()];
}

// 4. Test service files
try {
    $requiredServices = [
        '/services/StockDataService.php',
        '/services/PredictionScoringService.php'
    ];
    
    foreach ($requiredServices as $service) {
        if (!file_exists(__DIR__ . $service)) {
            throw new Exception("$service not found");
        }
        $results['services']['details'][] = ['status' => 'Pass', 'message' => "$service exists"];
    }
    
    // Test StockDataService
    require_once __DIR__ . '/services/StockDataService.php';
    if (!class_exists('StockDataService')) {
        throw new Exception("StockDataService class not found");
    }
    $results['services']['details'][] = ['status' => 'Pass', 'message' => 'StockDataService class exists'];
    
    // Test PredictionScoringService
    require_once __DIR__ . '/services/PredictionScoringService.php';
    if (!class_exists('PredictionScoringService')) {
        throw new Exception("PredictionScoringService class not found");
    }
    $results['services']['details'][] = ['status' => 'Pass', 'message' => 'PredictionScoringService class exists'];
    
    $results['services']['status'] = 'Pass';
} catch (Exception $e) {
    $results['services']['status'] = 'Fail';
    $results['services']['details'][] = ['status' => 'Fail', 'message' => 'Error: ' . $e->getMessage()];
}

// 5. Test include files
try {
    $requiredIncludes = [
        '/includes/prediction_score_display.php',
        '/includes/search_bar.php'
    ];
    
    foreach ($requiredIncludes as $include) {
        if (!file_exists(__DIR__ . $include)) {
            throw new Exception("$include not found");
        }
        $results['includes']['details'][] = ['status' => 'Pass', 'message' => "$include exists"];
    }
    
    // Test prediction_score_display.php
    require_once __DIR__ . '/includes/prediction_score_display.php';
    if (!function_exists('renderPredictionBadge')) {
        throw new Exception("renderPredictionBadge function not found");
    }
    $results['includes']['details'][] = ['status' => 'Pass', 'message' => 'renderPredictionBadge function exists'];
    
    // Test search_bar.php
    require_once __DIR__ . '/includes/search_bar.php';
    if (!function_exists('renderSearchBar')) {
        throw new Exception("renderSearchBar function not found");
    }
    $results['includes']['details'][] = ['status' => 'Pass', 'message' => 'renderSearchBar function exists'];
    
    $results['includes']['status'] = 'Pass';
} catch (Exception $e) {
    $results['includes']['status'] = 'Fail';
    $results['includes']['details'][] = ['status' => 'Fail', 'message' => 'Error: ' . $e->getMessage()];
}

// Output results
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SoVest Integration Test</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { padding: 20px; }
        .badge { font-size: 1rem; }
    </style>
</head>
<body>
    <div class="container">
        <?php outputResults($results); ?>
        
        <div class="mt-4">
            <a href="index.php" class="btn btn-primary">Return to Homepage</a>
        </div>
    </div>
</body>
</html>