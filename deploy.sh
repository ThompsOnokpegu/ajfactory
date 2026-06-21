#!/usr/bin/env bash
#
# Post-deploy steps for Hostinger.
# Run from the Laravel app root (e.g. ~/public_html/laravel).
# Called automatically by .github/workflows/deploy.yml after `git pull`,
# and safe to run by hand over SSH:  bash deploy.sh
#
set -euo pipefail

cd "$(dirname "$0")"

# If a deploy ships new migrations, review them first, then uncomment:
# php artisan migrate --force

# Publish the freshly-pulled build assets to the web root (the parent dir,
# i.e. public_html). Copies — the original stays in public/build.
rm -rf ../build
cp -r public/build ../build

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Deploy complete."
