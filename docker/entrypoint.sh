#!/usr/bin/env bash
set -e

PORT="${PORT:-8080}"
sed -i "s/__PORT__/${PORT}/g" /etc/nginx/sites-available/default

cd /app

echo "==> Rodando migrations..."
php artisan migrate --force

echo "==> Rodando seeders..."
php artisan db:seed --force

echo "==> Cacheando config, rotas e views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Subindo Nginx + PHP-FPM via supervisord..."
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
