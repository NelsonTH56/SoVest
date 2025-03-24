<?php
/**
 * SoVest - Database Service
 *
 * This service provides a modern replacement for legacy mysqli functions
 * using Eloquent ORM while maintaining backward compatibility.
 *
 * It follows PSR standards and provides proper error handling
 */

namespace Services;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Exception;
use PDO;

class DatabaseService
{
    /**
     * @var DatabaseService|null Singleton instance of the service
     */
    private static $instance = null;

    /**
     * Get the singleton instance of DatabaseService
     *
     * @return DatabaseService
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Private constructor to enforce singleton pattern
     */
    private function __construct()
    {
        // Ensure Eloquent is booted
        if (!Capsule::getDatabaseManager()) {
            require_once dirname(__DIR__) . '/bootstrap/database.php';
        }
    }

    /**
     * Get a database connection (compatible with legacy getDbConnection)
     * This returns a PDO instance that can be used similarly to mysqli
     *
     * @return PDO Database connection object
     * @throws Exception If connection fails
     */
    public function getConnection()
    {
        try {
            return Capsule::connection()->getPdo();
        } catch (Exception $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed. Please try again later.");
        }
    }

    /**
     * Execute a raw database query with error handling
     * Compatible with legacy executeQuery function
     *
     * @param string $sql SQL query to execute
     * @param array $bindings Optional. Prepared statement bindings
     * @return mixed Query result (array for SELECT, bool for others)
     * @throws Exception If query fails
     */
    public function executeQuery($sql, array $bindings = [])
    {
        try {
            // Determine if this is a SELECT query to decide return format
            $isSelect = stripos(trim($sql), 'SELECT') === 0;
            
            if ($isSelect) {
                return Capsule::select($sql, $bindings);
            } else {
                return Capsule::statement($sql, $bindings);
            }
        } catch (QueryException $e) {
            error_log("Database query failed: " . $e->getMessage() . " - Query: " . $sql);
            throw new Exception("Database operation failed. Please try again later.");
        }
    }

    /**
     * Sanitize input for database queries (using bindings instead is preferred)
     *
     * @param string $data Data to sanitize
     * @return string Sanitized data
     */
    public function sanitizeInput($data)
    {
        return Capsule::connection()->getPdo()->quote(trim($data));
    }

    /**
     * Get a query builder instance for a table
     *
     * @param string $table Table name
     * @return Builder Query builder instance
     */
    public function table($table)
    {
        return Capsule::table($table);
    }

    /**
     * Insert records into a table
     *
     * @param string $table Table name
     * @param array $data Data to insert (associative array)
     * @return int|bool Last insert ID or false
     */
    public function insert($table, array $data)
    {
        try {
            return Capsule::table($table)->insertGetId($data);
        } catch (QueryException $e) {
            error_log("Database insert failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update records in a table
     *
     * @param string $table Table name
     * @param array $data Data to update (associative array)
     * @param array $conditions Where conditions (associative array)
     * @return int Number of affected rows
     */
    public function update($table, array $data, array $conditions)
    {
        try {
            $query = Capsule::table($table);
            
            foreach ($conditions as $column => $value) {
                $query->where($column, $value);
            }
            
            return $query->update($data);
        } catch (QueryException $e) {
            error_log("Database update failed: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Delete records from a table
     *
     * @param string $table Table name
     * @param array $conditions Where conditions (associative array)
     * @return int Number of affected rows
     */
    public function delete($table, array $conditions)
    {
        try {
            $query = Capsule::table($table);
            
            foreach ($conditions as $column => $value) {
                $query->where($column, $value);
            }
            
            return $query->delete();
        } catch (QueryException $e) {
            error_log("Database delete failed: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Fetch all results from a SELECT query
     *
     * @param string $sql SQL query to execute
     * @param array $bindings Optional. Prepared statement bindings
     * @return array Array of rows
     */
    public function fetchAll($sql, array $bindings = [])
    {
        try {
            return Capsule::select($sql, $bindings);
        } catch (QueryException $e) {
            error_log("Database fetch failed: " . $e->getMessage() . " - Query: " . $sql);
            return [];
        }
    }

    /**
     * Fetch a single row from a SELECT query
     *
     * @param string $sql SQL query to execute
     * @param array $bindings Optional. Prepared statement bindings
     * @return array|null Single row or null if not found
     */
    public function fetchOne($sql, array $bindings = [])
    {
        try {
            $results = Capsule::select($sql, $bindings);
            return !empty($results) ? (array)$results[0] : null;
        } catch (QueryException $e) {
            error_log("Database fetch failed: " . $e->getMessage() . " - Query: " . $sql);
            return null;
        }
    }

    /**
     * Begin a database transaction
     *
     * @return bool Success
     */
    public function beginTransaction()
    {
        try {
            Capsule::connection()->beginTransaction();
            return true;
        } catch (Exception $e) {
            error_log("Begin transaction failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Commit a database transaction
     *
     * @return bool Success
     */
    public function commitTransaction()
    {
        try {
            Capsule::connection()->commit();
            return true;
        } catch (Exception $e) {
            error_log("Commit transaction failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Rollback a database transaction
     *
     * @return bool Success
     */
    public function rollbackTransaction()
    {
        try {
            Capsule::connection()->rollBack();
            return true;
        } catch (Exception $e) {
            error_log("Rollback transaction failed: " . $e->getMessage());
            return false;
        }
    }
}