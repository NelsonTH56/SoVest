<?php
/**
 * SoVest - Standardized Header
 * 
 * This file contains the standard header used across all pages in the application.
 * It includes the navigation menu, search functionality, and common meta tags.
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is authenticated
$isAuthenticated = isset($_COOKIE["userID"]);
$userID = $isAuthenticated ? $_COOKIE["userID"] : null;

// Include search bar component if available
if (file_exists(__DIR__ . '/search_bar.php')) {
    require_once __DIR__ . '/search_bar.php';
}

// Page title defaults to SoVest if not specified
$pageTitle = isset($pageTitle) ? $pageTitle . ' - SoVest' : 'SoVest';

// Set active page for navigation highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="SoVest - Social Stock Predictions Platform">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="images/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon-16x16.png">
    <link rel="manifest" href="images/site.webmanifest">
    
    <!-- Common styles -->
    <style>
        body {
            background-color: #2c2c2c;
            color: #d4d4d4;
        }
        .navbar {
            background-color: #1f1f1f;
        }
        .btn-primary {
            background-color: #28a745;
            border-color: #28a745;
        }
        .btn-primary:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }
        .btn-warning {
            background-color: #ffc107;
            border-color: #ffc107;
        }
        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
        }
        .card {
            background-color: #1f1f1f;
        }
    </style>
    
    <!-- Page-specific CSS -->
    <?php if (isset($pageCss)): ?>
    <link rel="stylesheet" href="<?php echo $pageCss; ?>">
    <?php endif; ?>
</head>
<body>
    <?php 
    // Initialize search bar if function exists
    if (function_exists('addSearchToNav')) {
        addSearchToNav();
    }
    ?>
    
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">SoVest</a>
            <img src="./images/logo.png" width="50px" alt="SoVest Logo">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Search bar in navigation -->
                <?php if (function_exists('renderSearchBar')): ?>
                    <?php echo renderSearchBar(); ?>
                <?php endif; ?>
                
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage == 'home.php' ? 'active' : ''; ?>" 
                           href="home.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage == 'search.php' ? 'active' : ''; ?>" 
                           href="search.php">Search</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage == 'trending.php' ? 'active' : ''; ?>" 
                           href="trending.php">Trending</a>
                    </li>
                    
                    <?php if ($isAuthenticated): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $currentPage == 'my_predictions.php' ? 'active' : ''; ?>" 
                               href="my_predictions.php">My Predictions</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $currentPage == 'leaderboard.php' ? 'active' : ''; ?>" 
                               href="leaderboard.php">Leaderboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $currentPage == 'account.php' ? 'active' : ''; ?>" 
                               href="account.php">My Account</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $currentPage == 'login.php' ? 'active' : ''; ?>" 
                               href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $currentPage == 'acctNew.php' ? 'active' : ''; ?>" 
                               href="acctNew.php">Sign Up</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main content container -->
    <div class="container mt-4">