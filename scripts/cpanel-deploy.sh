#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

PHP_BIN="${PHP:-}"

if [ -z "$PHP_BIN" ]; then
    for candidate in \
        /opt/cpanel/ea-php83/root/usr/bin/php \
        /opt/cpanel/ea-php82/root/usr/bin/php \
        "$(command -v php || true)"
    do
        if [ -n "$candidate" ] && [ -x "$candidate" ]; then
            version_id="$("$candidate" -r 'echo PHP_VERSION_ID;' 2>/dev/null || echo 0)"

            if [ "$version_id" -ge 80200 ]; then
                PHP_BIN="$candidate"
                break
            fi
        fi
    done
fi

if [ -z "$PHP_BIN" ]; then
    echo "Could not find PHP 8.2 or newer. Set PHP=/path/to/php and run again." >&2
    exit 1
fi

echo "==> Using PHP: $("$PHP_BIN" -v | awk 'NR == 1 { print $1, $2 }')"

run_composer() {
    if [ -f composer.phar ]; then
        "$PHP_BIN" composer.phar "$@"

        return
    fi

    for composer_bin in \
        "$(command -v composer 2>/dev/null || true)" \
        /opt/cpanel/composer/bin/composer \
        /usr/local/bin/composer \
        "$HOME/bin/composer"
    do
        if [ -n "$composer_bin" ] && [ -x "$composer_bin" ]; then
            "$composer_bin" "$@"

            return
        fi
    done

    if [ -f /opt/cpanel/composer/bin/composer ]; then
        "$PHP_BIN" /opt/cpanel/composer/bin/composer "$@"

        return
    fi

    echo "==> Composer not found; downloading composer.phar"
    "$PHP_BIN" -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    "$PHP_BIN" composer-setup.php --quiet
    rm -f composer-setup.php
    "$PHP_BIN" composer.phar "$@"
}

echo "==> Installing PHP dependencies"
run_composer install --no-dev --optimize-autoloader --no-interaction

if command -v npm >/dev/null 2>&1; then
    echo "==> Building frontend assets"
    npm ci
    npm run build
else
    echo "==> npm not found; skipping frontend build"
    echo "    Run npm run build locally and upload public/build if your hosting has no Node.js."
fi

echo "==> Running database migrations"
"$PHP_BIN" artisan migrate --force

echo "==> Linking storage"
"$PHP_BIN" artisan storage:link || true

echo "==> Caching Laravel bootstrap files"
"$PHP_BIN" artisan optimize

echo "==> Deployment complete"
