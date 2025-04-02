<?php
/**
 * SoVest Search Schema Update Script
 * 
 * This script applies the database schema changes needed for search functionality
 * using Laravel migration classes instead of raw SQL.
 */

// Include the Laravel Eloquent setup
require_once __DIR__ . '/bootstrap/database.php';
require_once __DIR__ . '/includes/db_config.php';

// Import required namespaces
use Illuminate\Database\Capsule\Manager as Capsule;

// Import migration classes
require_once __DIR__ . '/database/migrations/create_search_history_table.php';
require_once __DIR__ . '/database/migrations/create_saved_searches_table.php';

echo "Starting search schema update process...\n";

try {
    // Get database connection from centralized configuration
    $conn = getDbConnection();
    echo "Connected to database successfully.\n";

    $success_count = 0;
    $error_count = 0;

    // Run migrations in the correct order
    // Note: saved_searches should be created after search_history to maintain proper dependency order
    
    try {
        // Create search_history table first
        $searchHistoryMigration = new CreateSearchHistoryTable();
        $searchHistoryMigration->up();
        $success_count++;
    } catch (Exception $e) {
        echo "Error creating search_history table: " . $e->getMessage() . "\n";
        $error_count++;
    }

    try {
        // Create saved_searches table second
        $savedSearchesMigration = new CreateSavedSearchesTable();
        $savedSearchesMigration->up();
        $success_count++;
    } catch (Exception $e) {
        echo "Error creating saved_searches table: " . $e->getMessage() . "\n";
        $error_count++;
    }

    // Create migrations version tracking table if it doesn't exist
    if (!Capsule::schema()->hasTable('migrations_log')) {
        Capsule::schema()->create('migrations_log', function ($table) {
            $table->increments('id');
            $table->string('version');
            $table->timestamp('applied_at')->useCurrent();
        });
        
        echo "Migrations log table created.\n";
    }
    
    // Record this migration - check if we already applied this version
    $existingMigration = Capsule::table('migrations_log')
        ->where('version', 'search_schema_1.0')
        ->first();
        
    if (!$existingMigration) {
        Capsule::table('migrations_log')->insert([
            'version' => 'search_schema_1.0',
            'applied_at' => date('Y-m-d H:i:s')
        ]);
        echo "Migration version recorded.\n";
    } else {
        echo "Migration version was already recorded.\n";
    }

    echo "\nSearch schema update completed.\n";
    echo "Successful operations: $success_count\n";
    echo "Failed operations: $error_count\n";

    // Close database connection (legacy connection)
    closeDbConnection($conn);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>