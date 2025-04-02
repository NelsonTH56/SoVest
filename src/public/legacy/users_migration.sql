-- SoVest - Users Migration Script
-- This script creates a new users table and migrates data from npedigoUser
-- It's designed to be idempotent (safe to run multiple times)

-- Disable foreign key checks for the migration
SET FOREIGN_KEY_CHECKS = 0;

-- Create the new users table (if it doesn't exist already)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL, -- Sufficient length for hashed passwords
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    major VARCHAR(100),
    year VARCHAR(20),
    scholarship VARCHAR(50),
    reputation_score INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (email)
);

-- Check if npedigoUser table exists
SET @table_exists = 0;
SELECT COUNT(*) INTO @table_exists 
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
AND table_name = 'npedigoUser';

-- If npedigoUser exists, check its structure and migrate data
DROP PROCEDURE IF EXISTS MigrateUserData;
DELIMITER //
CREATE PROCEDURE MigrateUserData()
BEGIN
    -- Variables to track column existence
    DECLARE has_first_name INT DEFAULT 0;
    DECLARE has_last_name INT DEFAULT 0;
    
    -- Check if first_name column exists
    SELECT COUNT(*) INTO has_first_name 
    FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
    AND table_name = 'npedigoUser' 
    AND column_name = 'first_name';
    
    -- Check if last_name column exists
    SELECT COUNT(*) INTO has_last_name 
    FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
    AND table_name = 'npedigoUser' 
    AND column_name = 'last_name';
    
    -- If users table is empty and npedigoUser has data, migrate the data
    IF (SELECT COUNT(*) FROM users) = 0 THEN
        -- Insert data based on available columns
        IF has_first_name = 1 AND has_last_name = 1 THEN
            -- Case: npedigoUser has first_name and last_name columns
            INSERT INTO users (id, email, password, first_name, last_name, major, year, scholarship, reputation_score)
            SELECT id, email, password, first_name, last_name, major, year, scholarship, 
                   CASE WHEN reputation_score IS NULL THEN 0 ELSE reputation_score END
            FROM npedigoUser;
        ELSE
            -- Case: npedigoUser doesn't have first_name and last_name columns
            INSERT INTO users (id, email, password, major, year, scholarship, reputation_score)
            SELECT id, email, password, major, year, scholarship, 
                   CASE WHEN reputation_score IS NULL THEN 0 ELSE reputation_score END
            FROM npedigoUser;
        END IF;
        
        SELECT CONCAT('Data migrated from npedigoUser to users table. Migrated ', ROW_COUNT(), ' records.') AS Message;
    ELSE
        SELECT 'Users table already contains data. No migration performed.' AS Message;
    END IF;
END //
DELIMITER ;

-- Execute the migration procedure if npedigoUser exists
SET @users_count = 0;
SELECT COUNT(*) INTO @users_count FROM users;

-- Only run the migration if npedigoUser exists and users table is empty
SET @run_migration = 0;
SELECT IF(@table_exists > 0 AND @users_count = 0, 1, 0) INTO @run_migration;

-- Execute the migration if conditions are met
SET @migration_message = '';
SELECT IF(@run_migration = 1, 'Executing migration procedure...', 'Skipping migration procedure.') INTO @migration_message;
SELECT @migration_message AS Status;

-- Call the migration procedure conditionally
CALL IF(@run_migration = 1, 'MigrateUserData', 'SELECT ''Migration skipped'' AS Status');
IF @run_migration = 1 THEN
    CALL MigrateUserData();
END IF;

-- Drop the procedure as we don't need it anymore
DROP PROCEDURE IF EXISTS MigrateUserData;

-- Update foreign key references if npedigoUser exists
-- First, check if the tables that need updating exist

-- Check if predictions table exists
SET @predictions_exists = 0;
SELECT COUNT(*) INTO @predictions_exists 
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
AND table_name = 'predictions';

-- Check if prediction_votes table exists
SET @prediction_votes_exists = 0;
SELECT COUNT(*) INTO @prediction_votes_exists 
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
AND table_name = 'prediction_votes';

-- Check if search_history table exists
SET @search_history_exists = 0;
SELECT COUNT(*) INTO @search_history_exists 
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
AND table_name = 'search_history';

-- Check if saved_searches table exists
SET @saved_searches_exists = 0;
SELECT COUNT(*) INTO @saved_searches_exists 
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
AND table_name = 'saved_searches';

-- Update foreign key references in predictions table
DROP PROCEDURE IF EXISTS UpdatePredictionsForeignKeys;
DELIMITER //
CREATE PROCEDURE UpdatePredictionsForeignKeys()
BEGIN
    -- Check if the foreign key already references users table
    DECLARE constraint_exists INT DEFAULT 0;
    
    SELECT COUNT(*) INTO constraint_exists 
    FROM information_schema.KEY_COLUMN_USAGE 
    WHERE REFERENCED_TABLE_SCHEMA = DATABASE() 
    AND REFERENCED_TABLE_NAME = 'users'
    AND TABLE_NAME = 'predictions' 
    AND CONSTRAINT_NAME = 'predictions_ibfk_1';
    
    -- Drop existing foreign key constraint if it references npedigoUser
    IF constraint_exists = 0 THEN
        -- Check if the constraint exists for npedigoUser
        SELECT COUNT(*) INTO constraint_exists 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE REFERENCED_TABLE_SCHEMA = DATABASE() 
        AND REFERENCED_TABLE_NAME = 'npedigoUser'
        AND TABLE_NAME = 'predictions' 
        AND CONSTRAINT_NAME = 'predictions_ibfk_1';
        
        IF constraint_exists > 0 THEN
            -- Drop the foreign key
            ALTER TABLE predictions DROP FOREIGN KEY predictions_ibfk_1;
            
            -- Add foreign key reference to users table
            ALTER TABLE predictions 
            ADD CONSTRAINT predictions_ibfk_1 
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
            
            SELECT 'Updated foreign key reference in predictions table.' AS Message;
        ELSE
            SELECT 'No foreign key to update in predictions table.' AS Message;
        END IF;
    ELSE
        SELECT 'Foreign key in predictions table already references users table.' AS Message;
    END IF;
END //
DELIMITER ;

-- Update foreign key references in prediction_votes table
DROP PROCEDURE IF EXISTS UpdatePredictionVotesForeignKeys;
DELIMITER //
CREATE PROCEDURE UpdatePredictionVotesForeignKeys()
BEGIN
    -- Check if the foreign key already references users table
    DECLARE constraint_exists INT DEFAULT 0;
    
    SELECT COUNT(*) INTO constraint_exists 
    FROM information_schema.KEY_COLUMN_USAGE 
    WHERE REFERENCED_TABLE_SCHEMA = DATABASE() 
    AND REFERENCED_TABLE_NAME = 'users'
    AND TABLE_NAME = 'prediction_votes' 
    AND CONSTRAINT_NAME = 'prediction_votes_ibfk_2';
    
    -- Drop existing foreign key constraint if it references npedigoUser
    IF constraint_exists = 0 THEN
        -- Check if the constraint exists for npedigoUser
        SELECT COUNT(*) INTO constraint_exists 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE REFERENCED_TABLE_SCHEMA = DATABASE() 
        AND REFERENCED_TABLE_NAME = 'npedigoUser'
        AND TABLE_NAME = 'prediction_votes' 
        AND CONSTRAINT_NAME = 'prediction_votes_ibfk_2';
        
        IF constraint_exists > 0 THEN
            -- Drop the foreign key
            ALTER TABLE prediction_votes DROP FOREIGN KEY prediction_votes_ibfk_2;
            
            -- Add foreign key reference to users table
            ALTER TABLE prediction_votes 
            ADD CONSTRAINT prediction_votes_ibfk_2 
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
            
            SELECT 'Updated foreign key reference in prediction_votes table.' AS Message;
        ELSE
            SELECT 'No foreign key to update in prediction_votes table.' AS Message;
        END IF;
    ELSE
        SELECT 'Foreign key in prediction_votes table already references users table.' AS Message;
    END IF;
END //
DELIMITER ;

-- Update foreign key references in search_history table
DROP PROCEDURE IF EXISTS UpdateSearchHistoryForeignKeys;
DELIMITER //
CREATE PROCEDURE UpdateSearchHistoryForeignKeys()
BEGIN
    -- Check if the foreign key already references users table
    DECLARE constraint_exists INT DEFAULT 0;
    
    SELECT COUNT(*) INTO constraint_exists 
    FROM information_schema.KEY_COLUMN_USAGE 
    WHERE REFERENCED_TABLE_SCHEMA = DATABASE() 
    AND REFERENCED_TABLE_NAME = 'users'
    AND TABLE_NAME = 'search_history' 
    AND CONSTRAINT_NAME = 'search_history_ibfk_1';
    
    -- Drop existing foreign key constraint if it references npedigoUser
    IF constraint_exists = 0 THEN
        -- Check if the constraint exists for npedigoUser
        SELECT COUNT(*) INTO constraint_exists 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE REFERENCED_TABLE_SCHEMA = DATABASE() 
        AND REFERENCED_TABLE_NAME = 'npedigoUser'
        AND TABLE_NAME = 'search_history' 
        AND CONSTRAINT_NAME = 'search_history_ibfk_1';
        
        IF constraint_exists > 0 THEN
            -- Drop the foreign key
            ALTER TABLE search_history DROP FOREIGN KEY search_history_ibfk_1;
            
            -- Add foreign key reference to users table
            ALTER TABLE search_history 
            ADD CONSTRAINT search_history_ibfk_1 
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
            
            SELECT 'Updated foreign key reference in search_history table.' AS Message;
        ELSE
            SELECT 'No foreign key to update in search_history table.' AS Message;
        END IF;
    ELSE
        SELECT 'Foreign key in search_history table already references users table.' AS Message;
    END IF;
END //
DELIMITER ;

-- Update foreign key references in saved_searches table
DROP PROCEDURE IF EXISTS UpdateSavedSearchesForeignKeys;
DELIMITER //
CREATE PROCEDURE UpdateSavedSearchesForeignKeys()
BEGIN
    -- Check if the foreign key already references users table
    DECLARE constraint_exists INT DEFAULT 0;
    
    SELECT COUNT(*) INTO constraint_exists 
    FROM information_schema.KEY_COLUMN_USAGE 
    WHERE REFERENCED_TABLE_SCHEMA = DATABASE() 
    AND REFERENCED_TABLE_NAME = 'users'
    AND TABLE_NAME = 'saved_searches' 
    AND CONSTRAINT_NAME = 'saved_searches_ibfk_1';
    
    -- Drop existing foreign key constraint if it references npedigoUser
    IF constraint_exists = 0 THEN
        -- Check if the constraint exists for npedigoUser
        SELECT COUNT(*) INTO constraint_exists 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE REFERENCED_TABLE_SCHEMA = DATABASE() 
        AND REFERENCED_TABLE_NAME = 'npedigoUser'
        AND TABLE_NAME = 'saved_searches' 
        AND CONSTRAINT_NAME = 'saved_searches_ibfk_1';
        
        IF constraint_exists > 0 THEN
            -- Drop the foreign key
            ALTER TABLE saved_searches DROP FOREIGN KEY saved_searches_ibfk_1;
            
            -- Add foreign key reference to users table
            ALTER TABLE saved_searches 
            ADD CONSTRAINT saved_searches_ibfk_1 
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
            
            SELECT 'Updated foreign key reference in saved_searches table.' AS Message;
        ELSE
            SELECT 'No foreign key to update in saved_searches table.' AS Message;
        END IF;
    ELSE
        SELECT 'Foreign key in saved_searches table already references users table.' AS Message;
    END IF;
END //
DELIMITER ;

-- Execute the procedures conditionally based on table existence
IF @predictions_exists > 0 AND @table_exists > 0 THEN
    CALL UpdatePredictionsForeignKeys();
END IF;

IF @prediction_votes_exists > 0 AND @table_exists > 0 THEN
    CALL UpdatePredictionVotesForeignKeys();
END IF;

IF @search_history_exists > 0 AND @table_exists > 0 THEN
    CALL UpdateSearchHistoryForeignKeys();
END IF;

IF @saved_searches_exists > 0 AND @table_exists > 0 THEN
    CALL UpdateSavedSearchesForeignKeys();
END IF;

-- Clean up the procedures
DROP PROCEDURE IF EXISTS UpdatePredictionsForeignKeys;
DROP PROCEDURE IF EXISTS UpdatePredictionVotesForeignKeys;
DROP PROCEDURE IF EXISTS UpdateSearchHistoryForeignKeys;
DROP PROCEDURE IF EXISTS UpdateSavedSearchesForeignKeys;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Final message
SELECT 'Users migration completed successfully!' AS Status;