<?php
/**
 * Test file for DatabaseService
 * 
 * This file demonstrates how to use the new DatabaseService class 
 * as a replacement for legacy mysqli functions.
 */

require 'vendor/autoload.php';
require 'services/DatabaseService.php';

use Services\DatabaseService;

// Get the DatabaseService instance
$dbService = DatabaseService::getInstance();

echo "Testing DatabaseService...\n";

try {
    // Test connection
    $pdo = $dbService->getConnection();
    echo "✓ Connection successful\n";
    
    // Test simple query - get count of users
    $userCount = $dbService->fetchOne("SELECT COUNT(*) as count FROM users");
    echo "✓ Query successful - User count: " . $userCount['count'] . "\n";
    
    // Test table query builder
    $firstUser = $dbService->table('users')->orderBy('id')->first();
    echo "✓ Query builder successful - First user ID: " . $firstUser->id . "\n";
    
    // Test transaction methods
    $dbService->beginTransaction();
    echo "✓ Transaction begin successful\n";
    
    $dbService->commitTransaction();
    echo "✓ Transaction commit successful\n";
    
    // Demonstrate using prepared statements with bindings
    $userId = 1; // Example user ID
    $userDetails = $dbService->fetchOne(
        "SELECT * FROM users WHERE id = ?", 
        [$userId]
    );
    
    if ($userDetails) {
        echo "✓ Prepared statement successful - Found user: " . $userDetails['username'] . "\n";
    } else {
        echo "✓ Prepared statement successful - User not found\n";
    }
    
    echo "\nAll tests completed successfully!\n";
    echo "DatabaseService is ready to use as a replacement for legacy mysqli functions.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}