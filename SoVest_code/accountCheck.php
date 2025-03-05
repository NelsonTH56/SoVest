<?php
/**
 * Account Check Script
 * 
 * Handles new user registration using Eloquent ORM
 */

// Include Eloquent configuration
require_once 'bootstrap/database.php';

// Include User model
require_once 'database/models/User.php';

// Use the Models namespace
use Database\Models\User;

// Extract the form data with POST
$newEmail = isset($_POST['newEmail']) ? $_POST['newEmail'] : ''; 
$newPass = isset($_POST['newPass']) ? $_POST['newPass'] : '';

// Validate inputs
if (empty($newEmail) || empty($newPass)) {
    // Handle validation error
    header("Location: acctNew.php?error=missing_fields");
    exit;
}

try {
    // Check if the user already exists
    $existingUser = User::where('email', $newEmail)->first();
    
    if ($existingUser) {
        // User exists, redirect to home page with user ID
        $id = $existingUser->id;
        header("Location: home.php?userID=" . $id);
        exit;
    } else {
        // User doesn't exist, create a new user
        $user = new User();
        $user->email = $newEmail;
        $user->password = $newPass; // Note: In a production app, you should hash this password
        $user->reputation_score = 0;
        $user->save();
        
        // Redirect to login page
        header("Location: login.php");
        exit;
    }
} catch (Exception $e) {
    // Handle database errors
    header("Location: acctNew.php?error=database_error");
    exit;
}