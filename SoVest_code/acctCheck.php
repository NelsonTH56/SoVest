<?php
/**
 * SoVest - Account Creation Handler
 * 
 * Validates and processes new user registration.
 * Uses Eloquent ORM for database operations.
 */

// Include Eloquent ORM setup and User model
require_once 'bootstrap/database.php';
require_once 'database/models/User.php';

use Database\Models\User;

// Extract the form data with POST and validate
$newEmail = isset($_POST['newEmail']) ? filter_var($_POST['newEmail'], FILTER_SANITIZE_EMAIL) : '';
$newPass = isset($_POST['newPass']) ? $_POST['newPass'] : '';
$newMajor = isset($_POST['newMajor']) ? filter_var($_POST['newMajor'], FILTER_SANITIZE_STRING) : '';
$newYear = isset($_POST['newYear']) ? filter_var($_POST['newYear'], FILTER_SANITIZE_STRING) : '';
$newScholarship = isset($_POST['newScholarship']) ? filter_var($_POST['newScholarship'], FILTER_SANITIZE_STRING) : '';

// Basic validation
if (empty($newEmail) || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
    // Invalid email
    header("Location: acctNew.php?error=invalid_email");
    exit;
}

if (empty($newPass) || strlen($newPass) < 6) {
    // Password too short
    header("Location: acctNew.php?error=password_too_short");
    exit;
}

try {
    // Check if the email already exists using Eloquent
    $existingUser = User::where('email', $newEmail)->first();
    
    // If user exists, redirect back to account creation page
    if ($existingUser) {
        header("Location: acctNew.php?error=email_exists");
        exit;
    }
    
    // If email doesn't exist, create a new user
    // Hash the password
    $hashedPassword = password_hash($newPass, PASSWORD_DEFAULT);
    
    // Create a new User model instance and save
    $user = new User();
    $user->email = $newEmail;
    $user->password = $hashedPassword;
    $user->major = $newMajor;
    $user->year = $newYear;
    $user->scholarship = $newScholarship;
    $user->save();
    
    // Redirect to the login page with success message
    header("Location: login.php?success=1");
    exit;
    
} catch (Exception $e) {
    // Log the error
    error_log("Account creation error: " . $e->getMessage());
    
    // Redirect to account creation page with error
    header("Location: acctNew.php?error=system_error");
    exit;
}
?>