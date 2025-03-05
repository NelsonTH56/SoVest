<?php
/**
 * SoVest - Database Schema Application
 * 
 * This script applies the database schema using Laravel migration classes
 * Replaces the previous approach that used raw SQL from db_schema_update.sql
 * Maintains backward compatibility with npedigoUser table
 */

// Include the Laravel Eloquent setup
require_once __DIR__ . '/bootstrap/database.php';
require_once __DIR__ . '/includes/db_config.php';

// Import required namespaces
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

// Import migration classes
require_once __DIR__ . '/database/migrations/create_users_table.php';
require_once __DIR__ . '/database/migrations/create_stocks_table.php';
require_once __DIR__ . '/database/migrations/create_predictions_table.php';
require_once __DIR__ . '/database/migrations/create_stock_prices_table.php';
require_once __DIR__ . '/database/migrations/create_prediction_votes_table.php';
require_once __DIR__ . '/database/migrations/create_saved_searches_table.php';
require_once __DIR__ . '/database/migrations/create_search_history_table.php';

// Display an initial message
echo "<h2>Applying database schema updates...</h2>";

try {
    // Get database connection using centralized configuration
    $conn = connect_to_db();
    echo "Connected to database successfully.<br>";
    
    // Get the database name from centralized configuration
    $dbname = DB_NAME;

    // Check if npedigoUser table exists (for backward compatibility)
    $result = $conn->query("SHOW TABLES LIKE 'npedigoUser'");
    $hasLegacyTable = $result->num_rows > 0;
    
    // Run migrations in the correct order
    echo "Running migrations...<br>";
    
    // Users migration
    $usersMigration = new CreateUsersTable();
    $usersMigration->up();
    echo "Users table migration applied.<br>";
    
    // Stocks migration
    $stocksMigration = new CreateStocksTable();
    $stocksMigration->up();
    echo "Stocks table migration applied.<br>";
    
    // Predictions migration
    $predictionsMigration = new CreatePredictionsTable();
    $predictionsMigration->up();
    echo "Predictions table migration applied.<br>";
    
    // Stock prices migration
    $stockPricesMigration = new CreateStockPricesTable();
    $stockPricesMigration->up();
    echo "Stock prices table migration applied.<br>";
    
    // Prediction votes migration
    $predictionVotesMigration = new CreatePredictionVotesTable();
    $predictionVotesMigration->up();
    echo "Prediction votes table migration applied.<br>";
    
    // Optional saved searches migration
    if (class_exists('CreateSavedSearchesTable')) {
        $savedSearchesMigration = new CreateSavedSearchesTable();
        $savedSearchesMigration->up();
        echo "Saved searches table migration applied.<br>";
    }
    
    // Optional search history migration
    if (class_exists('CreateSearchHistoryTable')) {
        $searchHistoryMigration = new CreateSearchHistoryTable();
        $searchHistoryMigration->up();
        echo "Search history table migration applied.<br>";
    }
    
    // Check if we need to add or verify the reputation_score column in users table
    // This handles the case if the column wasn't created in the migration
    if (!columnExists('users', 'reputation_score')) {
        echo "Adding reputation_score column to users table...<br>";
        
        Capsule::schema()->table('users', function (Blueprint $table) {
            $table->integer('reputation_score')->default(0);
        });
        
        echo "Reputation score column added to users table.<br>";
    } else {
        echo "Reputation score column already exists in users table.<br>";
    }
    
    // For backward compatibility, check if npedigoUser table exists and add reputation_score if needed
    if ($hasLegacyTable) {
        if (!columnExists('npedigoUser', 'reputation_score')) {
            echo "Adding reputation_score column to npedigoUser table for backward compatibility.<br>";
            
            Capsule::schema()->table('npedigoUser', function (Blueprint $table) {
                $table->integer('reputation_score')->default(0);
            });
            
            echo "Reputation score column added to npedigoUser table.<br>";
        } else {
            echo "Reputation score column already exists in npedigoUser table.<br>";
        }
    }
    
    // Create migrations version tracking table
    if (!Capsule::schema()->hasTable('migrations_log')) {
        Capsule::schema()->create('migrations_log', function (Blueprint $table) {
            $table->increments('id');
            $table->string('version');
            $table->timestamp('applied_at')->useCurrent();
        });
        
        echo "Migrations log table created.<br>";
    }
    
    // Record this migration
    Capsule::table('migrations_log')->insert([
        'version' => '1.0',
        'applied_at' => date('Y-m-d H:i:s')
    ]);
    
    echo "Migration version recorded.<br>";
    echo "<div style='color: green; font-weight: bold;'>Database schema update completed successfully!</div>";
    
} catch (Exception $e) {
    // Handle any exceptions during the process
    echo "<div style='color: red; font-weight: bold;'>Error: " . $e->getMessage() . "</div>";
    error_log("Database schema application error: " . $e->getMessage());
}

/**
 * Check if a column exists in a table
 * 
 * @param string $table The table name
 * @param string $column The column name
 * @return bool True if the column exists, false otherwise
 */
function columnExists($table, $column) {
    global $dbname;
    $result = Capsule::select("SELECT COUNT(*) as count FROM information_schema.columns 
                            WHERE table_schema = '$dbname' 
                            AND table_name = '$table' 
                            AND column_name = '$column'");
    return $result[0]->count > 0;
}
?>