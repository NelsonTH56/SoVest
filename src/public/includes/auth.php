<?php
/**
 * SoVest - Authentication Functions
 * 
 * This file contains functions related to user authentication and authorization.
 */

// Import the User model and database manager
use Database\Models\User;
use Illuminate\Database\Capsule\Manager as DB;

// Include database configuration
require_once __DIR__ . '/../bootstrap/database.php';

/**
 * Authenticate a user based on email and password
 * 
 * @param string $email User email
 * @param string $password User password
 * @return array|false User data if authenticated, false otherwise
 */
function authenticateUser($email, $password) {
    try {
        // Find user by email using Eloquent
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            return false; // No user found with this email
        }
        
        // First try with password_verify() for hashed passwords
        if (password_verify($password, $user->password)) {
            return $user->toArray();
        }
        // For backward compatibility, check plaintext password
        else if ($user->password == $password) {
            // Update the password to a hashed version
            $user->password = password_hash($password, PASSWORD_DEFAULT);
            $user->updated_at = now();
            $user->save();
            
            return $user->toArray();
        }
        
        return false;
    } catch (\Exception $e) {
        error_log("Authentication error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if user is authenticated
 * 
 * @return bool True if user is authenticated, false otherwise
 */
function isAuthenticated() {
    return isset($_COOKIE["userID"]) || isset($_SESSION['user_id']);
}

/**
 * Get current user ID
 * 
 * @return int|null User ID if authenticated, null otherwise
 */
function getCurrentUserId() {
    if (isset($_COOKIE["userID"])) {
        return $_COOKIE["userID"];
    }
    
    if (isset($_SESSION['user_id'])) {
        return $_SESSION['user_id'];
    }
    
    return null;
}

/**
 * Get current user data
 * 
 * @return array|null User data if authenticated, null otherwise
 */
function getCurrentUser() {
    $userId = getCurrentUserId();
    
    if ($userId === null) {
        return null;
    }
    
    try {
        // Use Eloquent to find the user by ID
        $user = User::find((int)$userId);
        
        if (!$user) {
            return null;
        }
        
        return $user->toArray();
    } catch (\Exception $e) {
        error_log("Error getting current user: " . $e->getMessage());
        return null;
    }
}

/**
 * Require authentication to access a page
 * 
 * If user is not authenticated, redirect to login page
 * 
 * @param string $redirect URL to redirect to after login
 * @return void
 */
function requireAuthentication($redirect = null) {
    if (!isAuthenticated()) {
        $redirectParam = $redirect ? '?redirect=' . urlencode($redirect) : '';
        header("Location: login.php$redirectParam");
        exit;
    }
}

/**
 * Log in a user
 * 
 * @param int $userId User ID
 * @param bool $rememberMe Whether to set a cookie for persistent login
 * @return void
 */
function loginUser($userId, $rememberMe = false) {
    $userId = (int)$userId;
    
    // Store user ID in session
    $_SESSION['user_id'] = $userId;
    
    // Set cookie if remember me is enabled
    if ($rememberMe) {
        // Cookie expires in 30 days
        setcookie("userID", $userId, time() + (86400 * 30), "/");
    } else {
        // Session cookie (expires when browser is closed)
        setcookie("userID", $userId, 0, "/");
    }
}

/**
 * Log out a user
 * 
 * @return void
 */
function logoutUser() {
    // Clear session
    $_SESSION = array();
    
    // Clear cookie
    setcookie("userID", "", time() - 3600, "/");
    
    // Destroy session
    if (session_status() == PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

/**
 * Register a new user
 * 
 * @param array $userData User data
 * @return int|false User ID if registered, false on failure
 */
function registerUser($userData) {
    try {
        // Validate email
        if (!isset($userData['email']) || !filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        // Validate password
        if (!isset($userData['password']) || strlen($userData['password']) < 6) {
            return false;
        }
        
        // Check if email already exists using Eloquent
        $existingUser = User::where('email', $userData['email'])->first();
        if ($existingUser) {
            return false; // Email already exists
        }
        
        // Hash the password
        $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        // Create new user with Eloquent
        $user = new User();
        $user->email = $userData['email'];
        $user->password = $hashedPassword;
        $user->first_name = $userData['first_name'] ?? '';
        $user->last_name = $userData['last_name'] ?? '';
        $user->major = $userData['major'] ?? '';
        $user->year = $userData['year'] ?? '';
        $user->scholarship = $userData['scholarship'] ?? '';
        $user->reputation_score = 0;
        $user->save();
        
        return $user->id;
    } catch (\Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        return false;
    }
}

/**
 * Update user profile
 * 
 * @param int $userId User ID
 * @param array $userData User data to update
 * @return bool True on success, false on failure
 */
function updateUserProfile($userId, $userData) {
    try {
        // Find the user by ID
        $user = User::find((int)$userId);
        
        if (!$user) {
            return false;
        }
        
        $validFields = ['email', 'password', 'first_name', 'last_name', 'major', 'year', 'scholarship'];
        
        foreach ($validFields as $field) {
            if (isset($userData[$field])) {
                // Handle password specially for hashing
                if ($field === 'password') {
                    $user->password = password_hash($userData[$field], PASSWORD_DEFAULT);
                } else {
                    $user->$field = $userData[$field];
                }
            }
        }
        
        // Save the updated user
        return $user->save();
    } catch (\Exception $e) {
        error_log("Update profile error: " . $e->getMessage());
        return false;
    }
}
?>