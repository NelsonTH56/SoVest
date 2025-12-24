# SoVest API Documentation

## Overview

The SoVest API provides programmatic access to stock prediction functionality, including creating, updating, and deleting predictions, as well as voting on predictions and retrieving stock data.

**Base URL:** `/api`

**Authentication:** All API endpoints require user authentication via Laravel session cookies or API tokens.

---

## Table of Contents

1. [Prediction Endpoints](#prediction-endpoints)
2. [Search Endpoints](#search-endpoints)
3. [Stock Data Endpoints](#stock-data-endpoints)
4. [Voting Endpoints](#voting-endpoints)
5. [Error Handling](#error-handling)
6. [Rate Limiting](#rate-limiting)

---

## Prediction Endpoints

### Legacy API Handler (Backward Compatibility)

**Endpoint:** `POST /api/predictions`

This endpoint provides backward compatibility with the legacy prediction operations API.

**Action Parameter:**
The `action` parameter determines which operation to perform:
- `create` - Create a new prediction
- `update` - Update an existing prediction
- `delete` - Delete a prediction
- `get` - Retrieve a single prediction

#### Create Prediction (via API Handler)

**Request:**
```http
POST /api/predictions
Content-Type: application/x-www-form-urlencoded

action=create
stock_id=123
prediction_type=Bullish
end_date=2025-12-31
reasoning=Strong fundamentals and market position
target_price=175.50
```

**Parameters:**
- `action` (required): Must be `create`
- `stock_id` (required): ID of the stock
- `prediction_type` (required): Either "Bullish" or "Bearish"
- `end_date` (required): Prediction end date (YYYY-MM-DD), must be a future business day
- `reasoning` (required): Detailed explanation for the prediction
- `target_price` (optional): Target price for the stock

**Response:**
```json
{
  "success": true,
  "message": "Prediction created successfully",
  "data": {
    "prediction_id": 456
  }
}
```

**Status Codes:**
- `200 OK` - Prediction created successfully
- `400 Bad Request` - Missing required fields or validation error
- `401 Unauthorized` - User not authenticated
- `500 Internal Server Error` - Server error

---

#### Update Prediction (via API Handler)

**Request:**
```http
POST /api/predictions
Content-Type: application/x-www-form-urlencoded

action=update
prediction_id=456
prediction_type=Bearish
end_date=2025-11-30
reasoning=Updated market analysis shows downward trend
target_price=140.00
```

**Parameters:**
- `action` (required): Must be `update`
- `prediction_id` (required): ID of the prediction to update
- `prediction_type` (optional): Either "Bullish" or "Bearish"
- `end_date` (optional): New end date (YYYY-MM-DD)
- `reasoning` (optional): Updated reasoning
- `target_price` (optional): Updated target price

**Response:**
```json
{
  "success": true,
  "message": "Prediction updated successfully",
  "data": {
    "prediction_id": 456
  }
}
```

**Status Codes:**
- `200 OK` - Prediction updated successfully
- `400 Bad Request` - Missing prediction ID or validation error
- `401 Unauthorized` - User not authenticated
- `403 Forbidden` - User doesn't own this prediction
- `404 Not Found` - Prediction not found
- `422 Unprocessable Entity` - Cannot edit inactive prediction
- `500 Internal Server Error` - Server error

---

#### Delete Prediction (via API Handler)

**Request:**
```http
POST /api/predictions
Content-Type: application/x-www-form-urlencoded

action=delete
prediction_id=456
```

**Parameters:**
- `action` (required): Must be `delete`
- `prediction_id` (required): ID of the prediction to delete

**Response:**
```json
{
  "success": true,
  "message": "Prediction deleted successfully",
  "data": {
    "prediction_id": 456
  }
}
```

**Status Codes:**
- `200 OK` - Prediction deleted successfully
- `400 Bad Request` - Missing prediction ID
- `401 Unauthorized` - User not authenticated
- `403 Forbidden` - User doesn't own this prediction
- `404 Not Found` - Prediction not found
- `500 Internal Server Error` - Server error

---

#### Get Prediction (via API Handler)

**Request:**
```http
POST /api/predictions
Content-Type: application/x-www-form-urlencoded

action=get
prediction_id=456
```

**Parameters:**
- `action` (required): Must be `get`
- `prediction_id` (required): ID of the prediction to retrieve

**Response:**
```json
{
  "success": true,
  "message": "Prediction retrieved successfully",
  "data": {
    "prediction_id": 456,
    "user_id": 123,
    "stock_id": 789,
    "symbol": "AAPL",
    "company_name": "Apple Inc.",
    "prediction_type": "Bullish",
    "target_price": 175.50,
    "prediction_date": "2025-01-15 10:30:00",
    "end_date": "2025-12-31",
    "is_active": 1,
    "accuracy": null,
    "reasoning": "Strong fundamentals and market position"
  }
}
```

---

### Direct Prediction Endpoints

#### Create Prediction

**Endpoint:** `POST /api/predictions/create`

**Request:**
```http
POST /api/predictions/create
Content-Type: application/json

{
  "stock_id": 123,
  "prediction_type": "Bullish",
  "end_date": "2025-12-31",
  "reasoning": "Strong fundamentals and market position",
  "target_price": 175.50
}
```

**Response:** Same as via API handler (create action)

---

#### Update Prediction

**Endpoint:** `POST /api/predictions/update`

**Request:**
```http
POST /api/predictions/update
Content-Type: application/json

{
  "prediction_id": 456,
  "prediction_type": "Bearish",
  "end_date": "2025-11-30",
  "reasoning": "Updated market analysis",
  "target_price": 140.00
}
```

**Response:** Same as via API handler (update action)

---

#### Delete Prediction

**Endpoint:** `DELETE /api/predictions/delete/{id}`

**Request:**
```http
DELETE /api/predictions/delete/456
```

**Response:** Same as via API handler (delete action)

---

#### View Prediction

**Endpoint:** `GET /api/predictions/{id}`

**Request:**
```http
GET /api/predictions/456
```

**Response:** Returns the prediction view with related data

---

## Voting Endpoints

### Vote on Prediction

**Endpoint:** `POST /prediction/vote`

**Request:**
```http
POST /prediction/vote
Content-Type: application/x-www-form-urlencoded

prediction_id=456
vote_type=upvote
```

**Parameters:**
- `prediction_id` (required): ID of the prediction to vote on
- `vote_type` (required): Either "upvote" or "downvote"

**Response:**
```json
{
  "success": true,
  "message": "Vote recorded successfully"
}
```

**Notes:**
- Voting the same type again will toggle (remove) the vote
- Changing vote type will update the existing vote
- One vote per user per prediction (unique constraint)

**Status Codes:**
- `200 OK` - Vote processed successfully
- `400 Bad Request` - Missing prediction ID
- `401 Unauthorized` - User not authenticated
- `404 Not Found` - Prediction not found
- `500 Internal Server Error` - Server error

---

### Get Vote Counts

**Endpoint:** `GET /predictions/{id}/vote-counts`

**Request:**
```http
GET /predictions/456/vote-counts
```

**Response:**
```json
{
  "success": true,
  "upvotes": 42,
  "downvotes": 8,
  "netvotes": 34
}
```

---

## Search Endpoints

### General Search

**Endpoint:** `GET /api/search`

**Request:**
```http
GET /api/search?query=apple&type=all
```

**Parameters:**
- `query` (required): Search query string
- `type` (optional): Search type - "stocks", "users", "predictions", or "all" (default)

**Response:**
```json
{
  "success": true,
  "results": {
    "stocks": [...],
    "users": [...],
    "predictions": [...]
  }
}
```

---

### Search Stocks

**Endpoint:** `GET /api/search_stocks`

**Request:**
```http
GET /api/search_stocks?query=apple
```

**Parameters:**
- `query` (required): Stock symbol or company name

**Response:**
```json
{
  "success": true,
  "stocks": [
    {
      "stock_id": 123,
      "symbol": "AAPL",
      "company_name": "Apple Inc.",
      "sector": "Technology",
      "active": true
    }
  ]
}
```

---

## Stock Data Endpoints

### Get All Stocks

**Endpoint:** `GET /api/stocks`

**Request:**
```http
GET /api/stocks
```

**Response:**
```json
{
  "success": true,
  "stocks": [
    {
      "stock_id": 123,
      "symbol": "AAPL",
      "company_name": "Apple Inc.",
      "sector": "Technology",
      "active": true
    },
    ...
  ]
}
```

---

### Get Stock by Symbol

**Endpoint:** `GET /api/stocks/{symbol}`

**Request:**
```http
GET /api/stocks/AAPL
```

**Parameters:**
- `symbol` (required): Stock symbol (1-5 uppercase letters)

**Response:**
```json
{
  "success": true,
  "stock": {
    "stock_id": 123,
    "symbol": "AAPL",
    "company_name": "Apple Inc.",
    "sector": "Technology",
    "active": true
  }
}
```

---

### Get Stock Price

**Endpoint:** `GET /api/stocks/{symbol}/price`

**Request:**
```http
GET /api/stocks/AAPL/price
```

**Parameters:**
- `symbol` (required): Stock symbol (1-5 uppercase letters)

**Response:**
```json
{
  "success": true,
  "price": {
    "symbol": "AAPL",
    "price_date": "2025-01-15",
    "open_price": 150.25,
    "close_price": 152.75,
    "high_price": 153.50,
    "low_price": 149.80,
    "volume": 45000000
  }
}
```

---

## Error Handling

All API endpoints return consistent error responses:

```json
{
  "success": false,
  "message": "Error description here",
  "errors": {
    "field_name": ["Error message for this field"]
  }
}
```

### Common Error Codes

- `400 Bad Request` - Invalid or missing parameters
- `401 Unauthorized` - Authentication required
- `403 Forbidden` - User lacks permission
- `404 Not Found` - Resource not found
- `422 Unprocessable Entity` - Validation failed
- `429 Too Many Requests` - Rate limit exceeded
- `500 Internal Server Error` - Server error

---

## Rate Limiting

### External API Calls

Stock data fetching from Alpha Vantage is rate-limited to **5 requests per minute** to comply with API limits.

### Internal Rate Limiting

Laravel's built-in rate limiting can be configured in `app/Http/Kernel.php`:

```php
'api' => [
    'throttle:60,1', // 60 requests per minute
    'bindings',
],
```

---

## Authentication

### Session-Based Authentication

All API endpoints use Laravel's session-based authentication. Users must be logged in via the web interface before making API calls.

**CSRF Protection:**
For POST, PUT, PATCH, DELETE requests, include the CSRF token:

```http
X-CSRF-TOKEN: {{ csrf_token() }}
```

### Future: API Token Authentication

API token authentication can be implemented using Laravel Sanctum for stateless API access.

---

## Validation Rules

### Prediction Validation

- **prediction_type**: Must be "Bullish" or "Bearish"
- **end_date**: Must be a future business day (Monday-Friday)
- **target_price**: Optional, must be numeric if provided
- **reasoning**: Required, must be a non-empty string
- **stock_id**: Must reference an existing stock

### Business Day Validation

The system automatically validates that prediction end dates fall on business days (Monday-Friday). Weekend dates are rejected.

---

## Examples

### Create Prediction with cURL

```bash
curl -X POST http://localhost/api/predictions \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "X-CSRF-TOKEN: your-csrf-token" \
  --cookie "laravel_session=your-session-cookie" \
  -d "action=create" \
  -d "stock_id=123" \
  -d "prediction_type=Bullish" \
  -d "end_date=2025-12-31" \
  -d "reasoning=Strong fundamentals" \
  -d "target_price=175.50"
```

### Vote with JavaScript Fetch

```javascript
fetch('/prediction/vote', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/x-www-form-urlencoded',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
  },
  body: new URLSearchParams({
    prediction_id: 456,
    vote_type: 'upvote'
  })
})
.then(response => response.json())
.then(data => console.log(data));
```

---

## Support

For issues or questions about the API, please contact the development team or create an issue on the project repository.

**Last Updated:** December 2025
