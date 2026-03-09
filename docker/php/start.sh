#!/usr/bin/env bash
set -e

if [ ! -f .env ] && [ -f .env.example ]; then
  cp .env.example .env
fi

mkdir -p database
if [ ! -f database/database.sqlite ]; then
  touch database/database.sqlite
fi

if [ ! -f vendor/autoload.php ]; then
  composer install --no-interaction --prefer-dist
fi

app_key_line="$(grep '^APP_KEY=' .env 2>/dev/null || true)"
app_key_value="${app_key_line#APP_KEY=}"
if [ -z "$app_key_value" ]; then
  php artisan key:generate --force
fi

php artisan migrate --seed --force

exec php artisan serve --host=0.0.0.0 --port=8000
