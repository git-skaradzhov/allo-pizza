#!/usr/bin/env bash
set -euo pipefail

find_php_bin() {
    if [ -n "${PHP_BIN:-}" ] && [ -x "$PHP_BIN" ]; then
        echo "$PHP_BIN"
        return 0
    fi

    if [ -n "${PHP:-}" ] && [ -x "$PHP" ]; then
        echo "$PHP"
        return 0
    fi

    local candidate version_id

    for candidate in \
        /opt/cpanel/ea-php83/root/usr/bin/php \
        /opt/cpanel/ea-php82/root/usr/bin/php \
        /usr/local/bin/php83 \
        /usr/local/bin/php82 \
        /opt/homebrew/bin/php \
        /usr/local/bin/php \
        "$(command -v php 2>/dev/null || true)"
    do
        if [ -z "$candidate" ] || [ ! -x "$candidate" ]; then
            continue
        fi

        version_id="$("$candidate" -r 'echo PHP_VERSION_ID;' 2>/dev/null || echo 0)"

        if [ "$version_id" -ge 80200 ]; then
            echo "$candidate"
            return 0
        fi
    done

    return 1
}

require_php_bin() {
    if ! PHP_BIN="$(find_php_bin)"; then
        echo "Could not find PHP 8.2 or newer." >&2
        echo "Set PHP=/path/to/php82 or PHP_BIN=/path/to/php82 and run again." >&2
        exit 1
    fi

    export PHP_BIN
}
