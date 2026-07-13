# EventHub API

EventHub is an event and course booking platform: organizers publish events, participants
discover them and reserve a seat. This repository is the example application used throughout
the book *Laravel API: dalla struttura alla produzione* (both the Italian and the English
edition share this same codebase).

The project is API-only: there is no Blade frontend, no session-based web routes, no asset
build pipeline. Every request is served through `routes/api.php` and every response is JSON.

## Requirements

- Docker and Docker Compose (recommended path, see below), or
- PHP 8.4+ and Composer (manual path)

## Getting started

### With Docker (recommended)

```bash
docker compose up --build
```

This single command builds the application image, starts a MySQL 8.4 service (the same
database engine EventHub runs in production), installs the Composer dependencies, generates the
application key, runs the migrations, and serves the API on `http://localhost:8000`. Every team
member gets the same PHP version, the same MySQL version, and the same migrated schema, with no
manual setup step.

### Without Docker

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

This path uses SQLite (the default in `.env.example`) and is the quickest way to iterate locally
or run the test suite, but it does not exercise MySQL-specific behavior. Some behavior genuinely
differs between the two: for example, row locking (`lockForUpdate()`) is a no-op on SQLite but
enforced on MySQL. Prefer the Docker path whenever you need to verify something that depends on
the production database engine.

## Testing

The test suite uses Pest.

```bash
composer test
```

## Quality checks

```bash
composer quality
```

Runs code style (Pint), the test suite (Pest), and an OpenAPI documentation generation check
(`scramble:analyze`) in sequence, stopping at the first failure. The same sequence runs
automatically on every push and pull request (`.github/workflows/quality.yml`).
