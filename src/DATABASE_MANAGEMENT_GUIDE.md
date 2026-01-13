# Database Management Guide

## Your Database Connection Info

- **Database Type**: MySQL
- **Host**: 127.0.0.1 (localhost)
- **Port**: 3306
- **Database Name**: sovest
- **Username**: root
- **Password**: "#nEllyjElly56"

---

## Method 1: Using Helper Scripts (Easiest)

I've created three PHP scripts to help you manage your database:

### 1. List All Users
```bash
php list_users.php
```
Shows all users with ID, email, name, and creation date.

### 2. Delete a User
```bash
php delete_user.php <user_id>
```
Examples:
```bash
php delete_user.php 1              # Delete user with ID 1
php delete_user.php                # Shows list of users first
```

### 3. Run Custom SQL Queries
```bash
php db_console.php "SQL_QUERY_HERE"
```
Examples:
```bash
# View all users
php db_console.php "SELECT * FROM users"

# Delete a specific user
php db_console.php "DELETE FROM users WHERE id = 1"

# Update user email
php db_console.php "UPDATE users SET email = 'new@example.com' WHERE id = 1"

# View all predictions
php db_console.php "SELECT * FROM predictions"

# Count users
php db_console.php "SELECT COUNT(*) as total FROM users"
```

---

## Method 2: Using Laravel Artisan Commands

### Start Interactive Tinker Console
```bash
php artisan tinker
```

Once in Tinker, you can run:

```php
// List all users
User::all();

// Find user by ID
$user = User::find(1);

// Find user by email
$user = User::where('email', 'test@example.com')->first();

// Delete user by ID
User::find(1)->delete();

// Delete all users (careful!)
User::truncate();

// Count users
User::count();

// Get user with their predictions
$user = User::with('predictions')->find(1);

// Create a new user
User::create([
    'email' => 'newuser@example.com',
    'password' => bcrypt('password123'),
    'first_name' => 'John',
    'last_name' => 'Doe'
]);
```

Type `exit` to leave Tinker.

---

## Method 3: Using MySQL Command Line

### Connect to MySQL
```bash
mysql -u root -p"#nEllyjElly56" sovest
```

### Common MySQL Commands
```sql
-- Show all tables
SHOW TABLES;

-- View all users
SELECT * FROM users;

-- View specific columns
SELECT id, email, first_name, last_name FROM users;

-- Delete a user
DELETE FROM users WHERE id = 1;

-- Delete user by email
DELETE FROM users WHERE email = 'test@example.com';

-- Update user
UPDATE users SET first_name = 'New Name' WHERE id = 1;

-- Count users
SELECT COUNT(*) FROM users;

-- View user with their predictions count
SELECT u.id, u.email, COUNT(p.prediction_id) as predictions_count
FROM users u
LEFT JOIN predictions p ON u.id = p.user_id
GROUP BY u.id;

-- Delete all users (WARNING!)
TRUNCATE TABLE users;

-- Exit MySQL
EXIT;
```

---

## Method 4: Using MySQL Workbench (GUI Tool)

1. Download MySQL Workbench: https://dev.mysql.com/downloads/workbench/
2. Install and open MySQL Workbench
3. Create new connection:
   - Connection Name: SoVest Local
   - Hostname: 127.0.0.1
   - Port: 3306
   - Username: root
   - Password: "#nEllyjElly56"
4. Click "Test Connection" then "OK"
5. Double-click the connection to open
6. Select "sovest" database from left sidebar
7. Right-click "users" table → "Select Rows" to view users
8. Use SQL editor to run custom queries

---

## Method 5: Using phpMyAdmin (Web Interface)

If you have phpMyAdmin installed with your MySQL:
1. Open http://localhost/phpmyadmin in browser
2. Login:
   - Username: root
   - Password: "#nEllyjElly56"
3. Click "sovest" database on left
4. Click "users" table
5. Use "Browse", "Search", "Delete" tabs to manage users

---

## Method 6: Using DBeaver (Free Universal Tool)

1. Download DBeaver: https://dbeaver.io/download/
2. Install and open DBeaver
3. Click "New Database Connection"
4. Select "MySQL"
5. Fill in:
   - Host: localhost
   - Port: 3306
   - Database: sovest
   - Username: root
   - Password: "#nEllyjElly56"
6. Click "Test Connection" then "Finish"
7. Expand connection → sovest → Tables → users
8. Right-click → View Data

---

## Quick Reference: Delete Users

### Current Users in Your Database:
```
ID: 1 - test@example.com
ID: 2 - nthayslett@gmail.com
```

### To Delete User #1 (test@example.com):
```bash
# Using helper script
php delete_user.php 1

# Using Tinker
php artisan tinker
User::find(1)->delete();

# Using MySQL CLI
mysql -u root -p"#nEllyjElly56" sovest -e "DELETE FROM users WHERE id = 1;"

# Using db_console
php db_console.php "DELETE FROM users WHERE id = 1"
```

### To Delete User #2 (nthayslett@gmail.com):
```bash
php delete_user.php 2
```

---

## Database Tables in Your Project

- **users** - User accounts
- **predictions** - Stock predictions
- **stocks** - Stock information
- **stock_prices** - Historical stock prices
- **prediction_votes** - Votes on predictions
- **saved_searches** - User saved searches
- **search_history** - Search history
- **sessions** - User sessions
- **cache** - Application cache
- **jobs** - Background jobs queue

---

## Safety Tips

⚠️ **Before deleting users**, consider:
1. Backup your database: `mysqldump -u root -p"#nEllyjElly56" sovest > backup.sql`
2. Check if user has predictions: `SELECT COUNT(*) FROM predictions WHERE user_id = X;`
3. Deleting a user will cascade delete their:
   - Predictions
   - Votes
   - Search history
   - Saved searches

---

## Restore Database from Backup
```bash
mysql -u root -p"#nEllyjElly56" sovest < backup.sql
```

---

## Reset Entire Database (Start Fresh)
```bash
# Drop all tables and re-run migrations
php artisan migrate:fresh

# Drop all tables, re-run migrations, and seed with fake data
php artisan migrate:fresh --seed
```

---

**Need help?** Run any command with `--help` flag for more options!
