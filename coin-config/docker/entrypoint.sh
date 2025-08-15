#!/usr/bin/env bash
set -euo pipefail

if [ ! -f "/var/www/html/vendor/autoload.php" ]; then
	composer install --no-interaction --prefer-dist --no-progress
fi

if ! grep -q "APP_KEY=" .env || [ -z "$(grep '^APP_KEY=' .env | cut -d= -f2-)" ]; then
	php artisan key:generate --force
fi

php artisan migrate --force || true
php artisan db:seed --force || true

exec apache2-foreground