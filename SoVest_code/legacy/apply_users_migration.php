<?php
/**
 * SoVest - User Table Migration Script (Laravel/Eloquent version)
 * 
 * This script safely migrates data from the legacy 'npedigoUser' table to the new 'users' table
 * using Laravel migration classes and Eloquent ORM instead of raw SQL.
 * 
 * It handles the entire migration process including:
 * - Checking for the existence of necessary tables
 * - Creating a backup of the npedigoUser table
 * - Creating the users table if it doesn't exist
 * - Migrating data from npedigoUser to users
 * - Updating foreign key references in related tables
 * - Detailed reporting and logging of all migration steps
 * 
 * REQUIREMENTS:
 * - PHP 7.0 or higher
 * - MySQL 5.7 or higher
 * - Valid database credentials in .env file or db_config.php
 * - Appropriate database permissions (ALTER TABLE, CREATE TABLE, etc.)
 * 
 * USAGE:
 * 1. Standard mode: php apply_users_migration.php
 * 2. Test/dry-run mode: php apply_users_migration.php --test
 * 
 * SAFETY FEATURES:
 * - Transaction support to ensure atomic operations
 * - Backup table creation before migration
 * - Comprehensive error handling and rollback
 * - Test mode option to preview changes without committing
 */

// Set error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the Laravel Eloquent setup and other required files
require_once 'bootstrap/database.php';
require_once 'includes/db_config.php';
require_once 'database/migrations/create_users_table.php';

// Import required namespaces
use Illuminate\Database\Capsule\Manager as Capsule;
use Database\Models\User;
use Services\DatabaseService;

// Constants for the migration
define('LOG_FILE', dirname(__FILE__) . '/logs/migration_log.txt');
define('BACKUP_TABLE', 'npedigoUser_backup_' . date('Ymd_His'));
define('MIGRATION_VERSION', 'users_migration_1.0');

// Create logs directory if it doesn't exist
if (!file_exists(dirname(LOG_FILE))) {
    mkdir(dirname(LOG_FILE), 0755, true);
}

/**
 * Write a message to the migration log file
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
    return Capsule::schema()->hasTable($tableName);
}

/**
 * Count the number of rows in a table
 *
 * @param string $tableName Name of the table to count
 * @return int Number of rows in the table
 */
function countTableRows($tableName) {
    return Capsule::table($tableName)->count();
}

/**
 * Create a backup of the npedigoUser table
 *
 * @return bool True if backup was successful, false otherwise
 */
function createBackup() {
    try {
        if (tableExists(BACKUP_TABLE)) {
            logMigration("Backup table " . BACKUP_TABLE . " already exists, skipping backup creation", 'WARNING');
            return true;
        }
        
        // Create backup table structure
        Capsule::schema()->create(BACKUP_TABLE, function ($table) {
            // Get the structure of npedigoUser table
            $columns = Capsule::connection()->getDoctrineSchemaManager()->listTableColumns('npedigoUser');
            
            foreach ($columns as $column) {
                $type = $column->getType()->getName();
                $length = $column->getLength();
                $nullable = !$column->getNotnull();
                $default = $column->getDefault();
                $unsigned = $column->getUnsigned();
                
                // Add each column to the backup table
                $colDefinition = $table->addColumn(
                    $column->getName(),
                    $type,
                    ['length' => $length, 'nullable' => $nullable]
                );
                
                if ($default !== null) {
                    $colDefinition->default($default);
                }
                
                if ($unsigned) {
                    $colDefinition->unsigned();
                }
                
                if ($column->getName() === 'id' && $column->getAutoincrement()) {
                    $table->primary('id');
                }
            }
        });
        
        // Copy data from npedigoUser to backup table
        $data = Capsule::table('npedigoUser')->get();
        foreach ($data as $row) {
            Capsule::table(BACKUP_TABLE)->insert((array)$row);
        }
        
        logMigration("Created backup of npedigoUser table as " . BACKUP_TABLE);
        return true;
    } catch (Exception $e) {
        logMigration("Failed to create backup: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * Run the users table migration
 *
 * @return bool True if successful, false otherwise
 */
function runUserMigration() {
    try {
        $migration = new CreateUsersTable();
        $migration->up();
        return true;
    } catch (Exception $e) {
        logMigration("Failed to create users table: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * Migrate data from npedigoUser to users table
 *
 * @return bool True if successful, false otherwise
 */
function migrateUserData() {
    try {
        // Skip if users table already has data
        if (countTableRows('users') > 0) {
            logMigration("Users table already contains data. No migration performed.");
            return true;
        }
        
        // Check for column existence in npedigoUser
        $columns = Capsule::connection()->getDoctrineSchemaManager()->listTableColumns('npedigoUser');
        $hasFirstName = isset($columns['first_name']);
        $hasLastName = isset($columns['last_name']);
        
        // Get all user data from npedigoUser
        $users = Capsule::table('npedigoUser')->get();
        $insertCount = 0;
        
        foreach ($users as $user) {
            $userData = [
                'id' => $user->id,
                'email' => $user->email,
                'password' => $user->password,
                'major' => $user->major ?? null,
                'year' => $user->year ?? null,
                'scholarship' => $user->scholarship ?? null,
                'reputation_score' => $user->reputation_score ?? 0,
            ];
            
            // Add first_name and last_name if they exist
            if ($hasFirstName) {
                $userData['first_name'] = $user->first_name ?? null;
            }
            
            if ($hasLastName) {
                $userData['last_name'] = $user->last_name ?? null;
            }
            
            // Insert into users table
            User::create($userData);
            $insertCount++;
        }
        
        logMigration("Data migrated from npedigoUser to users table. Migrated $insertCount records.");
        return true;
    } catch (Exception $e) {
        logMigration("Failed to migrate user data: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * Update foreign key references in related tables
 *
 * @return array Results of foreign key updates
 */
function updateForeignKeyReferences() {
    $tables = ['predictions', 'prediction_votes', 'search_history', 'saved_searches'];
    $results = [];
    
    foreach ($tables as $table) {
        if (!tableExists($table)) {
            $results[$table] = "Table not found - skipping";
            continue;
        }
        
        try {
            // Check if the foreign key references npedigoUser
            $foreignKeys = Capsule::connection()->getDoctrineSchemaManager()->listTableForeignKeys($table);
            $needsUpdate = false;
            $constraintName = '';
            
            foreach ($foreignKeys as $fk) {
                if ($fk->getForeignTableName() === 'npedigoUser') {
                    $needsUpdate = true;
                    $constraintName = $fk->getName();
                    break;
                }
                
                if ($fk->getForeignTableName() === 'users') {
                    $needsUpdate = false;
                    break;
                }
            }
            
            if ($needsUpdate && !empty($constraintName)) {
                // Get the column name that references user_id
                $foreignKeyColumns = [];
                foreach ($foreignKeys as $fk) {
                    if ($fk->getName() === $constraintName) {
                        $foreignKeyColumns = $fk->getLocalColumns();
                        break;
                    }
                }
                
                if (!empty($foreignKeyColumns)) {
                    $userIdColumn = $foreignKeyColumns[0]; // Usually 'user_id'
                    
                    // Drop the existing foreign key
                    Capsule::schema()->table($table, function ($table) use ($constraintName) {
                        $table->dropForeign($constraintName);
                    });
                    
                    // Add the new foreign key to users table
                    Capsule::schema()->table($table, function ($table) use ($userIdColumn) {
                        $table->foreign($userIdColumn)
                              ->references('id')
                              ->on('users')
                              ->onDelete('cascade');
                    });
                    
                    $results[$table] = "OK - Updated foreign key to reference users table";
                } else {
                    $results[$table] = "ERROR - Could not identify foreign key columns";
                }
            } else if ($needsUpdate) {
                $results[$table] = "ERROR - Foreign key references npedigoUser but could not be updated";
            } else {
                $results[$table] = "OK - Already references users table correctly";
            }
        } catch (Exception $e) {
            $results[$table] = "ERROR - " . $e->getMessage();
        }
    }
    
    return $results;
}

/**
 * Verify the migration was successful by comparing data
 *
 * @return bool True if verification passed, false otherwise
 */
function verifyMigration() {
    // Skip verification if npedigoUser doesn't exist
    if (!tableExists('npedigoUser')) {
        logMigration("npedigoUser table doesn't exist, skipping verification");
        return true;
    }
    
    // Skip verification if users table doesn't exist
    if (!tableExists('users')) {
        logMigration("users table doesn't exist, verification failed", 'ERROR');
        return false;
    }
    
    // Count rows in both tables
    $npedigoUserCount = countTableRows('npedigoUser');
    $usersCount = countTableRows('users');
    
    if ($usersCount < $npedigoUserCount) {
        logMigration("Data verification failed: users table has fewer rows ($usersCount) than npedigoUser ($npedigoUserCount)", 'ERROR');
        return false;
    }
    
    logMigration("Data verification passed: npedigoUser has $npedigoUserCount rows, users has $usersCount rows");
    
    // Check a few sample records for data integrity
    try {
        $samples = Capsule::table('npedigoUser')
            ->select('id', 'email')
            ->limit(10)
            ->get();
        
        $mismatches = 0;
        foreach ($samples as $sample) {
            $user = User::find($sample->id);
            if (!$user || $user->email !== $sample->email) {
                logMigration("Data mismatch for user ID {$sample->id}: npedigoUser email '{$sample->email}' != users email '" . ($user ? $user->email : 'not found') . "'", 'ERROR');
                $mismatches++;
            }
        }
        
        if ($mismatches > 0) {
            logMigration("Data integrity check failed with $mismatches mismatches", 'ERROR');
            return false;
        }
        
        logMigration("Data integrity check passed");
        return true;
    } catch (Exception $e) {
        logMigration("Failed to verify data integrity: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * Record the migration version in the migrations_log table
 *
 * @return bool True if successful, false otherwise
 */
function recordMigrationVersion() {
    try {
        // Create migrations_log table if it doesn't exist
        if (!tableExists('migrations_log')) {
            Capsule::schema()->create('migrations_log', function ($table) {
                $table->increments('id');
                $table->string('version');
                $table->timestamp('applied_at')->useCurrent();
            });
            
            logMigration("Migrations log table created");
        }
        
        // Check if this migration version is already recorded
        $existingMigration = Capsule::table('migrations_log')
            ->where('version', MIGRATION_VERSION)
            ->first();
            
        if (!$existingMigration) {
            Capsule::table('migrations_log')->insert([
                'version' => MIGRATION_VERSION,
                'applied_at' => date('Y-m-d H:i:s')
            ]);
            logMigration("Migration version recorded");
        } else {
            logMigration("Migration version was already recorded");
        }
        
        return true;
    } catch (Exception $e) {
        logMigration("Failed to record migration version: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * Main migration function that orchestrates the entire process
 *
 * @param bool $testMode Whether to run in test mode
 * @return void
 */
function runMigration($testMode = false) {
    logMigration("Starting user table migration" . ($testMode ? " (TEST MODE)" : ""));
    
    try {
        // Check if migration is already applied
        if (tableExists('migrations_log') && 
            Capsule::table('migrations_log')->where('version', MIGRATION_VERSION)->exists()) {
            logMigration("This migration has already been applied.", 'WARNING');
            // Continue to perform verification only
        }
        
        // Start a transaction
        Capsule::connection()->beginTransaction();
        
        // 1. Check if tables exist
        $npedigoUserExists = tableExists('npedigoUser');
        $usersExists = tableExists('users');
        
        logMigration("npedigoUser table exists: " . ($npedigoUserExists ? "Yes" : "No"));
        logMigration("users table exists: " . ($usersExists ? "Yes" : "No"));
        
        // 2. Create backup if npedigoUser exists
        if ($npedigoUserExists) {
            if (!createBackup()) {
                throw new Exception("Failed to create backup of npedigoUser table");
            }
        } else {
            logMigration("npedigoUser table doesn't exist, skipping backup", 'WARNING');
        }
        
        // 3. Create users table if it doesn't exist
        if (!$usersExists) {
            if (!runUserMigration()) {
                throw new Exception("Failed to create users table");
            }
        } else {
            logMigration("Users table already exists, skipping creation");
        }
        
        // 4. Migrate data from npedigoUser to users
        if ($npedigoUserExists) {
            if (!migrateUserData()) {
                throw new Exception("Failed to migrate user data");
            }
        } else {
            logMigration("npedigoUser table doesn't exist, skipping data migration", 'WARNING');
        }
        
        // 5. Update foreign key references
        if ($npedigoUserExists) {
            $foreignKeyResults = updateForeignKeyReferences();
            logMigration("Foreign key reference update results:");
            foreach ($foreignKeyResults as $table => $result) {
                logMigration("  $table: $result");
            }
        } else {
            logMigration("npedigoUser table doesn't exist, skipping foreign key updates", 'WARNING');
        }
        
        // If we're in test mode, rollback the transaction
        if ($testMode) {
            logMigration("Test mode - rolling back transaction");
            Capsule::connection()->rollBack();
            return;
        } else {
            // Otherwise, commit the transaction
            Capsule::connection()->commit();
            
            // 6. Record the migration version (outside transaction to ensure it's recorded)
            recordMigrationVersion();
            
            // 7. Verify the migration was successful
            if (!verifyMigration()) {
                throw new Exception("Migration verification failed");
            }
            
            logMigration("User table migration completed successfully");
        }
        
    } catch (Exception $e) {
        // Rollback the transaction if there's an error
        if (Capsule::connection()->transactionLevel() > 0) {
            Capsule::connection()->rollBack();
        }
        
        logMigration("Migration failed: " . $e->getMessage(), 'ERROR');
        logMigration("Please check the migration log for details and resolve any issues before retrying.", 'ERROR');
    }
}

// Check if the script is being run in test mode
$testMode = false;
if (isset($argv) && count($argv) > 1) {
    $testMode = ($argv[1] === '--test');
}

// Run the migration
runMigration($testMode);
?>