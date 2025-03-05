<?php
/**
 * SoVest - Logout
 * 
 * This file handles user logout using the Eloquent authentication system.
 */

// Include the authentication functions
require_once __DIR__ . '/includes/auth.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

try {
    // Use the Eloquent-style logout function to properly clear session and cookies
    logoutUser();
    
    // Redirect to index page
    header("Location: index.php");
    exit;
} catch (Exception $e) {
    // Log the error
    error_log("Logout error: " . $e->getMessage());
    
    // Redirect to index page even if there's an error
    header("Location: index.php?error=logout_failed");
    exit;
}
?>