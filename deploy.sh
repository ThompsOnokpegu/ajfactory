#!/usr/bin/env bash
#
# Post-deploy steps for Hostinger.
# Run from the Laravel app root (e.g. ~/public_html/laravel).
# Called automatically by .github/workflows/deploy.yml after `git pull`,
# and safe to run by hand over SSH:  bash deploy.sh
#
set -euo pipefail

cd "$(dirname "$0")"

php artisan migrate --force

# Publish the freshly-pulled build assets to the web root (the parent dir,
# i.e. public_html). Copies — the original stays in public/build.
rm -rf ../build
cp -r public/build ../build

# The web root is public_html, NOT public/, so EVERY static file Laravel serves
# has to be published the same way. Only build/ ever was, so anything else added
# to public/ since setup simply 404'd while looking perfectly present in the repo:
# /img/og-preview.jpg was referenced as og:image by four pages and stayed a 404 even
# after the file was committed, and the favicon kept serving the framework default
# no matter what the repo contained.
#
# Merge, never mirror: files are overwritten but nothing is deleted, because the
# web root also holds things an operator put there by hand. index.php, .htaccess
# and robots.txt are deliberately left alone — those are server-shaped and
# overwriting them from public/ would break routing.
mkdir -p ../img
cp -rf public/img/. ../img/
cp -f public/favicon.ico public/favicon.svg public/apple-touch-icon.png ../

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Deploy complete."
