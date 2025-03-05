<?php
/**
 * SoVest - Data Migration Utility
 * 
 * This script migrates data from legacy database tables to new Eloquent ORM tables.
 * It handles the entire migration process including:
 * - Checking for the existence of necessary tables
 * - Migrating data while preserving IDs
 * - Validating the migration
 * - Detailed reporting during the migration process
 * 
 * REQUIREMENTS:
 * - PHP 7.0 or higher
 * - MySQL 5.7 or higher
 * - Valid database credentials in .env file
 * - Appropriate database permissions
 * 
 * USAGE:
 * php migrate_data.php [--test]
 *   --test: Run in test mode (no database changes)
 */

// Set error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the database configuration for Eloquent
require_once 'bootstrap/database.php';
require_once 'includes/db_config.php';

// Set up autoloader for models
spl_autoload_register(function ($class) {
    // Check if the class belongs to our Models namespace
    if (strpos($class, 'Models\\') === 0) {
        $className = str_replace('Models\\', '', $class);
        $fileName = __DIR__ . '/database/models/' . $className . '.php';
        if (file_exists($fileName)) {
            require_once $fileName;
        }
    }
});

// Import models
use Database\Models\User;
use Database\Models\Stock;
use Database\Models\Prediction;
use Database\Models\PredictionVote;
use Database\Models\StockPrice;
use Database\Models\SearchHistory;
use Database\Models\SavedSearch;

// Constants for the migration
define('LOG_FILE', dirname(__FILE__) . '/logs/data_migration_log.txt');

// Create logs directory if it doesn't exist
if (!file_exists(dirname(LOG_FILE))) {
    mkdir(dirname(LOG_FILE), 0755, true);
}

/**
 * Write a message to the migration log file and output to console
 *
 * @param string $message The message to log
 * @param string $level The log level (INFO, WARNING, ERROR)
 * @return void
 */
function logMigration($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
    file_put_contents(LOG_FILE, $logMessage, FILE_APPEND);
    
    // Also output to console
    echo $logMessage;
}

/**
 * Check if a table exists in the database
 *
 * @param string $tableName Name of the table to check
 * @return bool True if the table exists, false otherwise
 */
function tableExists($tableName) {
    // Use Illuminate's DB facade for database operations
    return \Illuminate\Database\Capsule\Manager::schema()->hasTable($tableName);
}

/**
 * Count the number of rows in a table
 *
 * @param string $tableName Name of the table to count
 * @return int Number of rows in the table
 */
function countTableRows($tableName) {
    // Use raw query through Eloquent's DB facade
    return \Illuminate\Database\Capsule\Manager::table($tableName)->count();
}

/**
 * Migrate users from npedigoUser to users table
 *
 * @param bool $testMode Whether to run in test mode
 * @return array Migration status information
 */
function migrateUsers($testMode) {
    if (!tableExists('npedigoUser')) {
        logMigration("npedigoUser table does not exist, skipping user migration", 'INFO');
        return ['success' => true, 'skipped' => true, 'message' => 'npedigoUser table does not exist'];
    }

    // Check if users table already has data
    $existingUsers = User::count();
    if ($existingUsers > 0) {
        logMigration("Users table already contains $existingUsers records, skipping migration", 'INFO');
        return ['success' => true, 'skipped' => true, 'message' => 'Users table already has data'];
    }

    try {
        if ($testMode) {
            logMigration("Test mode: Would migrate users from npedigoUser to users table", 'INFO');
            return ['success' => true, 'skipped' => true, 'message' => 'Test mode, no changes made'];
        }

        // Start database transaction
        \Illuminate\Database\Capsule\Manager::connection()->beginTransaction();

        // Check columns in npedigoUser table
        $hasFirstName = \Illuminate\Database\Capsule\Manager::schema()->hasColumn('npedigoUser', 'first_name');
        $hasLastName = \Illuminate\Database\Capsule\Manager::schema()->hasColumn('npedigoUser', 'last_name');

        // Get legacy users using Eloquent's DB facade
        $legacyUsers = \Illuminate\Database\Capsule\Manager::table('npedigoUser')->get();
        
        $migrated = 0;
        foreach ($legacyUsers as $legacyUser) {
            $userData = [
                'id' => $legacyUser->id,
                'email' => $legacyUser->email,
                'password' => $legacyUser->password, // Note: We're keeping the same password format
                'major' => isset($legacyUser->major) ? $legacyUser->major : null,
                'year' => isset($legacyUser->year) ? $legacyUser->year : null,
                'scholarship' => isset($legacyUser->scholarship) ? $legacyUser->scholarship : null,
                'reputation_score' => isset($legacyUser->reputation_score) ? $legacyUser->reputation_score : 0,
            ];
            
            // Add first_name and last_name if they exist in the source table
            if ($hasFirstName && isset($legacyUser->first_name)) {
                $userData['first_name'] = $legacyUser->first_name;
            }
            
            if ($hasLastName && isset($legacyUser->last_name)) {
                $userData['last_name'] = $legacyUser->last_name;
            }
            
            // Create user using Eloquent's create method
            User::create($userData);
            $migrated++;
        }
        
        // Commit transaction
        \Illuminate\Database\Capsule\Manager::connection()->commit();

        logMigration("Successfully migrated $migrated users from npedigoUser to users table", 'INFO');
        return [
            'success' => true, 
            'migrated' => $migrated, 
            'message' => "Successfully migrated $migrated users"
        ];
    } catch (\Exception $e) {
        // Rollback transaction on error
        \Illuminate\Database\Capsule\Manager::connection()->rollBack();
        logMigration("Error migrating users: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Migrate stocks data to the Eloquent stocks table
 *
 * @param bool $testMode Whether to run in test mode
 * @return array Migration status information
 */
function migrateStocks($testMode) {
    // Check if there's a legacy stocks table with a different structure
    // For now, we'll just check if the stocks table exists and has data
    if (!tableExists('stocks')) {
        logMigration("Stocks table does not exist, no migration needed", 'INFO');
        return ['success' => true, 'skipped' => true, 'message' => 'No stocks table found'];
    }

    // Count existing Eloquent stocks
    $existingCount = Stock::count();
    
    if ($existingCount > 0) {
        logMigration("Stocks table already contains $existingCount records, no need to migrate", 'INFO');
        return ['success' => true, 'skipped' => true, 'message' => 'Stocks table already has data'];
    }
    
    logMigration("No legacy stocks data to migrate", 'INFO');
    return ['success' => true, 'skipped' => true, 'message' => 'No migration needed for stocks'];
}

/**
 * Migrate predictions data to the Eloquent predictions table
 *
 * @param bool $testMode Whether to run in test mode
 * @return array Migration status information
 */
function migratePredictions($testMode) {
    // Check if there's a legacy predictions table
    // This would involve checking for any potential old prediction tables
    // For now, we'll just check the new predictions table
    
    if (!tableExists('predictions')) {
        logMigration("Predictions table does not exist, no migration needed", 'INFO');
        return ['success' => true, 'skipped' => true, 'message' => 'No predictions table found'];
    }

    // Count existing Eloquent predictions
    $existingCount = Prediction::count();
    
    if ($existingCount > 0) {
        logMigration("Predictions table already contains $existingCount records, no need to migrate", 'INFO');
        return ['success' => true, 'skipped' => true, 'message' => 'Predictions table already has data'];
    }
    
    logMigration("No legacy predictions data to migrate", 'INFO');
    return ['success' => true, 'skipped' => true, 'message' => 'No migration needed for predictions'];
}

/**
 * Migrate prediction votes data to the Eloquent prediction_votes table
 *
 * @param bool $testMode Whether to run in test mode
 * @return array Migration status information
 */
function migratePredictionVotes($testMode) {
    if (!tableExists('prediction_votes')) {
        logMigration("Prediction votes table does not exist, no migration needed", 'INFO');
        return ['success' => true, 'skipped' => true, 'message' => 'No prediction_votes table found'];
    }

    // Count existing Eloquent prediction votes
    $existingCount = PredictionVote::count();
    
    if ($existingCount > 0) {
        logMigration("Prediction votes table already contains $existingCount records, no need to migrate", 'INFO');
        return ['success' => true, 'skipped' => true, 'message' => 'Prediction votes table already has data'];
    }
    
    logMigration("No legacy prediction votes data to migrate", 'INFO');
    return ['success' => true, 'skipped' => true, 'message' => 'No migration needed for prediction votes'];
}

/**
 * Migrate stock prices data to the Eloquent stock_prices table
 *
 * @param bool $testMode Whether to run in test mode
 * @return array Migration status information
 */
function migrateStockPrices($testMode) {
    if (!tableExists('stock_prices')) {
        logMigration("Stock prices table does not exist, no migration needed", 'INFO');
        return ['success' => true, 'skipped' => true, 'message' => 'No stock_prices table found'];
    }

    // Count existing Eloquent stock prices
    $existingCount = StockPrice::count();
    
    if ($existingCount > 0) {
        logMigration("Stock prices table already contains $existingCount records, no need to migrate", 'INFO');
        return ['success' => true, 'skipped' => true, 'message' => 'Stock prices table already has data'];
    }
    
    logMigration("No legacy stock prices data to migrate", 'INFO');
    return ['success' => true, 'skipped' => true, 'message' => 'No migration needed for stock prices'];
}

/**
 * Migrate search history data to the Eloquent search_history table
 *
 * @param bool $testMode Whether to run in test mode
 * @return array Migration status information
 */
function migrateSearchHistory($testMode) {
    if (!tableExists('search_history')) {
        logMigration("Search history table does not exist, no migration needed", 'INFO');
        return ['success' => true, 'skipped' => true, 'message' => 'No search_history table found'];
    }

    // Count existing Eloquent search history records
    $existingCount = SearchHistory::count();
    
    if ($existingCount > 0) {
        logMigration("Search history table already contains $existingCount records, no need to migrate", 'INFO');
        return ['success' => true, 'skipped' => true, 'message' => 'Search history table already has data'];
    }
    
    logMigration("No legacy search history data to migrate", 'INFO');
    return ['success' => true, 'skipped' => true, 'message' => 'No migration needed for search history'];
}

/**
 * Migrate saved searches data to the Eloquent saved_searches table
 *
 * @param bool $testMode Whether to run in test mode
 * @return array Migration status information
 */
function migrateSavedSearches($testMode) {
    if (!tableExists('saved_searches')) {
        logMigration("Saved searches table does not exist, no migration needed", 'INFO');
        return ['success' => true, 'skipped' => true, 'message' => 'No saved_searches table found'];
    }

    // Count existing Eloquent saved searches
    $existingCount = SavedSearch::count();
    
    if ($existingCount > 0) {
        logMigration("Saved searches table already contains $existingCount records, no need to migrate", 'INFO');
        return ['success' => true, 'skipped' => true, 'message' => 'Saved searches table already has data'];
    }
    
    logMigration("No legacy saved searches data to migrate", 'INFO');
    return ['success' => true, 'skipped' => true, 'message' => 'No migration needed for saved searches'];
}

/**
 * Verify the integrity of the migrated data
 *
 * @return bool True if verification passed, false otherwise
 */
function verifyMigration() {
    $success = true;
    $messages = [];
    
    // Verify users migration if applicable
    if (tableExists('npedigoUser') && tableExists('users')) {
        $legacyCount = countTableRows('npedigoUser');
        $newCount = User::count();
        
        if ($newCount < $legacyCount) {
            logMigration("User verification failed: users table has fewer rows ($newCount) than npedigoUser ($legacyCount)", 'ERROR');
            $success = false;
            $messages[] = "User migration verification failed";
        } else {
            logMigration("User verification passed: npedigoUser has $legacyCount rows, users has $newCount rows");
            $messages[] = "User migration verification passed";
        }
    }
    
    // Add more verification steps for other tables if needed
    
    return ['success' => $success, 'messages' => $messages];
}

/**
 * Main migration process that orchestrates all migrations
 *
 * @param bool $testMode Whether to run in test mode
 * @return void
 */
function runMigration($testMode = false) {
    logMigration("Starting data migration" . ($testMode ? " (TEST MODE)" : ""));
    
    try {
        // Make sure we have a database connection
        try {
            \Illuminate\Database\Capsule\Manager::connection()->getPdo();
            logMigration("Database connection established successfully");
        } catch (\Exception $e) {
            throw new \Exception("Failed to connect to database: " . $e->getMessage());
        }
        
        // Run migrations for each table type
        $results = [
            'users' => migrateUsers($testMode),
            'stocks' => migrateStocks($testMode),
            'predictions' => migratePredictions($testMode),
            'prediction_votes' => migratePredictionVotes($testMode),
            'stock_prices' => migrateStockPrices($testMode),
            'search_history' => migrateSearchHistory($testMode),
            'saved_searches' => migrateSavedSearches($testMode)
        ];
        
        // Print migration results summary
        logMigration("Migration Results Summary:");
        foreach ($results as $table => $result) {
            $status = $result['success'] ? ($result['skipped'] ?? false ? "SKIPPED" : "SUCCESS") : "FAILED";
            logMigration("  $table: $status - " . $result['message']);
        }
        
        // Verify migration if not in test mode
        if (!$testMode) {
            $verification = verifyMigration();
            if ($verification['success']) {
                logMigration("Data migration verification passed");
            } else {
                logMigration("Data migration verification failed", 'WARNING');
                foreach ($verification['messages'] as $message) {
                    logMigration("  - $message", 'WARNING');
                }
            }
        }
        
        logMigration("Data migration completed" . ($testMode ? " (TEST MODE - no changes committed)" : " successfully"));
        
    } catch (\Exception $e) {
        logMigration("Migration failed: " . $e->getMessage(), 'ERROR');
        logMigration("Please check the log file for details and resolve any issues before retrying.", 'ERROR');
        
        // Provide guidance on common errors
        if (strpos($e->getMessage(), 'access denied') !== false || strpos($e->getMessage(), 'connection refused') !== false) {
            logMigration("Database connection issue detected. Please check your .env file for correct credentials.", 'INFO');
        } 
        
        if (strpos($e->getMessage(), 'Unknown database') !== false) {
            logMigration("Database does not exist. Please create it or check your .env file for the correct database name.", 'INFO');
        }
    }
}

// Parse command line arguments
$testMode = false;
if (isset($argv) && count($argv) > 1) {
    $testMode = ($argv[1] === '--test');
}

// Run the migration
runMigration($testMode);
?>