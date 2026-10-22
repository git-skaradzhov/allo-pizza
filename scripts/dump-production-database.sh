#!/usr/bin/env bash
# Run this ON THE PRODUCTION SERVER. It never imports and never writes to the live tables.
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
# shellcheck source=scripts/find-php.sh
source "$ROOT_DIR/scripts/find-php.sh"
require_php_bin

if ! command -v mysqldump >/dev/null 2>&1 && [ ! -x /usr/bin/mysqldump ]; then
    echo "mysqldump not found. On cPanel it is usually /usr/bin/mysqldump." >&2
    exit 1
fi

MYSQLDUMP_BIN="$(command -v mysqldump 2>/dev/null || echo /usr/bin/mysqldump)"
BACKUP_DIR="${BACKUP_DIR:-$HOME/allo-pizza-backups}"
STAMP="$(date +%Y%m%d-%H%M%S)"
DUMP_FILE="${BACKUP_DIR}/prod-${STAMP}.sql.gz"
MEDIA_FILE="${BACKUP_DIR}/prod-public-storage-${STAMP}.tar.gz"

mkdir -p "$BACKUP_DIR"

CONFIG="$("$PHP_BIN" "$ROOT_DIR/scripts/db-connection.php")"
DB_HOST="$(printf '%s' "$CONFIG" | "$PHP_BIN" -r 'echo json_decode(stream_get_contents(STDIN), true)["host"] ?? "";')"
DB_PORT="$(printf '%s' "$CONFIG" | "$PHP_BIN" -r 'echo json_decode(stream_get_contents(STDIN), true)["port"] ?? 3306;')"
DB_DATABASE="$(printf '%s' "$CONFIG" | "$PHP_BIN" -r 'echo json_decode(stream_get_contents(STDIN), true)["database"] ?? "";')"
DB_USERNAME="$(printf '%s' "$CONFIG" | "$PHP_BIN" -r 'echo json_decode(stream_get_contents(STDIN), true)["username"] ?? "";')"
DB_PASSWORD="$(printf '%s' "$CONFIG" | "$PHP_BIN" -r 'echo json_decode(stream_get_contents(STDIN), true)["password"] ?? "";')"
DB_SOCKET="$(printf '%s' "$CONFIG" | "$PHP_BIN" -r 'echo json_decode(stream_get_contents(STDIN), true)["unix_socket"] ?? "";')"

if [ -z "$DB_DATABASE" ] || [ -z "$DB_USERNAME" ]; then
    echo "Could not read mysql credentials from Laravel .env." >&2
    exit 1
fi

DUMP_ARGS=(
    --single-transaction
    --quick
    --no-tablespaces
    --default-character-set=utf8mb4
    --hex-blob
    --routines
    --triggers
    -h "$DB_HOST"
    -P "$DB_PORT"
    -u "$DB_USERNAME"
)

if [ -n "$DB_SOCKET" ]; then
    DUMP_ARGS+=(--socket="$DB_SOCKET")
fi

echo "==> Dumping database ${DB_DATABASE} to ${DUMP_FILE}"
MYSQL_PWD="$DB_PASSWORD" "$MYSQLDUMP_BIN" "${DUMP_ARGS[@]}" "$DB_DATABASE" | gzip > "$DUMP_FILE"
unset MYSQL_PWD

if [ -d "$ROOT_DIR/storage/app/public" ]; then
    echo "==> Archiving storage/app/public to ${MEDIA_FILE}"
    tar -C "$ROOT_DIR/storage/app" -czf "$MEDIA_FILE" public
else
    echo "==> storage/app/public not found; skipped media archive"
    MEDIA_FILE=""
fi

echo
echo "Done. Files are outside the web root:"
echo "  DB:    ${DUMP_FILE}"
echo "  Media: ${MEDIA_FILE:-"(none)"}"
echo
echo "Download them to the local machine, then:"
echo "  bash scripts/import-production-dump.sh ${DUMP_FILE##*/} --yes"
echo "  tar -C storage/app -xzf ${MEDIA_FILE##*/}"
