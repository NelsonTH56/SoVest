<?php

/**
 * SoVest Database Migration Runner
 *
 * This script runs all migrations in the correct order to set up or update
 * the database structure. It tracks which migrations have been run to avoid
 * duplicate executions.
 * 
 * Usage:
 *   php migrate.php           - Run pending migrations
 *   php migrate.php --fresh   - Drop all tables and run all migrations
 *   php migrate.php --rollback - Rollback the last batch of migrations
 * 
 * The script will:
 * 1. Create a migrations table to track applied migrations
 * 2. Find and apply all pending migrations in the correct order
 * 3. Handle table dependencies automatically
 * 4. Provide detailed output of the migration process
 */

// Require the database configuration
require_once __DIR__ . '/bootstrap/database.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

// Define the migrations path
$migrationsPath = __DIR__ . '/database/migrations';

// Initialize command line arguments
$args = array_slice($_SERVER['argv'], 1);
$freshFlag = in_array('--fresh', $args);
$rollbackFlag = in_array('--rollback', $args);

// Handle the fresh flag (drop all tables and start fresh)
if ($freshFlag) {
    handleFreshInstall();
} elseif ($rollbackFlag) {
    // Handle rollback
    rollback();
} else {
    // Normal migration process
    runMigrations();
}

/**
 * Main migration function
 */
function runMigrations() {
    global $migrationsPath;
    
    // Create migrations table if it doesn't exist
    createMigrationsTable();
    
    // Get all applied migrations from the database
    $appliedMigrations = Capsule::table('migrations')->pluck('migration')->toArray();
    
    // Get all migration files from the directory
    $migrationFiles = scandir($migrationsPath);
    $pendingMigrations = [];
    
    // Filter only PHP files and exclude . and .. and already applied migrations
    foreach ($migrationFiles as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'php' && !in_array($file, $appliedMigrations)) {
            $pendingMigrations[] = $file;
        }
    }
    
    // If no migrations found, exit
    if (empty($pendingMigrations)) {
        echo "No pending migrations found.\n";
        exit(0);
    }
    
    // Get the ordered migrations
    $orderedMigrations = getOrderedMigrations($pendingMigrations);
    
    echo "Running migrations...\n";
    
    // Get the latest batch number
    $batch = Capsule::table('migrations')->max('batch');
    $batch = $batch ? $batch + 1 : 1;
    
    // Execute each migration
    foreach ($orderedMigrations as $migrationFile) {
        try {
            echo "Migrating: {$migrationFile}\n";
            
            // Get the class name from the file name
            $className = getMigrationClassName($migrationFile);
            
            // Include the migration file
            require_once "{$migrationsPath}/{$migrationFile}";
            
            // Instantiate the migration class
            $migration = new $className();
            
            // Run the migration
            $migration->up();
            
            // Record the migration in the migrations table
            Capsule::table('migrations')->insert([
                'migration' => $migrationFile,
                'batch' => $batch
            ]);
            
            echo "Migrated:  {$migrationFile}\n";
        } catch (Exception $e) {
            echo "Error migrating {$migrationFile}: " . $e->getMessage() . "\n";
            echo "Migration failed. You might need to run with --fresh to reset the database.\n";
            exit(1);
        }
    }
    
    echo "All migrations completed successfully.\n";
}

/**
 * Creates the migrations table if it doesn't exist
 */
function createMigrationsTable() {
    if (!Capsule::schema()->hasTable('migrations')) {
        Capsule::schema()->create('migrations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('migration');
            $table->integer('batch');
            $table->timestamp('run_at')->useCurrent();
        });
        
        echo "Created migrations table.\n";
    }
}

/**
 * Get ordered migrations based on dependencies
 */
function getOrderedMigrations($pendingMigrations) {
    // Dependency order - this defines the correct order of execution
    $dependencyOrder = [
        'create_users_table.php',
        'create_stocks_table.php',
        'create_predictions_table.php',
        'create_prediction_votes_table.php',
        'create_stock_prices_table.php',
        'create_search_history_table.php',
        'create_saved_searches_table.php'
    ];
    
    // Reorder pending migrations based on dependency order
    $orderedMigrations = [];
    foreach ($dependencyOrder as $migrationName) {
        if (in_array($migrationName, $pendingMigrations)) {
            $orderedMigrations[] = $migrationName;
            $key = array_search($migrationName, $pendingMigrations);
            unset($pendingMigrations[$key]);
        }
    }
    
    // Add any remaining migrations not explicitly ordered
    $orderedMigrations = array_merge($orderedMigrations, $pendingMigrations);
    
    return $orderedMigrations;
}

/**
 * Get migration class name from file name
 */
function getMigrationClassName($migrationFile) {
    $className = str_replace('.php', '', $migrationFile);
    $className = str_replace(' ', '', ucwords(str_replace('_', ' ', $className)));
    return $className;
}

/**
 * Handle fresh install - drops all existing tables and runs migrations
 */
function handleFreshInstall() {
    echo "Dropping all tables...\n";
    
    // Get all existing tables
    $tables = Capsule::select("SHOW TABLES");
    $databaseName = Capsule::connection()->getDatabaseName();
    
    // Disable foreign key checks temporarily
    Capsule::statement('SET FOREIGN_KEY_CHECKS=0');
    
    // Drop each table
    foreach ($tables as $table) {
        $tableName = "Tables_in_{$databaseName}";
        $dropTable = $table->$tableName;
        Capsule::schema()->drop($dropTable);
        echo "Dropped table: {$dropTable}\n";
    }
    
    // Re-enable foreign key checks
    Capsule::statement('SET FOREIGN_KEY_CHECKS=1');
    
    // Now run migrations fresh
    runMigrations();
}

/**
 * Rollback the last batch of migrations
 */
function rollback() {
    global $migrationsPath;
    
    // Check if migrations table exists
    if (!Capsule::schema()->hasTable('migrations')) {
        echo "Migrations table doesn't exist. Nothing to rollback.\n";
        return;
    }
    
    $batch = Capsule::table('migrations')->max('batch');
    
    if (!$batch) {
        echo "Nothing to rollback.\n";
        return;
    }
    
    $migrations = Capsule::table('migrations')
        ->where('batch', $batch)
        ->orderBy('id', 'desc')
        ->get();
    
    if ($migrations->isEmpty()) {
        echo "No migrations found in batch {$batch}.\n";
        return;
    }
    
    echo "Rolling back batch {$batch}...\n";
    
    // Disable foreign key checks temporarily
    Capsule::statement('SET FOREIGN_KEY_CHECKS=0');
    
    foreach ($migrations as $migration) {
        try {
            echo "Rolling back: {$migration->migration}\n";
            
            // Get the class name from the file name
            $className = getMigrationClassName($migration->migration);
            
            // Include the migration file
            require_once "{$migrationsPath}/{$migration->migration}";
            
            // Instantiate the migration class and run down()
            $migrationInstance = new $className();
            $migrationInstance->down();
            
            // Remove from migrations table
            Capsule::table('migrations')->where('id', $migration->id)->delete();
            
            echo "Rolled back: {$migration->migration}\n";
        } catch (Exception $e) {
            echo "Error rolling back {$migration->migration}: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    // Re-enable foreign key checks
    Capsule::statement('SET FOREIGN_KEY_CHECKS=1');
    
    echo "Rollback completed successfully.\n";
}

/**
 * Print usage information
 */
function printUsage() {
    echo "SoVest Migration Runner\n";
    echo "----------------------\n";
    echo "Usage: php migrate.php [options]\n\n";
    echo "Options:\n";
    echo "  --fresh     Drop all tables and run all migrations\n";
    echo "  --rollback  Rollback the last batch of migrations\n";
    echo "\n";
}