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

## Following the book chapter by chapter

This repository's git history is organized to mirror the book, not just its final state.
Every chapter has a corresponding tag marking exactly what the codebase looked like once that
chapter was complete, and chapters substantial enough to introduce several distinct concepts
in sequence also have intermediate tags for their main steps.

- `chapter-NN` marks the end of chapter N (`chapter-00` for the Introduction, `chapter-13` for
  Appendix A, `chapter-14` for Appendix B).
- `chapter-NN-step-MM` marks a specific step within a chapter, for the chapters that have more
  than one.
- Purely editorial chapters (the Introduction, the Conclusions, Appendix B) introduce no code
  of their own: their tag points to the same commit as the chapter before them.

To check out the code exactly as it was at a given point in the book:

```bash
git checkout chapter-04
```

Go back to the latest state with `git checkout main`. See `CHANGELOG.md` for the full list of
tags and a one-line description of what each one introduces.

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

## Companion sub-projects: legacy system and gateway facade

Two standalone PHP scripts, `legacy/` and `gateway/`, live alongside this Laravel application.
Neither is part of EventHub, and neither is Laravel: they exist to make the case study in
Chapter 11 ("Da monolite a API-first") and the appendix on API gateways concrete and runnable,
not just described on the page.

- **`legacy/`** is the minimal skeleton of the pre-existing monolithic course management system
  the migration case study starts from: plain PHP, no framework, no tests, no API. See
  `legacy/README.md`.
- **`gateway/`** is the strangler-fig facade built incrementally in Chapter 11: a single entry
  point that decides, request by request, whether the legacy system or EventHub should serve it.
  See `gateway/README.md`.

Both are excluded from this project's Pint configuration (`pint.json`): they are deliberately
*not* written to EventHub's own coding conventions, since part of the point of Chapter 11 is the
stylistic contrast between the two.

To reproduce the examples in Chapter 11 end to end, run all three services at once, each on its
own port, from three separate terminals (all commands below assume `code/` as the current
directory):

```bash
# 1. EventHub itself, http://localhost:8000
php artisan serve
```

```bash
# 2. The legacy system, http://localhost:8001 (php seed.php only needs to run once)
cd legacy
php seed.php
php -S localhost:8001 -t public
```

```bash
# 3. The facade, http://localhost:8080, the single entry point Chapter 11 routes requests through
cd gateway
php -S localhost:8080 -t public public/index.php
```
