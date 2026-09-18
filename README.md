# npokcu

Laravel backend for a digital product catalog, checkout workflow and private delivery of purchased files.

The project is a deliberately isolated foundation for a proxy-product store. It models the core business flow without copying production catalog data or connecting to a real payment provider.

## What it demonstrates

- product catalog with categories and duration-based prices;
- authenticated accounts and guest checkout;
- email verification, password reset and password change flows;
- checkout with order and order-item snapshots;
- idempotent test payments;
- admin panel for categories, products, orders and users;
- policies and middleware for access control;
- private file delivery with download limits and audit logs;
- queued account and delivery emails;
- database migrations, factories, seeders and feature tests;
- Russian validation and interface text in UTF-8.

The current checkout uses a test payment service. No real payment provider is connected.

## Stack

- PHP 8.3+
- Laravel 13
- Blade
- Laravel Breeze authentication
- MySQL/MariaDB for local application development
- SQLite in the test suite
- Vite and Tailwind CSS
- PHPUnit
- database-backed queues, cache and sessions

## Architecture

The application follows Laravel's standard structure with business logic separated into focused services:

- `app/Http/Controllers` — web controllers;
- `app/Http/Requests` — input validation;
- `app/Models` — Eloquent models and relationships;
- `app/Policies` — authorization rules;
- `app/Services/Orders` — checkout and order workflow;
- `app/Services/Payments` — payment abstraction and test payment;
- `app/Services/Accounts` — guest-account creation and linking;
- `app/Services/Deliveries` — private file generation and delivery;
- `app/Mail` — account and delivery notifications;
- `database/migrations` — schema history;
- `database/factories` and `database/seeders` — development data;
- `tests/Feature` and `tests/Unit` — automated checks.

Main domain entities:

`User`, `Category`, `Product`, `ProductDurationOption`, `Order`, `OrderItem`, `PaymentTransaction`, `ProxyDelivery` and `DownloadLog`.

## Business flow

1. A visitor opens the catalog and selects a product duration.
2. The checkout request validates the selected product and duration again inside a database transaction.
3. The order stores an immutable snapshot of the purchased item and price.
4. The test payment service creates an idempotent paid transaction.
5. A guest order is linked to an existing account or a new account is created.
6. A private delivery file is created and an email is queued.
7. Authenticated downloads are authorized through a policy and recorded in `download_logs`.

The checkout uses a request token hash to make repeated POST requests safe. Product and duration rows are locked during the transaction to prevent purchasing inactive or changed options.

## Requirements

- PHP 8.3 or newer;
- Composer;
- Node.js and npm;
- MySQL 8/MariaDB for the default local setup;
- PDO SQLite for the test suite;
- a mail transport if you want to test real email delivery.

## Local setup

Clone the repository and install the dependencies:

```bash
git clone https://github.com/axfri/npokcu.git
cd npokcu

composer install
npm install
```

Create the environment file:

```bash
cp .env.example .env
php artisan key:generate
```

On Windows PowerShell:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

Configure the database values in `.env`, then run migrations and demo seed data:

```bash
php artisan migrate
php artisan db:seed
npm run build
```

The seeder creates clearly marked demo categories, products, one demo user and a completed test order. It does not contain production catalog data.

Start the application:

```bash
php artisan serve
```

For frontend development with live Vite reload:

```bash
composer run dev
```

The development script starts the Laravel server, queue listener, log viewer and Vite together.

## Testing

Run the complete test suite:

```bash
composer run test
```

or:

```bash
php artisan test
```

Tests use an in-memory SQLite database and cover:

- registration and authentication;
- email verification;
- password reset and password change;
- catalog and product visibility;
- checkout and order creation;
- guest account processing;
- admin access;
- private delivery authorization;
- download logging;
- database structure;
- money formatting and mail behavior.

## Environment

Important variables are documented in [`.env.example`](.env.example).

For a local demo, review at least:

- `APP_ENV`;
- `APP_DEBUG`;
- database connection values;
- `QUEUE_CONNECTION`;
- `MAIL_*` values;
- `PROXY_DELIVERY_DISK`;
- `PAYMENT_TEST_MODE`.

Never commit `.env`, application keys, database credentials, mail credentials or payment secrets.

## Security notes

- Admin routes require authentication and the admin middleware.
- Checkout input is validated through Form Requests.
- Sensitive downloads are stored on a private filesystem disk.
- Download access is checked through a policy and recorded for auditing.
- Passwords are hashed by Laravel.
- Login, password reset and verification endpoints use throttling.
- Payment payloads intentionally do not store provider secrets.
- Real payment callbacks and provider-specific credentials are not implemented yet.

Before production use, the project still needs a real payment adapter, callback signature verification, a production mail transport, queue worker monitoring, backups and a deployment security review.

## Current limitations

- no Docker or docker-compose configuration yet;
- no public REST API;
- no real payment provider;
- no Redis-specific integration;
- no production deployment workflow;
- the catalog contains demo data only.

These limitations are intentional for the current foundation stage.

## License

This project is provided for portfolio and development purposes.

