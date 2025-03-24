# SoVest Search Functionality

This document provides instructions for setting up and configuring the search functionality in the SoVest application.

## Eloquent ORM Implementation

> **Important:** Eloquent ORM is now the preferred way to interact with the database, including for search functionality. Raw SQL queries are deprecated and should be avoided in new code.

The SoVest search system has been modernized to use Laravel's Eloquent ORM, which provides:
- Simplified database queries for search operations
- Model relationships for easy navigation between related data
- Improved security through query parameterization
- Better maintainability of search functionality

### Common Eloquent Search Operations

Here are examples of common search operations using Eloquent:

**Basic search across models:**
```php
// Search for stocks by name or symbol
$stocks = \Database\Models\Stock::where('symbol', 'like', "%{$searchTerm}%")
    ->orWhere('company_name', 'like', "%{$searchTerm}%")
    ->get();

// Search for predictions with specific criteria
$predictions = \Database\Models\Prediction::where('prediction_type', 'Bullish')
    ->where('target_price', '>', 100)
    ->get();
```

**Working with search history:**
```php
// Save a search to history
$searchHistory = new \Database\Models\SearchHistory;
$searchHistory->user_id = $userId;
$searchHistory->search_query = $searchTerm;
$searchHistory->search_date = now();
$searchHistory->save();

// Get user's search history
$user = \Database\Models\User::find($userId);
$searchHistory = $user->searchHistory()->orderBy('search_date', 'desc')->get();
```

**Working with saved searches:**
```php
// Save a search as favorite
$savedSearch = new \Database\Models\SavedSearch;
$savedSearch->user_id = $userId;
$savedSearch->search_query = $searchTerm;
$savedSearch->search_name = $searchName;
$savedSearch->created_at = now();
$savedSearch->save();

// Get user's saved searches
$user = \Database\Models\User::find($userId);
$savedSearches = $user->savedSearches;
```

## New Features

The search system includes the following features:

1. Comprehensive search across stocks, predictions, and users
2. Search bar in navigation accessible from all pages
3. Advanced search filters (symbol, prediction type, etc.)
4. Real-time search suggestions
5. Search history tracking
6. Saved searches functionality
7. Responsive search results display
8. Search result pagination

## Setup Instructions

### 1. Database Updates

> **Note:** The legacy SQL-based method has been replaced with Laravel migrations.

#### Using Laravel Migrations (Recommended)

Run the Laravel migrations to set up your search-related tables:

```bash
php artisan migrate
```

#### Legacy Method (Deprecated)

If needed, the legacy script is still available in the `legacy` directory:

```
php legacy/apply_search_schema.php
```

This will create two new tables:
- `search_history`: Tracks users' search queries
- `saved_searches`: Stores users' saved search queries

### 2. File Structure

The search functionality consists of the following files:

- `search.php` - Main search interface
- `api/search.php` - API endpoint for search operations
- `includes/search_bar.php` - Reusable search bar component
- `js/search.js` - JavaScript for search interactions
- `css/search.css` - Styling for search components
- ~~`search_schema_update.sql`~~ - Database schema updates (moved to legacy directory)
- ~~`apply_search_schema.php`~~ - Script to apply schema updates (moved to legacy directory)
- `database/migrations/*_create_search_history_table.php` - Laravel migration for search history
- `database/migrations/*_create_saved_searches_table.php` - Laravel migration for saved searches

### 3. Integration

The search bar has been integrated into these pages:
- home.php
- trending.php
- account.php

To add the search bar to additional pages, include the following code before the navigation section:

```php
<?php 
// Include search bar component
require_once __DIR__ . '/includes/search_bar.php'; 
// Add search functionality to the page
addSearchToNav();
?>
```

Then add this inside the navbar collapse div:

```php
<!-- Search bar in navigation -->
<?php echo renderSearchBar(); ?>
```

## Using the Search

### Basic Search
- Enter keywords in the search bar to find stocks, predictions, or users
- Use the filters to narrow down results by type or prediction direction
- Sort results by relevance, date, accuracy, or votes

### Advanced Features
- Real-time suggestions appear as you type
- Click the "Save This Search" button to add searches to your favorites
- View and manage your search history
- Click on saved searches to quickly re-run them

## Troubleshooting

If search suggestions aren't appearing:
1. Ensure JavaScript is enabled in your browser
2. Check browser console for errors
3. Verify the database tables were created successfully

For other issues, check the server error logs.