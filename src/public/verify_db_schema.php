<?php
/**
 * SoVest - Database Schema Verification
 * 
 * This script verifies that all required database tables and columns exist.
 * It uses Eloquent Schema Builder to check the database structure.
 * Updated to check for the new users table while maintaining backward compatibility.
 */

// Include necessary configuration
require_once __DIR__ . '/includes/db_config.php';

// Bootstrap Eloquent
require_once __DIR__ . '/bootstrap/database.php';

// Import required classes
use Illuminate\Database\Capsule\Manager as Capsule;

// Try to connect to the database using Eloquent
try {
    // Verify database connection by making a simple query
    Capsule::select('SELECT 1 as connection_test');
    
    echo "Connected to database successfully.<br><br>";
    
    // Function to check if a table exists using Eloquent Schema Builder
    function tableExists($table) {
        return Capsule::schema()->hasTable($table);
    }
    
    // Function to check if a column exists in a table using Eloquent Schema Builder
    function columnExists($table, $column) {
        return Capsule::schema()->hasColumn($table, $column);
    }
    
    // Check if all required tables exist
    $tables = ['users', 'stocks', 'predictions', 'prediction_votes', 'stock_prices'];
    $all_tables_exist = true;
    
    foreach ($tables as $table) {
        if (tableExists($table)) {
            echo "✓ Table '$table' exists.<br>";
        } else {
            echo "✗ Table '$table' does not exist!<br>";
            $all_tables_exist = false;
        }
    }
    
    // Check if reputation_score column exists in users table
    if (tableExists('users')) {
        if (columnExists('users', 'reputation_score')) {
            echo "✓ Column 'reputation_score' exists in users table.<br>";
        } else {
            echo "✗ Column 'reputation_score' does not exist in users table!<br>";
            $all_tables_exist = false;
        }
    }
    
    // For backward compatibility, check if npedigoUser table exists
    if (tableExists('npedigoUser')) {
        echo "✓ Legacy table 'npedigoUser' exists (will be deprecated).<br>";
        
        // Check if reputation_score column exists in npedigoUser table
        if (columnExists('npedigoUser', 'reputation_score')) {
            echo "✓ Column 'reputation_score' exists in npedigoUser table.<br>";
        } else {
            echo "✗ Column 'reputation_score' does not exist in npedigoUser table!<br>";
            // Not necessary for mandatory functionality, so don't update $all_tables_exist
        }
    } else {
        echo "ⓘ Legacy table 'npedigoUser' does not exist. This is fine if you're using only the new schema.<br>";
    }
    
    echo "<br>";
    if ($all_tables_exist) {
        echo "All required database objects exist. Schema update was successful.";
    } else {
        echo "Some database objects are missing. Please run Laravel migrations with 'php artisan migrate' or check the legacy script at 'legacy/apply_db_schema.php' if using the legacy method.";
    }
    
} catch (Exception $e) {
    // Handle connection errors
    die("Database error: " . $e->getMessage());
}
?>