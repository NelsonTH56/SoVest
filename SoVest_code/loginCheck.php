<?php
/**
 * SoVest - Login Check
 * 
 * Validates user login credentials against the database.
 * Uses Eloquent ORM for database operations.
 */

// Include Eloquent ORM setup and User model
require_once 'bootstrap/database.php';
require_once 'database/models/User.php';

use Database\Models\User;

// Extract the form data with POST
$tryEmail = isset($_POST['tryEmail']) ? $_POST['tryEmail'] : '';
$tryPass = isset($_POST['tryPass']) ? $_POST['tryPass'] : '';

try {
    // Find the user by email using Eloquent
    $user = User::where('email', $tryEmail)->first();
    
    // If user not found, redirect to login page
    if (!$user) {
        header("Location: index.php");
        exit;
    }
    
    // If user found, compare the password securely
    // First try with password_verify() for hashed passwords
    if (password_verify($tryPass, $user->password)) {
        setcookie("userID", $user->id, time() + (86400 * 30), "/"); // Sets cookie for 30 days
        header("Location: home.php");
        exit;
    } 
    // For backward compatibility, check if the password matches plaintext (old method)
    else if ($tryPass == $user->password) {
        // Set the cookie for login
        setcookie("userID", $user->id, time() + (86400 * 30), "/"); // Sets cookie for 30 days
        
        // Update the password to hashed version for future logins using Eloquent
        $user->password = password_hash($tryPass, PASSWORD_DEFAULT);
        $user->updated_at = date('Y-m-d H:i:s'); // Set current timestamp for updated_at
        $user->save();
        
        header("Location: home.php");
        exit;
    } else {
        // Password doesn't match, redirect to login page
        header("Location: index.php");
        exit;
    }
    
} catch (Exception $e) {
    // Log the error
    error_log("Login error: " . $e->getMessage());
    
    // Redirect to login page with a generic error
    header("Location: index.php?error=1");
    exit;
}
?>