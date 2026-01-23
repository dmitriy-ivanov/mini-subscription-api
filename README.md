# Mini Subscription Service API

A simple backend service for managing user subscriptions, built with Laravel 12. This API allows customers to register and manage subscriptions to digital products (Daily News, Tech Journal, World Times).

## Table of Contents

- [Architecture Decisions](#architecture-decisions)
- [Setup Instructions](#setup-instructions)
- [API Documentation](#api-documentation)
- [Testing](#testing)
- [Environment Variables](#environment-variables)

## Architecture Decisions

### Database: SQLite

SQLite was chosen for simplicity in local testing. Data persists between requests, is easy to inspect, and requires no separate database server.

### Authentication: Global API Key

The system uses a simple global API key stored in the `.env` file. This simplified approach meets the assignment's basic protection requirement. In production, this would be replaced with OAuth2 or JWT tokens for per-customer authentication.

### RESTful API Design

The API follows RESTful conventions with nested resources, standard HTTP verbs, and consistent JSON responses.

### Service Layer

Business logic is separated into service classes to keep controllers thin and improve testability.

### Database Constraints

A unique constraint on `(customer_id, product_id, status)` prevents duplicate active subscriptions at the database level.

### Extensibility

The system is intentionally minimal to stay within scope, but the structure allows easy extension to billing, renewals, audit logs, and B2B seat-based subscriptions.

## Setup Instructions

### Quick Start (Docker - Recommended)

The fastest way to get started is using Docker:

1. **Clone the repository**:

    ```bash
    git clone <repo-url>
    cd <repo-folder>
    ```

2. **Build and start the container**:

    ```bash
    docker-compose up --build
    ```

The container will automatically:

- Copy `.env.example` to `.env` if `.env` doesn't exist
- Generate application key
- Install dependencies
- Run migrations
- Seed the database
- Start the Laravel development server

The API will be available at `http://localhost:8000`

To stop the container:

```bash
docker-compose down
```

**Note:** The default API key is `changeme123` (set in `.env.example`, which is copied to `.env`). See [Environment Variables](#environment-variables) for details.

### Full Manual Local Setup (Optional)

If you prefer to run without Docker:

**Prerequisites:**

- PHP 8.2 or higher
- Composer
- SQLite extension for PHP

**Steps:**

1. **Clone the repository**:

    ```bash
    git clone <repo-url>
    cd <repo-folder>
    ```

2. **Install dependencies**:

    ```bash
    composer install
    ```

3. **Configure environment**:

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

4. **Set up the database**:

    ```bash
    touch database/database.sqlite
    php artisan migrate
    php artisan db:seed
    ```

5. **Set API key** in `.env`:

    ```
    API_KEY=changeme123
    ```

6. **Start the development server**:

    ```bash
    php artisan serve
    ```

The API will be available at `http://localhost:8000`

## API Documentation

### Base URL

```
http://localhost:8000/api
```

### Endpoints Summary

| Endpoint                                                  | Method | Auth | Description                                          |
| --------------------------------------------------------- | ------ | ---- | ---------------------------------------------------- |
| `/api/customers`                                          | POST   | No   | Register a new customer                              |
| `/api/products`                                           | GET    | No   | List all available products                          |
| `/api/customers/{customer_id}/subscriptions`              | POST   | Yes  | Subscribe customer to a product                      |
| `/api/customers/{customer_id}/subscriptions`              | GET    | Yes  | List customer subscriptions (active only by default) |
| `/api/customers/{customer_id}/subscriptions?all=true`     | GET    | Yes  | List all subscriptions including cancelled           |
| `/api/customers/{customer_id}/subscriptions/{product_id}` | DELETE | Yes  | Cancel customer subscription to a product            |

### Authentication

For protected endpoints, include the API key using either:

- `X-API-Key` header: `curl -H "X-API-Key: your-api-key" ...`
- `Authorization: Bearer` header: `curl -H "Authorization: Bearer your-api-key" ...`

Replace `your-api-key` with the value from your `.env` file's `API_KEY`.

### Endpoints

#### 1. Register Customer

Register a new customer. No authentication required.

**Note:** The response contains the customer `id` which you'll need to use in subscription endpoints.

**Request:**

```bash
curl -X POST http://localhost:8000/api/customers \
  -H "Content-Type: application/json" \
  -d '{"name": "John Doe", "email": "john@example.com"}'
```

**Response:** `201 Created`

```json
{
    "message": "Customer registered successfully",
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "created_at": "2026-01-23T10:00:00.000000Z"
    }
}
```

**Validation Errors:** `422 Unprocessable Entity`

```json
{
    "error": "Validation failed",
    "message": "The given data was invalid.",
    "errors": {
        "email": ["A customer with this email already exists."]
    }
}
```

#### 2. List Products

Get all available products. No authentication required. Use this to discover product IDs for subscribing.

**Request:**

```bash
curl http://localhost:8000/api/products
```

**Response:** `200 OK`

```json
{
    "data": [
        {
            "id": 1,
            "name": "Daily News"
        },
        {
            "id": 2,
            "name": "Tech Journal"
        },
        {
            "id": 3,
            "name": "World Times"
        }
    ]
}
```

#### 3. Subscribe to Product

Subscribe a customer to a product. Requires authentication (see [Authentication](#authentication) above).

**Request:**

```bash
curl -X POST http://localhost:8000/api/customers/1/subscriptions \
  -H "X-API-Key: your-api-key" \
  -H "Content-Type: application/json" \
  -d '{"product_id": 1}'
```

**Response:** `201 Created`

```json
{
    "message": "Subscription created successfully",
    "data": {
        "id": 1,
        "customer_id": 1,
        "product": {
            "id": 1,
            "name": "Daily News"
        },
        "status": "active",
        "subscribed_at": "2026-01-23T10:00:00.000000Z"
    }
}
```

**Error:** `422 Unprocessable Entity` (if already subscribed)

```json
{
    "error": "Subscription failed",
    "message": "Customer already has an active subscription to this product."
}
```

#### 4. List Customer Subscriptions

Get subscriptions for a customer. Returns only active subscriptions by default. Requires authentication.

**Request:**

```bash
curl http://localhost:8000/api/customers/1/subscriptions \
  -H "X-API-Key: your-api-key"
```

To get all subscriptions (including cancelled), add `?all=true`:

```bash
curl "http://localhost:8000/api/customers/1/subscriptions?all=true" \
  -H "X-API-Key: your-api-key"
```

**Response:** `200 OK` (default - active only)

```json
{
    "data": [
        {
            "id": 1,
            "product": {
                "id": 1,
                "name": "Daily News"
            },
            "status": "active",
            "subscribed_at": "2026-01-23T10:00:00.000000Z",
            "cancelled_at": null,
            "created_at": "2026-01-23T10:00:00.000000Z"
        }
    ]
}
```

**Response:** `200 OK` (with `?all=true` - includes cancelled)

```json
{
    "data": [
        {
            "id": 1,
            "product": {
                "id": 1,
                "name": "Daily News"
            },
            "status": "active",
            "subscribed_at": "2026-01-23T10:00:00.000000Z",
            "cancelled_at": null,
            "created_at": "2026-01-23T10:00:00.000000Z"
        },
        {
            "id": 2,
            "product": {
                "id": 2,
                "name": "Tech Journal"
            },
            "status": "cancelled",
            "subscribed_at": "2026-01-23T09:00:00.000000Z",
            "cancelled_at": "2026-01-23T10:30:00.000000Z",
            "created_at": "2026-01-23T09:00:00.000000Z"
        }
    ]
}
```

#### 5. Unsubscribe from Product

Cancel a customer's subscription to a product. Requires authentication.

**Request:**

```bash
curl -X DELETE http://localhost:8000/api/customers/1/subscriptions/1 \
  -H "X-API-Key: your-api-key"
```

**Response:** `200 OK`

```json
{
    "message": "Subscription cancelled successfully"
}
```

**Error:** `404 Not Found` (if no active subscription exists)

```json
{
    "error": "Not found",
    "message": "No active subscription found for this customer and product."
}
```

### Error Responses

All error responses follow a consistent format:

```json
{
    "error": "Error type",
    "message": "Human-readable error message"
}
```

Common HTTP status codes:

- `200` - Success
- `201` - Created
- `401` - Unauthorized (missing or invalid API key)
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error

## Testing

### Run Tests

```bash
php artisan test
```

**Note:** The feature tests will automatically set `API_KEY=test-api-key`, so no manual setup is required for testing.

### Test Coverage

The test suite includes:

- ✅ Customer registration (success and validation errors)
- ✅ Subscription creation
- ✅ Subscription listing
- ✅ Subscription cancellation
- ✅ Authentication middleware
- ✅ Duplicate subscription prevention
- ✅ Invalid product handling
- ✅ Missing API key handling

### Example Test Command

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/Api/SubscriptionTest.php

# Run with coverage (requires Xdebug)
php artisan test --coverage
```

## Environment Variables

Key environment variables in `.env`:

```env
APP_NAME=Laravel
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=sqlite

API_KEY=changeme123
```

### Required Variables

- `API_KEY`: The API key used for authentication. Replace with any random string you like. Use the same value when calling endpoints with `X-API-Key` or `Bearer` token.

## Known Limitations / Next Steps

Since this is a mini-project, the following limitations apply:

- **Only global API key authentication**: The system uses a single global API key for all requests. In a production system, this would be replaced with OAuth2 or JWT tokens for per-customer authentication.

- **Products are pre-seeded**: Products are created via database seeding and cannot be managed through the API. A production system would include product management endpoints.

- **No pagination in listing endpoints**: The listing endpoints (`GET /api/products`, `GET /api/customers/{customer}/subscriptions`) return all results without pagination. For production use, pagination should be implemented to handle large datasets efficiently.

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
