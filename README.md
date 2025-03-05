# SoVest - Stock Prediction and Social Investment Platform

## Project Overview

SoVest is a PHP-based web application for stock predictions that allows users to create and share stock predictions while building reputation based on accuracy. The platform serves as a social investment community where users can track their prediction performance and follow top analysts.

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **ORM**: Laravel's Eloquent ORM (standalone implementation)
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap
- **API Integration**: Alpha Vantage API for stock data
- **Authentication**: Custom PHP authentication system
- **Dependencies**: Managed through Composer

## Installation

For detailed installation instructions, please see the [Installation Guide](/SoVest_code/docs/installation.md). The guide covers:

- System requirements
- Database setup
- Application configuration
- Web server configuration
- Cron jobs setup
- Security recommendations

## Project Structure

The SoVest application is organized as follows:

- **/SoVest_code**: Main application code
  - **/admin**: Administrative tools and dashboards
  - **/api**: RESTful API endpoints for data access
  - **/bootstrap**: Application initialization code
  - **/config**: Configuration files
  - **/cron**: Scheduled tasks for stock updates and prediction evaluation
  - **/css**: Stylesheets
  - **/database**: Database models, migrations, and seeders
  - **/docs**: Documentation files
  - **/includes**: Common include files and utilities
  - **/js**: JavaScript files
  - **/legacy**: Deprecated files (raw SQL files and old PHP scripts)
  - **/services**: Service classes for business logic

## Database Structure and Eloquent ORM Implementation

SoVest has recently undergone a significant update to modernize its data access layer by implementing Laravel's Eloquent ORM. This transition moves the project away from direct SQL queries and procedural database access to a more object-oriented, secure, and maintainable approach using Eloquent models, relationships, and migrations.

This update provides several benefits:
- More maintainable and readable code
- Better security through prepared statements and query building
- Simplified data access with model relationships
- Structured database schema management through migrations
- More robust error handling

### Models

The following Eloquent models have been created in the `database/models/` directory:

#### User
- Represents user accounts
- Fields: id, email, password, first_name, last_name, major, year, scholarship, reputation_score, created_at, updated_at
- Relationships:
  - `predictions()`: One-to-many relationship with Prediction
  - `votes()`: One-to-many relationship with PredictionVote
  - `searchHistory()`: One-to-many relationship with SearchHistory
  - `savedSearches()`: One-to-many relationship with SavedSearch

#### Stock
- Represents stock information
- Fields: stock_id, symbol, company_name, sector, created_at
- Relationships:
  - `predictions()`: One-to-many relationship with Prediction
  - `prices()`: One-to-many relationship with StockPrice

#### Prediction
- Represents stock predictions made by users
- Fields: prediction_id, user_id, stock_id, prediction_type, target_price, prediction_date, end_date, is_active, accuracy, reasoning
- Relationships:
  - `user()`: Belongs-to relationship with User
  - `stock()`: Belongs-to relationship with Stock
  - `votes()`: One-to-many relationship with PredictionVote

#### PredictionVote
- Represents votes on predictions
- Fields: vote_id, prediction_id, user_id, vote_type, vote_date
- Relationships:
  - `prediction()`: Belongs-to relationship with Prediction
  - `user()`: Belongs-to relationship with User

#### StockPrice
- Represents historical stock prices
- Fields: price_id, stock_id, price_date, open_price, close_price, high_price, low_price, volume
- Relationships:
  - `stock()`: Belongs-to relationship with Stock

#### SearchHistory
- Represents user search history
- Fields: id, user_id, search_query, search_type, created_at
- Relationships:
  - `user()`: Belongs-to relationship with User

#### SavedSearch
- Represents saved searches for users
- Fields: id, user_id, search_query, search_type, created_at
- Relationships:
  - `user()`: Belongs-to relationship with User

### Migration System

The project now uses a Laravel-inspired migration system for managing database schema changes. Migrations are stored in `database/migrations/` and follow a structured format for creating and modifying database tables.

Key features of the migration system:
- Tracks which migrations have been run in a database table
- Supports creating tables with appropriate columns, indexes, and foreign keys
- Allows rollback of migrations
- Executes migrations in the correct order based on dependencies

### Running Migrations

To run database migrations, use the `migrate.php` script in the root directory:

```bash
# Run pending migrations
php migrate.php

# Fresh install (drops all tables and runs all migrations)
php migrate.php --fresh

# Rollback the last batch of migrations
php migrate.php --rollback
```

The migration system will:
1. Check which migrations have already been run
2. Execute pending migrations in the correct order
3. Update the migrations table to track completed migrations

### Migrating Data from Legacy Tables

If you need to migrate data from the legacy database structure to the new Eloquent tables, use the `migrate_data.php` script:

```bash
# Test mode (simulates migration without making changes)
php migrate_data.php --test

# Perform actual data migration
php migrate_data.php
```

The data migration utility:
- Transfers data from legacy tables to the new Eloquent tables
- Maintains relationships between tables
- Provides detailed logging during the migration process
- Includes a test mode for verifying migration logic

## Development Workflow

1. **Setup Environment**: Clone the repository and follow the installation instructions
2. **Database Changes**: 
   - Create new migrations in the `database/migrations/` directory
   - Run migrations using `php migrate.php`
   - **Important**: Never use raw SQL directly; always use Eloquent ORM
3. **Code Changes**:
   - Update models in `database/models/` for any data structure changes
   - Use the existing service classes in `services/` for business logic
   - Follow the MVC pattern with controllers in the application root
4. **Testing**:
   - Write tests for any new functionality
   - Test database operations using `test_database_service.php`
   - Test API integrations using `test_integration.php`
5. **Deployment**:
   - Update the `.env` file for production settings
   - Run migrations on the production database
   - Set up cron jobs for automated tasks

## Additional Information for Developers

### Database Configuration

Eloquent ORM is configured in `bootstrap/database.php` which initializes the database connection using credentials from `.env` via `includes/db_config.php`. The configuration uses the Illuminate Database Capsule Manager for standalone Eloquent usage outside of a full Laravel application.

### Using Models in Your Code

When working with database operations, use Eloquent models instead of direct SQL queries:

```php
// Example: Finding a user by ID
$user = User::find($userId);

// Example: Creating a new stock
$stock = new Stock();
$stock->symbol = 'AAPL';
$stock->company_name = 'Apple Inc.';
$stock->sector = 'Technology';
$stock->save();

// Example: Retrieving predictions with related data
$predictions = Prediction::with(['user', 'stock'])->where('is_active', true)->get();
```

### Authentication System

The authentication system has been updated to use Eloquent models while maintaining backward compatibility with existing cookie-based authentication. The updated authentication functions can be found in `includes/auth.php`.

### API Operations

API endpoints have been updated to use Eloquent models instead of direct SQL queries. Refer to files in the `api/` directory for examples of Eloquent usage in API operations.

### Search Functionality

Search functionality now uses the SearchHistory and SavedSearch Eloquent models. Search operations can be found in `search.php` and `api/search.php`.

## Contact Information

For issues, suggestions, or contributions:

- **Project Maintainer**: [project maintainer email]
- **Support Email**: support@sovest.example.com
- **Bug Reports**: Please submit issues through our GitHub repository