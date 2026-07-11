#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"

# shellcheck source=scripts/find-php.sh
source "$ROOT_DIR/scripts/find-php.sh"

require_php_bin

exec "$PHP_BIN" "$ROOT_DIR/artisan" "$@"
