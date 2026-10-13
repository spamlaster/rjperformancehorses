#!/bin/sh
# Build locally and deploy to the VPS. Usage: deploy/deploy.sh user@host
set -eu
HOST="$1"
npm run build
rsync -az --delete dist/ "$HOST:/var/www/rjph/web/"
rsync -az --delete --exclude .env --exclude storage --exclude vendor --exclude tests api/ "$HOST:/var/www/rjph/api/"
ssh -t "$HOST" 'cd /var/www/rjph/api \
  && composer install --no-dev --optimize-autoloader \
  && php artisan migrate --force \
  && php artisan config:cache && php artisan route:cache \
  && sudo systemctl reload php8.3-fpm'
