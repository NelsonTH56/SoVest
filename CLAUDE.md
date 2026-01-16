# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**SoVest** is a stock prediction and social investment platform built with Laravel 12. Users create and vote on stock predictions (bullish/bearish), track accuracy, and build reputation scores. The system periodically fetches real stock data from Alpha Vantage API, evaluates predictions against actual performance, and ranks users by accuracy.

**Tech Stack:**
- Backend: Laravel 12, PHP 8.2+
- Database: MySQL
- Frontend: Vite 6.0+ with Tailwind CSS 4.0, Blade templates
- Testing: PHPUnit 11.5.3, Laravel Dusk 8.3
- API Integration: Alpha Vantage (stock data)

## Common Commands

### Development
```bash
# Start development environment (from src/ directory)
php artisan serve          # Start Laravel server (port 8000)
npm run dev                # Start Vite dev server with hot reload

# Database
php artisan migrate        # Run migrations
php artisan migrate:fresh  # Drop all tables and re-run migrations
php artisan db:seed        # Run database seeders

# Asset compilation
npm run build              # Build production assets

# Code quality
./vendor/bin/pint          # Run Laravel Pint (PHP CS Fixer)
```

### Testing
```bash
# Run specific test suites
php artisan test --testsuite=Unit        # Unit tests only
php artisan test --testsuite=Integration # Integration tests
php artisan test --testsuite=Feature     # Feature tests
php artisan dusk                         # Browser tests (Dusk)

# Run all tests
php artisan test

# Single test file
php artisan test tests/Unit/ExampleTest.php
```

### Stock Data & Predictions
```bash
# Manual task execution
php artisan stocks:listings          # Update stock catalog from API
php artisan stocks:update            # Fetch latest stock prices
php artisan predictions:evaluate     # Evaluate active predictions

# Note: These run automatically on schedule:
# - UpdateStockListings: Weekly
# - UpdateStockPrices: Hourly
# - EvaluatePredictions: Daily @ 00:00
```

### Debugging
```bash
# View logs
php artisan pail                     # Real-time log viewer
tail -f storage/logs/laravel.log     # Laravel application logs
tail -f logs/stock-updates.log       # Stock price update logs
tail -f logs/stock-listings.log      # Stock listing sync logs
tail -f logs/prediction-evaluations.log  # Prediction scoring logs
```

## Architecture & Code Structure

### Custom Architecture Patterns

#### 1. Service-Oriented Architecture with Dependency Injection
All business logic is abstracted into services via interfaces (located in `src/app/Services/Interfaces/`):

**Four Core Services:**
1. **PredictionScoringService** - Core prediction evaluation algorithm
   - `evaluateActivePredictions()` - Batch evaluate expired predictions
   - `evaluatePrediction()` - Single prediction accuracy calculation
   - `updateUserReputation()` - Adjust user reputation based on accuracy
   - Implements sophisticated scoring: directional correctness (75 pts) + magnitude bonuses + reputation tiers

2. **SearchService** - Multi-type search with adaptive result limiting
   - Supports stocks, users, predictions, and combined searches
   - Autocomplete with type-based limiting
   - Multiple sort strategies (relevance, date, accuracy, votes)

3. **StockDataService** - External API integration with rate limiting
   - Fetch & store stock prices from Alpha Vantage (5 requests/minute limit)
   - Stock listing updates via CSV parsing
   - Price history retrieval

4. **ResponseFormatter** - Standardized response formatting
   - JSON/HTML/XML response formatting
   - Unified error handling structure

**All interfaces are bound in** `src/app/Providers/AppServiceProvider.php` for automatic dependency injection.

#### 2. Invocable Task Classes Pattern
Three task classes in `src/app/Tasks/` implement `__invoke()` for scheduler:
- `UpdateStockListings` - Weekly stock catalog sync
- `UpdateStockPrices` - Hourly price update
- `EvaluatePredictions` - Daily midnight evaluation

These can be called via:
- Scheduler: `Schedule::call(UpdateStockPrices::class)->hourly()`
- Artisan: `php artisan stocks:update`
- Manual: `app()->call(UpdateStockPrices::class)`

#### 3. Custom Model Validation Trait
All models use `ValidationTrait` (`src/app/Models/Traits/ValidationTrait.php`) providing:
- Rule-based attribute validation (required, numeric, email, min, max, date, in, regex, unique)
- Custom error messages per model
- `validateAndSave()` convenience method
- Business logic validators like `validateFutureDate()` (ensures predictions are within 1095 business days)

**When creating/modifying models:**
- Override `getValidationRules()` to define field rules
- Override `getValidationMessages()` for custom error messages
- Use `validateAndSave()` instead of `save()` to enforce validation

### Database Models & Relationships

**8 Eloquent Models:**

```
User
├─ predictions() HasMany → Prediction
├─ predictionVotes() HasMany → PredictionVote
├─ searchHistory() HasMany → SearchHistory
└─ savedSearches() HasMany → SavedSearch

Prediction (custom primary key: prediction_id, no Laravel timestamps)
├─ user() BelongsTo → User
├─ stock() BelongsTo → Stock
└─ votes() HasMany → PredictionVote
Fields: prediction_type (Bullish/Bearish enum), target_price, end_date, is_active, accuracy

PredictionVote
├─ prediction() BelongsTo → Prediction
└─ user() BelongsTo → User
Fields: vote_type (upvote/downvote), vote_date

Stock
├─ predictions() HasMany → Prediction
└─ prices() HasMany → StockPrice

StockPrice (price_date for historical tracking)
└─ stock() BelongsTo → Stock

SearchHistory (created_at only)
└─ user() BelongsTo → User

SavedSearch (created_at only)
└─ user() BelongsTo → User
```

**Note:** Predictions use custom `prediction_id` primary key and manual `prediction_date` instead of Laravel timestamps.

### Routing Structure

**Web Routes** (`src/routes/web.php`):
- Auth: `/register`, `/login`, `/logout`
- Predictions: `/predictions`, `/predictions/create`, `/predictions/{id}`, `/predictions/{id}/edit`
- User: `/account`, `/home`, `/leaderboard`, `/profile/upload-photo`
- Search: `/search`

**API Routes** (`/api` prefix):
- Predictions: GET/POST `/predictions`, GET `/predictions/{id}`, DELETE `/predictions/delete/{id}`
- Stocks: GET `/stocks`, GET `/stocks/{symbol}`, GET `/stocks/{symbol}/price`
- Search: GET `/search`, GET `/search_stocks`

**Console Commands** (`src/routes/console.php`):
- Scheduled tasks defined here
- Custom Artisan commands for manual execution

**Middleware:**
- `auth` - Require authentication
- `prediction.owner` - Custom middleware (`src/app/Http/Middleware/EnsurePredictionOwner.php`) validates prediction ownership

### Controllers (5 total)
1. **AuthController** - Registration, login, logout
2. **PredictionController** - Full CRUD for predictions, voting
3. **SearchController** - Search logic, stock data retrieval
4. **UserController** - Profile, leaderboard, account management
5. **MainController** - Static pages (landing, about)

### Configuration Files

**Important configs in** `src/config/`:
- `api_config.php` - Alpha Vantage API key, rate limits (5 req/min), logging paths
- `database.php` - MySQL connection settings (use .env for credentials)
- `auth.php` - Authentication guards and providers

**Environment variables** (`.env` in `src/` directory):
- `DB_DATABASE=sovest` (MySQL database name)
- `ALPHA_VANTAGE_API_KEY` - Stock data API key
- `APP_DEBUG=true` for development

## Development Patterns & Best Practices

### 1. Use Services for Business Logic
**Never write complex logic in controllers.** All business logic should be in service classes:

```php
// Good: Controller delegates to service
public function evaluatePredictions(PredictionScoringServiceInterface $scoringService)
{
    $scoringService->evaluateActivePredictions();
}

// Bad: Business logic in controller
public function evaluatePredictions()
{
    $predictions = Prediction::where('is_active', true)->get();
    foreach ($predictions as $prediction) {
        // Complex calculation logic...
    }
}
```

### 2. Leverage Dependency Injection
All services are bound to interfaces in `AppServiceProvider`. Use type-hinting for automatic injection:

```php
public function __construct(
    PredictionScoringServiceInterface $scoringService,
    StockDataServiceInterface $stockService
) {
    // Services auto-injected by Laravel
}
```

### 3. Model Validation First
Always validate using the `ValidationTrait` before saving:

```php
// Good
$prediction->validateAndSave();

// Also good
if (!$prediction->validate()) {
    return redirect()->back()->withErrors($prediction->getErrors());
}
$prediction->save();
```

### 4. Standardize Responses
All responses should go through `ResponseFormatter`:

```php
// JSON response
return $this->responseFormatter->successResponse($data, 'Success message');

// Error response
return $this->responseFormatter->errorResponse('Error message', 400);
```

### 5. Use Invocable Pattern for Background Tasks
Create invocable classes for scheduled jobs instead of closures:

```php
// Good: Invocable class in app/Tasks/
class UpdateStockPrices
{
    public function __invoke()
    {
        // Task logic
    }
}

// Schedule in routes/console.php
Schedule::call(UpdateStockPrices::class)->hourly();
```

### 6. API Rate Limiting
When working with external APIs, respect rate limits in `StockDataService`:
- Alpha Vantage: 5 requests per minute
- Service automatically throttles requests
- Check `config/api_config.php` for configuration

### 7. Middleware for Authorization
Use custom middleware for entity-level checks:
- `prediction.owner` ensures only prediction owners can edit/delete
- Apply in routes: `Route::put('/predictions/{id}', ...)->middleware('prediction.owner')`

## Critical Files for Quick Onboarding

**Read these first:**
1. `src/app/Providers/AppServiceProvider.php` - Dependency injection bindings
2. `src/routes/web.php` - Routing structure and middleware usage
3. `src/app/Services/PredictionScoringService.php` - Core business logic (scoring algorithm)
4. `src/app/Models/Traits/ValidationTrait.php` - Custom validation system
5. `src/config/api_config.php` - External API configuration

## Project Status & Roadmap

This project has completed Phase 2 of its development plan (SQL to Eloquent ORM migration). Key accomplishments:
- All raw SQL converted to Eloquent ORM
- Service layer with interfaces implemented
- Custom validation trait across all models
- Migration scripts for schema management

**Development phases** (see `development_plan.md` for details):
- Phase 1: Database Structure ✅ Completed
- Phase 2: SQL to Eloquent ORM ✅ Completed
- Phase 3: Application Restructuring (Next)
- Phase 4: Frontend Modernization
- Phase 5: Feature Expansion
- Phase 6: Full Laravel Migration (Future)

## Working Directory

**All Laravel commands should be run from** `src/` **directory:**
```bash
cd src/
php artisan [command]
npm run [script]
```

The repository root (`/c/Users/nthay/SoVest/`) contains only metadata files (`composer.json`, `composer.phar`, `development_plan.md`).
