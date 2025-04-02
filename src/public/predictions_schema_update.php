<?php
/**
 * SoVest Predictions Schema Update Script (DEPRECATED)
 * 
 * This script applies the database schema changes needed for stock predictions.
 * Updated to use centralized database configuration from db_config.php.
 * 
 * NOTE: This script is deprecated and uses legacy SQL files from the legacy directory.
 * For new installations, please use Laravel migrations instead:
 * php artisan migrate
 */

// Include centralized database configuration
require_once __DIR__ . '/includes/db_config.php';

// Database connection using centralized configuration
try {
    // Get database connection from centralized configuration
    $conn = getDbConnection();
    echo "Connected to database successfully.\n";
    
    // Read SQL schema file - using full path to legacy directory
    $sql_file = file_get_contents(__DIR__ . '/legacy/db_schema_update.sql');
    if ($sql_file === false) {
        throw new Exception("Could not read schema file: " . __DIR__ . '/legacy/db_schema_update.sql');
        
        // Note: This script is deprecated. Please use Laravel migrations instead:
        // php artisan migrate
    }
    $sql_commands = explode(';', $sql_file);
    
    $success_count = 0;
    $error_count = 0;
    
    // Execute each SQL command
    foreach ($sql_commands as $sql) {
        $sql = trim($sql);
        
        if (empty($sql)) {
            continue;
        }
        
        try {
            // Using the executeQuery function from db_config.php
            executeQuery($sql, $conn);
            echo "Successfully executed: " . substr($sql, 0, 50) . "...\n";
            $success_count++;
        } catch (Exception $e) {
            echo "Error executing SQL: " . $e->getMessage() . "\n";
            echo "SQL was: " . $sql . "\n";
            $error_count++;
        }
    }
    
    echo "\nSchema update completed.\n";
    echo "Successful commands: $success_count\n";
    echo "Failed commands: $error_count\n";
    
    // Close database connection properly
    closeDbConnection($conn);
    
} catch (Exception $e) {
    // Handle connection or other errors
    die("Error: " . $e->getMessage());
}
?>