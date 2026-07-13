# EventHub API

EventHub is an event and course booking platform: organizers publish events, participants
discover them and reserve a seat. This repository is the example application used throughout
the book *Laravel API: dalla struttura alla produzione*.

The project is API-only: there is no Blade frontend, no session-based web routes, no asset
build pipeline. Every request is served through `routes/api.php` and every response is JSON.

## Requirements

- PHP 8.3+ and Composer

## Getting started

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

## Testing

The test suite uses Pest.

```bash
composer test
```
