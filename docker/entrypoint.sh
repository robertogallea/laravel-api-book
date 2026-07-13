#!/usr/bin/env bash
set -e

if [ ! -d vendor ]; then
    composer install --no-interaction --no-progress
fi

if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate --force
fi

echo "Waiting for MySQL at ${DB_HOST}:${DB_PORT}..."
until php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; do
    sleep 2
done

php artisan migrate --force

# Not "php artisan serve": it spawns the PHP built-in server as a child process
# that does not inherit the container's environment variables, so it silently
# falls back to whatever DB_CONNECTION is written in .env (sqlite) instead of
# the mysql service defined in docker-compose.yml. Running the same underlying
# command Laravel's ServerCommand uses, directly, keeps it in this process.
cd public
exec php -S 0.0.0.0:8000 /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
