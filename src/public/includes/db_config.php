<?php
/**
 * SoVest - Database Configuration
 * 
 * This file contains the centralized database configuration settings.
 * Now loads database credentials from .env file instead of hardcoded values.
 * It also provides backward compatibility with legacy code while leveraging
 * the modern DatabaseService when available.
 * 
 * Environment Variables Required in .env:
 * - DB_SERVER: Database server hostname
 * - DB_USERNAME: Database username
 * - DB_PASSWORD: Database password
 * - DB_NAME: Database name
 */

// Autoload Composer dependencies if available
if (file_exists(dirname(dirname(__DIR__)) . '/vendor/autoload.php')) {
    require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';
}

// Import DatabaseService
use Services\DatabaseService;

// Load environment variables from .env file
function loadEnvVariables() {
    $envFile = dirname(dirname(dirname(__FILE__))) . '/.env';
    
    if (!file_exists($envFile)) {
        error_log("Error: .env file not found at {$envFile}");
        throw new Exception("Environment configuration not found. Please contact administrator.");
    }
    
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        error_log("Error: Unable to read .env file");
        throw new Exception("Environment configuration could not be loaded. Please contact administrator.");
    }
    
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse line as key=value
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        // Set as environment variable
        if (!putenv("{$name}={$value}")) {
            error_log("Error: Unable to set environment variable {$name}");
        }
    }
}

// Load environment variables
try {
    loadEnvVariables();
} catch (Exception $e) {
    error_log($e->getMessage());
    // Still define defaults for backward compatibility
    define('DB_SERVER', 'localhost');
    define('DB_USERNAME', 'sovest_user');
    define('DB_PASSWORD', 'sovest_is_dope');
    define('DB_NAME', 'sovest');
    // Don't throw the exception as it might break existing code
}

// Helper function to get environment variable with validation
function getEnvVar($name, $default = null) {
    $value = getenv($name);
    if ($value === false) {
        if ($default === null) {
            error_log("Error: Required environment variable {$name} not found");
            throw new Exception("Database configuration error. Please contact administrator.");
        }
        return $default;
    }
    return $value;
}

// Define database constants from environment variables with defaults for backward compatibility
try {
    define('DB_SERVER', getEnvVar('DB_SERVER', 'localhost'));
    define('DB_USERNAME', getEnvVar('DB_USERNAME', 'sovest_user'));
    define('DB_PASSWORD', getEnvVar('DB_PASSWORD', 'sovest_is_dope'));
    define('DB_NAME', getEnvVar('DB_NAME', 'sovest'));
} catch (Exception $e) {
    error_log($e->getMessage());
    // Don't throw the exception as it might break existing code
    // Constants were already defined with defaults above
}

/**
 * Initialize Eloquent if not already initialized
 * 
 * @return bool Success status
 */
function initializeEloquent() {
    static $initialized = false;
    
    if ($initialized) {
        return true;
    }
    
    try {
        if (!class_exists('Illuminate\Database\Capsule\Manager')) {
            return false;
        }
        
        if (!file_exists(dirname(__DIR__) . '/bootstrap/database.php')) {
            return false;
        }
        
        require_once dirname(__DIR__) . '/bootstrap/database.php';
        $initialized = true;
        return true;
    } catch (Exception $e) {
        error_log("Failed to initialize Eloquent: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if DatabaseService is available
 * 
 * @return bool Whether DatabaseService is available
 */
function isDatabaseServiceAvailable() {
    return class_exists('Services\DatabaseService') && initializeEloquent();
}

/**
 * Get a database connection
 * 
 * Uses DatabaseService if available, falls back to mysqli
 * 
 * @return mysqli|PDO Database connection object
 * @throws Exception If connection fails
 */
function getDbConnection() {
    static $conn = null;
    
    // If we already have a connection, return it
    if ($conn !== null) {
        return $conn;
    }
    
    // Try to use DatabaseService if available
    if (isDatabaseServiceAvailable()) {
        try {
            $dbService = DatabaseService::getInstance();
            $conn = $dbService->getConnection();
            return $conn;
        } catch (Exception $e) {
            error_log("DatabaseService connection failed, falling back to mysqli: " . $e->getMessage());
            // Fall back to mysqli if DatabaseService fails
        }
    }
    
    // Create new mysqli connection as fallback
    $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    // Check connection
    if (!$conn) {
        // Log the error
        error_log("Database connection failed: " . mysqli_connect_error());
        
        // Throw exception with sanitized message for security
        throw new Exception("Database connection failed. Please try again later.");
    }
    
    return $conn;
}

/**
 * Close the database connection
 * 
 * @param mysqli|PDO $conn Database connection to close
 * @return void
 */
function closeDbConnection($conn) {
    if ($conn instanceof \mysqli) {
        mysqli_close($conn);
    }
    // PDO connections from DatabaseService don't need to be manually closed
}

/**
 * Sanitize input for database queries
 * 
 * Note: It's recommended to use parameter binding with prepared statements instead 
 * of this function for better security. This is maintained for backward compatibility.
 * 
 * @param string $data Data to sanitize
 * @param mysqli|PDO $conn Database connection for escaping
 * @return string Sanitized data
 */
function sanitizeDbInput($data, $conn = null) {
    if ($conn === null) {
        $conn = getDbConnection();
    }
    
    // Check if we're using DatabaseService's PDO connection
    if ($conn instanceof \PDO) {
        // Remove quotes added by PDO::quote
        return trim(substr($conn->quote(trim($data)), 1, -1));
    }
    
    // Otherwise use mysqli
    return mysqli_real_escape_string($conn, trim($data));
}

/**
 * Execute a database query with error handling
 * 
 * Uses DatabaseService if available, falls back to mysqli
 * 
 * @param string $sql SQL query to execute
 * @param mixed $conn Database connection or array of bindings when using DatabaseService
 * @return mixed Query result (depends on query type and connection type)
 * @throws Exception If query fails
 */
function executeQuery($sql, $conn = null) {
    // If $conn is an array, it contains bindings for DatabaseService
    $bindings = [];
    if (is_array($conn)) {
        $bindings = $conn;
        $conn = null;
    }
    
    // Try to use DatabaseService if available and no specific connection was provided
    if ($conn === null && isDatabaseServiceAvailable()) {
        try {
            $dbService = DatabaseService::getInstance();
            return $dbService->executeQuery($sql, $bindings);
        } catch (Exception $e) {
            error_log("DatabaseService query failed, falling back to mysqli: " . $e->getMessage());
            // Fall back to mysqli if DatabaseService fails
        }
    }
    
    // Ensure we have a connection
    if ($conn === null) {
        $conn = getDbConnection();
    }
    
    // If not using DatabaseService, execute with mysqli
    if ($conn instanceof \mysqli) {
        $result = mysqli_query($conn, $sql);
        
        if ($result === false) {
            // Log the error
            error_log("Database query failed: " . mysqli_error($conn) . " - Query: " . $sql);
            
            // Throw exception with sanitized message for security
            throw new Exception("Database operation failed. Please try again later.");
        }
        
        return $result;
    }
    
    // If we have a PDO connection but not through DatabaseService
    if ($conn instanceof \PDO) {
        try {
            $stmt = $conn->query($sql);
            
            if ($stmt === false) {
                throw new Exception("Query execution failed");
            }
            
            // For SELECT queries, fetch all results
            if (stripos(trim($sql), 'SELECT') === 0) {
                return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }
            
            return $stmt;
        } catch (Exception $e) {
            error_log("Database query failed: " . $e->getMessage() . " - Query: " . $sql);
            throw new Exception("Database operation failed. Please try again later.");
        }
    }
    
    throw new Exception("Invalid database connection provided");
}

/**
 * Get a model instance by its class name
 * 
 * @param string $modelName Model class name (without namespace)
 * @return mixed Model instance or null if not available
 */
function getModel($modelName) {
    if (!isDatabaseServiceAvailable()) {
        return null;
    }
    
    $modelClass = "Database\\Models\\{$modelName}";
    
    if (class_exists($modelClass)) {
        return new $modelClass();
    }
    
    return null;
}
?>