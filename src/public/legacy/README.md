# Legacy Database Files

This directory contains SQL schema files and PHP applier scripts that were used for database management before the project was migrated to Laravel migrations.

## Why These Files Were Moved

These files have been moved to this legacy directory because the project now uses Laravel's migration system instead of raw SQL files for database schema management. The Laravel migration system provides several advantages:

- Version control for database changes
- Easier rollback capabilities
- Database agnostic schema definitions
- Better integration with the Eloquent ORM system
- Automated testing support

## SQL Files

The following SQL files were used for database schema definition:

- **db_schema_update.sql**: Contains core table definitions including users, stocks, predictions, prediction_votes, and stock_prices tables.
- **search_schema_update.sql**: Contains search-related table definitions for search_history and saved_searches tables.
- **users_migration.sql**: Contains SQL for migrating data from legacy user tables to the new structure.

## PHP Applier Files

These PHP files were used to execute the SQL scripts:

- **apply_db_schema.php**: Applies the core database schema from db_schema_update.sql.
- **apply_search_schema.php**: Applies the search-related schema from search_schema_update.sql.
- **apply_users_migration.php**: Executes the user migration script from users_migration.sql.

## New Laravel Migrations

The functionality of these legacy SQL files has been replaced by Laravel migration files, which can be found in:

```
./SoVest_code/database/migrations/
```

The migration files include:
- create_users_table.php
- create_predictions_table.php
- create_stocks_table.php
- create_stock_prices_table.php
- create_prediction_votes_table.php
- create_saved_searches_table.php
- create_search_history_table.php

## Documentation Updates

All documentation references to these legacy files have been updated to reflect the new Laravel migration system. This includes:

- README_DATABASE_UPDATE.md
- README_SEARCH_UPDATE.md
- docs/installation.md
- verify_db_schema.php
- predictions_schema_update.php

Please use the Laravel migration system for all future database schema changes instead of modifying these legacy files.