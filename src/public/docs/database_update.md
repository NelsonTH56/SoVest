# SoVest Database Schema Update

This document provides instructions for updating the SoVest database schema to support stock predictions, voting, and price tracking.

## Eloquent ORM Implementation

> **Important:** Eloquent ORM is now the preferred way to interact with the database. Raw SQL queries are deprecated and should be avoided in new code.

The SoVest application has been modernized to use Laravel's Eloquent ORM, which provides:
- Object-oriented access to database tables
- Simplified query building
- Automatic relationship management
- Better security through parameterized queries
- Improved code maintainability

### Common Eloquent Operations

Here are examples of common database operations using Eloquent:

**Retrieving records:**
```php
// Get all stocks
$stocks = \Database\Models\Stock::all();

// Find a specific stock by ID
$stock = \Database\Models\Stock::find($stockId);

// Find a stock by symbol
$stock = \Database\Models\Stock::where('symbol', 'AAPL')->first();
```

**Creating records:**
```php
// Create a new prediction
$prediction = new \Database\Models\Prediction;
$prediction->user_id = $userId;
$prediction->stock_id = $stockId;
$prediction->prediction_type = 'Bullish';
$prediction->target_price = 150.00;
$prediction->prediction_date = now();
$prediction->end_date = now()->addDays(30);
$prediction->reasoning = 'Strong earnings report expected';
$prediction->save();

// Or use create method with mass assignment
$prediction = \Database\Models\Prediction::create([
    'user_id' => $userId,
    'stock_id' => $stockId,
    'prediction_type' => 'Bullish',
    'target_price' => 150.00,
    'prediction_date' => now(),
    'end_date' => now()->addDays(30),
    'reasoning' => 'Strong earnings report expected'
]);
```

**Updating records:**
```php
// Update a prediction
$prediction = \Database\Models\Prediction::find($predictionId);
$prediction->is_active = false;
$prediction->accuracy = 85;
$prediction->save();
```

**Using relationships:**
```php
// Get all predictions for a stock
$stock = \Database\Models\Stock::find($stockId);
$predictions = $stock->predictions;

// Get a user's predictions
$user = \Database\Models\User::find($userId);
$userPredictions = $user->predictions;
```

## Schema Changes

The following changes are being applied to the database:

1. **New Tables:**
   - `stocks`: For storing stock information (symbol, company name, sector)
   - `predictions`: For storing user predictions about stocks
   - `prediction_votes`: For tracking votes on predictions
   - `stock_prices`: For tracking historical stock prices

2. **Table Modifications:**
   - Added `reputation_score` column to the `npedigoUser` table to track user reputation

## How to Apply the Changes

> **Note:** The legacy SQL-based method has been replaced with Laravel migrations.

### Laravel Migrations (Recommended)

Run the Laravel migrations to set up your database schema:

```bash
php artisan migrate
```

### Legacy Method (Deprecated)

If needed, the legacy SQL scripts are still available in the `legacy` directory:

1. Run the `legacy/apply_db_schema.php` script in your web browser or via PHP CLI:

   ```bash
   php legacy/apply_db_schema.php
   ```

2. Verify the changes were applied successfully by running:

   ```bash
   php verify_db_schema.php
   ```

## Database Schema Details

### stocks table
- `stock_id`: Unique identifier for each stock
- `symbol`: Stock ticker symbol (e.g., AAPL, MSFT)
- `company_name`: Full company name
- `sector`: Industry sector the company belongs to
- `created_at`: Timestamp when the stock was added to the system

### predictions table
- `prediction_id`: Unique identifier for each prediction
- `user_id`: User who made the prediction
- `stock_id`: Stock the prediction is about
- `prediction_type`: Either 'Bullish' (expecting price increase) or 'Bearish' (expecting price decrease)
- `target_price`: Expected price target
- `prediction_date`: When the prediction was made
- `end_date`: When the prediction expires
- `is_active`: Whether the prediction is still active
- `accuracy`: Calculated accuracy of the prediction (updated when the prediction ends)
- `reasoning`: User's explanation for the prediction

### prediction_votes table
- `vote_id`: Unique identifier for each vote
- `prediction_id`: Prediction being voted on
- `user_id`: User who cast the vote
- `vote_type`: Either 'upvote' or 'downvote'
- `vote_date`: When the vote was cast

### stock_prices table
- `price_id`: Unique identifier for each price record
- `stock_id`: Stock the price is for
- `price_date`: Date of the price data
- `open_price`: Opening price
- `close_price`: Closing price
- `high_price`: Highest price during the period
- `low_price`: Lowest price during the period
- `volume`: Trading volume

## User Reputation

The `reputation_score` column added to the `npedigoUser` table will be used to track user reputation based on the accuracy of their predictions.