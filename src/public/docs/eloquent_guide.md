# Eloquent ORM Guide for SoVest

## Introduction

The SoVest application is transitioning from raw SQL queries to Laravel's Eloquent ORM (Object-Relational Mapper). Eloquent provides a clean, intuitive ActiveRecord implementation for working with the database, making database operations more intuitive and secure.

### Benefits of Eloquent ORM in SoVest

1. **Improved Code Readability**: Database operations are more intuitive with method chaining syntax
2. **Increased Security**: Automatic protection against SQL injection through prepared statements
3. **Simplified Relationships**: Easy handling of relationships between tables (users, stocks, predictions)
4. **Reduced Code Redundancy**: Less boilerplate code needed for common database operations
5. **Better Maintainability**: Separation of database logic from application logic
6. **Type Safety**: Strong typing and better IDE support through PHP objects

## Setup and Configuration

### Bootstrap Configuration

Eloquent is initialized in `bootstrap/database.php`, which sets up the database connection and bootstraps the ORM:

```php
<?php
    use Illuminate\Database\Capsule\Manager as Capsule;

    require __DIR__ . '/../vendor/autoload.php'; 

    require_once __DIR__ . '/../includes/db_config.php';

    $capsule = new Capsule;

    $capsule->addConnection([
        'driver'    => 'mysql',  
        'host'      => DB_SERVER,
        'database'  => DB_NAME,
        'username'  => DB_USERNAME,
        'password'  => DB_PASSWORD,
        'charset'   => 'utf8',
        'collation' => 'utf8_unicode_ci',
        'prefix'    => '',
    ]);

    $capsule->setAsGlobal();  // Makes database globally accessible
    $capsule->bootEloquent(); // Boots Eloquent ORM

    return $capsule;
```

### Database Service

The `DatabaseService` class provides an abstraction layer that helps transition from raw SQL to Eloquent. It offers methods compatible with both approaches:

```php
// Get the database service instance
$db = \Services\DatabaseService::getInstance();

// Using Eloquent-style methods
$predictions = $db->table('predictions')
    ->where('user_id', $userId)
    ->get();

// Using compatibility methods for legacy code
$sql = "SELECT * FROM predictions WHERE user_id = ?";
$results = $db->fetchAll($sql, [$userId]);
```

## Models Overview

All SoVest models are located in `database/models/` and follow a consistent structure.

### Key Models in SoVest

- **User**: Represents user accounts with authentication data
- **Stock**: Stores stock symbols and company information
- **Prediction**: User predictions about stock performance
- **PredictionVote**: Votes on predictions from other users
- **StockPrice**: Historical stock price data
- **SearchHistory**: User search history for stocks
- **SavedSearch**: Saved searches for future reference

### Model Structure Example

```php
<?php

namespace Database\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model {
    // Table name
    protected $table = 'users';

    // Primary key
    protected $primaryKey = 'id';

    // Timestamps are enabled in this table
    public $timestamps = true;

    // Allow mass assignment for these columns
    protected $fillable = [
        'email',
        'password',
        'first_name',
        'last_name',
        'major',
        'year',
        'scholarship',
        'reputation_score'
    ];

    // Relationships
    public function predictions()
    {
        return $this->hasMany(Prediction::class, 'user_id');
    }

    public function predictionVotes()
    {
        return $this->hasMany(PredictionVote::class, 'user_id');
    }

    // Other relationships...
}
```

### Key Model Properties

- **$table**: Specifies the database table name
- **$primaryKey**: Defines the primary key column (defaults to 'id')
- **$timestamps**: Enables/disables automatic timestamp handling
- **$fillable**: Lists columns that can be mass-assigned
- **$hidden**: Lists columns that should be hidden from array/JSON output
- **$casts**: Specifies how attributes should be cast to different types

## Relationships Between Models

Eloquent makes it easy to define and use relationships between tables.

### Types of Relationships in SoVest

1. **One-to-Many**: A user has many predictions
2. **Many-to-One (Belongs To)**: A prediction belongs to a user
3. **Many-to-Many**: Handled through intermediate tables

### Defining Relationships

```php
// User model - A user has many predictions
public function predictions()
{
    return $this->hasMany(Prediction::class, 'user_id');
}

// Prediction model - A prediction belongs to a user
public function user()
{
    return $this->belongsTo(User::class, 'user_id');
}
```

### Accessing Related Data

```php
// Get a user's predictions
$user = \Database\Models\User::find(1);
$predictions = $user->predictions;

// Get the user who made a prediction
$prediction = \Database\Models\Prediction::find(1);
$user = $prediction->user;

// Chain relationships
$stockPredictions = \Database\Models\Stock::find(1)->predictions;
```

## Common Database Operations

### Creating Records

```php
// Method 1: Create a new instance and save
$prediction = new \Database\Models\Prediction();
$prediction->user_id = $userId;
$prediction->stock_id = $stockId;
$prediction->prediction_type = 'bullish';
$prediction->target_price = 150.00;
$prediction->prediction_date = date('Y-m-d H:i:s');
$prediction->end_date = date('Y-m-d H:i:s', strtotime('+30 days'));
$prediction->is_active = 1;
$prediction->reasoning = 'Strong quarterly earnings expected';
$prediction->save();

// Method 2: Mass assignment using create()
$predictionData = [
    'user_id' => $userId,
    'stock_id' => $stockId,
    'prediction_type' => 'bullish',
    'target_price' => 150.00,
    'prediction_date' => date('Y-m-d H:i:s'),
    'end_date' => date('Y-m-d H:i:s', strtotime('+30 days')),
    'is_active' => 1,
    'reasoning' => 'Strong quarterly earnings expected'
];
$prediction = \Database\Models\Prediction::create($predictionData);
```

### Reading Records

```php
// Find a record by primary key
$prediction = \Database\Models\Prediction::find($predictionId);

// Get first record matching criteria
$activePrediction = \Database\Models\Prediction::where('user_id', $userId)
    ->where('is_active', 1)
    ->first();

// Get all records matching criteria
$userPredictions = \Database\Models\Prediction::where('user_id', $userId)
    ->orderBy('prediction_date', 'desc')
    ->get();

// Count records
$predictionCount = \Database\Models\Prediction::where('user_id', $userId)->count();
```

### Updating Records

```php
// Method 1: Find and update
$prediction = \Database\Models\Prediction::find($predictionId);
$prediction->target_price = 160.00;
$prediction->reasoning = 'Updated due to new market information';
$prediction->save();

// Method 2: Mass update
\Database\Models\Prediction::where('prediction_id', $predictionId)
    ->update([
        'target_price' => 160.00,
        'reasoning' => 'Updated due to new market information'
    ]);
```

### Deleting Records

```php
// Method 1: Find and delete
$prediction = \Database\Models\Prediction::find($predictionId);
$prediction->delete();

// Method 2: Delete by condition
\Database\Models\Prediction::where('prediction_id', $predictionId)->delete();

// Soft delete (if model uses SoftDeletes trait)
// This would set deleted_at instead of removing the record
$prediction->delete();
```

## Query Building

Eloquent provides a robust query builder for complex database operations.

### Basic Queries

```php
// Get all stocks
$allStocks = \Database\Models\Stock::all();

// Filter with where clause
$techStocks = \Database\Models\Stock::where('sector', 'Technology')->get();

// Limit and order results
$topStocks = \Database\Models\Stock::orderBy('created_at', 'desc')
    ->limit(10)
    ->get();
```

### Advanced Filtering

```php
// Multiple conditions
$predictions = \Database\Models\Prediction::where('user_id', $userId)
    ->where('prediction_type', 'bullish')
    ->where('is_active', 1)
    ->get();

// OR conditions
$predictions = \Database\Models\Prediction::where('user_id', $userId)
    ->where(function ($query) {
        $query->where('prediction_type', 'bullish')
            ->orWhere('prediction_type', 'bearish');
    })
    ->get();

// Date range conditions
$predictions = \Database\Models\Prediction::whereBetween('prediction_date', [$startDate, $endDate])
    ->get();
```

### Aggregations

```php
// Count
$totalPredictions = \Database\Models\Prediction::count();

// Sum
$totalBullishPredictions = \Database\Models\Prediction::where('prediction_type', 'bullish')->count();

// Average
$avgTargetPrice = \Database\Models\Prediction::where('stock_id', $stockId)
    ->avg('target_price');

// Group by with count
$predictionsByType = \Database\Models\Prediction::selectRaw('prediction_type, count(*) as count')
    ->groupBy('prediction_type')
    ->get();
```

### Raw Expressions

For complex queries that can't be expressed through Eloquent methods:

```php
// Using raw expressions in select
$predictions = \Database\Models\Prediction::selectRaw('prediction_id, target_price, DATEDIFF(end_date, prediction_date) as duration')
    ->where('user_id', $userId)
    ->get();

// Raw where conditions
$predictions = \Database\Models\Prediction::whereRaw('DATEDIFF(NOW(), prediction_date) < 30')
    ->get();
```

## Eager Loading Relationships

Eager loading helps avoid the N+1 query problem when working with relationships.

### Basic Eager Loading

```php
// Without eager loading (causes N+1 query problem)
$predictions = \Database\Models\Prediction::where('user_id', $userId)->get();
foreach ($predictions as $prediction) {
    // This will execute a new query for each prediction
    $stock = $prediction->stock;
    echo $stock->symbol;
}

// With eager loading (much more efficient)
$predictions = \Database\Models\Prediction::where('user_id', $userId)
    ->with('stock')
    ->get();
    
foreach ($predictions as $prediction) {
    // No additional query needed
    $stock = $prediction->stock;
    echo $stock->symbol;
}
```

### Multiple Relationship Loading

```php
// Load multiple relationships at once
$predictions = \Database\Models\Prediction::where('user_id', $userId)
    ->with(['stock', 'votes'])
    ->get();

// Nested relationships
$users = \Database\Models\User::with(['predictions.stock', 'predictions.votes'])
    ->get();
```

### Constraining Eager Loads

```php
// Only load active predictions
$users = \Database\Models\User::with(['predictions' => function ($query) {
    $query->where('is_active', 1);
}])->get();

// Only load recent votes
$predictions = \Database\Models\Prediction::with(['votes' => function ($query) {
    $query->where('created_at', '>=', now()->subDays(7));
}])->get();
```

## Migration System

Laravel migrations provide a version control system for your database schema.

### Migration Structure

SoVest's migrations are located in `database/migrations/` and follow this pattern:

```php
<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateUsersTable
{
    public function up()
    {
        if (!Capsule::schema()->hasTable('users')) {
            Capsule::schema()->create('users', function ($table) {
                $table->increments('id');
                $table->string('email', 255)->unique();
                $table->string('password', 255);
                $table->string('first_name', 100)->nullable();
                $table->string('last_name', 100)->nullable();
                // Other columns...
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        if (Capsule::schema()->hasTable('users')) {
            Capsule::schema()->drop('users');
        }
    }
}
```

### Creating a New Migration

To create a new migration, follow this pattern:

1. Create a file in `database/migrations/` named `create_table_name_table.php`
2. Use the schema builder methods to define your table structure
3. Implement both `up()` and `down()` methods

### Common Migration Methods

```php
// Column types
$table->string('name', 100);        // VARCHAR
$table->integer('age');             // INTEGER
$table->decimal('price', 8, 2);     // DECIMAL with precision and scale
$table->text('description');        // TEXT
$table->boolean('is_active');       // BOOLEAN
$table->date('birthday');           // DATE
$table->dateTime('published_at');   // DATETIME
$table->timestamps();               // created_at and updated_at TIMESTAMP

// Modifiers
$table->string('email')->unique();  // Add UNIQUE constraint
$table->integer('votes')->default(0); // Set default value
$table->string('name')->nullable(); // Allow NULL values

// Foreign keys
$table->foreignId('user_id')
    ->constrained()
    ->onDelete('cascade');
```

### Running Migrations

SoVest uses a custom migration runner:

```php
// Execute migrations
php migrate.php
```

## Comparison: Raw SQL vs. Eloquent

### Example 1: Fetching User Predictions

Raw SQL:
```php
$sql = "SELECT p.*, s.symbol, s.company_name 
        FROM predictions p 
        JOIN stocks s ON p.stock_id = s.stock_id 
        WHERE p.user_id = ? 
        ORDER BY p.prediction_date DESC";
$predictions = $db->fetchAll($sql, [$userId]);
```

Eloquent:
```php
$predictions = \Database\Models\Prediction::where('user_id', $userId)
    ->with('stock')
    ->orderBy('prediction_date', 'desc')
    ->get();
```

### Example 2: Creating a New Prediction

Raw SQL:
```php
$sql = "INSERT INTO predictions 
        (user_id, stock_id, prediction_type, target_price, prediction_date, end_date, is_active, reasoning) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$db->executeQuery($sql, [
    $userId, $stockId, $predictionType, $targetPrice, 
    date('Y-m-d H:i:s'), $endDate, 1, $reasoning
]);
```

Eloquent:
```php
\Database\Models\Prediction::create([
    'user_id' => $userId,
    'stock_id' => $stockId,
    'prediction_type' => $predictionType,
    'target_price' => $targetPrice,
    'prediction_date' => date('Y-m-d H:i:s'),
    'end_date' => $endDate,
    'is_active' => 1,
    'reasoning' => $reasoning
]);
```

### Example 3: Updating a Record

Raw SQL:
```php
$sql = "UPDATE predictions 
        SET target_price = ?, reasoning = ? 
        WHERE prediction_id = ? AND user_id = ?";
$db->executeQuery($sql, [$targetPrice, $reasoning, $predictionId, $userId]);
```

Eloquent:
```php
\Database\Models\Prediction::where('prediction_id', $predictionId)
    ->where('user_id', $userId)
    ->update([
        'target_price' => $targetPrice,
        'reasoning' => $reasoning
    ]);
```

## Best Practices for SoVest

### Use Models for Data Operations

Always prefer the Eloquent model approach for database operations:

```php
// Instead of raw SQL:
$sql = "SELECT * FROM users WHERE id = ?";
$user = $db->fetchOne($sql, [$userId]);

// Use Eloquent:
$user = \Database\Models\User::find($userId);
```

### Leverage Relationships

Use defined relationships instead of manual joins:

```php
// Instead of manually joining tables:
$sql = "SELECT p.*, s.symbol FROM predictions p JOIN stocks s ON p.stock_id = s.stock_id WHERE p.user_id = ?";
$predictions = $db->fetchAll($sql, [$userId]);

// Use relationships:
$user = \Database\Models\User::find($userId);
$predictions = $user->predictions()->with('stock')->get();
```

### Use DatabaseService for Compatibility

When working with legacy code, use DatabaseService as a bridge:

```php
$db = \Services\DatabaseService::getInstance();

// Eloquent-style table operations
$predictions = $db->table('predictions')
    ->where('user_id', $userId)
    ->orderBy('prediction_date', 'desc')
    ->get();
```

### Use Mass Assignment Safely

Always define `$fillable` or `$guarded` properties in your models to prevent mass assignment vulnerabilities:

```php
// In your model:
protected $fillable = ['name', 'email', 'password'];

// Then you can use:
User::create($request->all());
```

### Implement Proper Transaction Handling

For operations that modify multiple tables:

```php
try {
    DB::beginTransaction();
    
    // Create a prediction
    $prediction = \Database\Models\Prediction::create([...]);
    
    // Update user reputation
    $user = \Database\Models\User::find($userId);
    $user->reputation_score += 5;
    $user->save();
    
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    // Handle error
}
```

## Troubleshooting Common Issues

### Model Not Found

**Problem**: `Model not found` or class not found errors.

**Solution**: Check namespaces and ensure proper autoloading:

```php
// Correct:
use Database\Models\User;

// Or using fully qualified name:
$user = \Database\Models\User::find($id);
```

### SQL Errors

**Problem**: Encountering SQL syntax errors or constraint violations.

**Solution**: Check your query structure and parameters:

```php
// Debug query with toSql()
$query = \Database\Models\User::where('email', $email);
echo $query->toSql();
```

### N+1 Query Problem

**Problem**: Slow performance due to executing many separate queries.

**Solution**: Use eager loading with `with()`:

```php
// Instead of:
$predictions = \Database\Models\Prediction::all();
foreach ($predictions as $prediction) {
    $user = $prediction->user; // Separate query for each prediction
}

// Use:
$predictions = \Database\Models\Prediction::with('user')->get();
```

### Mass Assignment Exceptions

**Problem**: `MassAssignmentException` when using `create()` or `update()`.

**Solution**: Update the `$fillable` array in your model to include the fields:

```php
// In your model:
protected $fillable = ['field1', 'field2', 'field3'];
```

### Timestamps Issues

**Problem**: Automatic timestamps not being set or updated.

**Solution**: Ensure `$timestamps` is set to `true` or use timestamp methods:

```php
// Model property:
public $timestamps = true;

// Or disable for specific model:
public $timestamps = false;

// Manual timestamp handling:
$model->created_at = now();
$model->save();
```

## Resources for Further Learning

- [Laravel Documentation](https://laravel.com/docs/eloquent)
- [Eloquent: Getting Started](https://laravel.com/docs/eloquent)
- [Eloquent Relationships](https://laravel.com/docs/eloquent-relationships)
- [Database Migrations](https://laravel.com/docs/migrations)
- [Query Builder](https://laravel.com/docs/queries)

By following this guide, you'll be able to effectively use Eloquent ORM in the SoVest project, making your database operations more secure, maintainable, and expressive.