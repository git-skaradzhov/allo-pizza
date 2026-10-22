#!/usr/bin/env bash
# Import a production mysqldump into the LOCAL database. Refuses to run in production.
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
# shellcheck source=scripts/find-php.sh
source "$ROOT_DIR/scripts/find-php.sh"
require_php_bin

YES=0
DUMP_FILE=""

for arg in "$@"; do
    case "$arg" in
        --yes) YES=1 ;;
        --help|-h)
            echo "Usage: bash scripts/import-production-dump.sh /path/to/prod-YYYYMMDD.sql.gz [--yes]"
            exit 0
            ;;
        *)
            if [ -n "$DUMP_FILE" ]; then
                echo "Unexpected argument: $arg" >&2
                exit 1
            fi
            DUMP_FILE="$arg"
            ;;
    esac
done

if [ -z "$DUMP_FILE" ]; then
    echo "Usage: bash scripts/import-production-dump.sh /path/to/prod-YYYYMMDD.sql.gz [--yes]" >&2
    exit 1
fi

if [ ! -f "$DUMP_FILE" ]; then
    echo "Dump file not found: $DUMP_FILE" >&2
    exit 1
fi

if ! command -v mysql >/dev/null 2>&1 && [ ! -x /usr/bin/mysql ]; then
    echo "mysql client not found." >&2
    exit 1
fi

MYSQL_BIN="$(command -v mysql 2>/dev/null || echo /usr/bin/mysql)"
CONFIG="$("$PHP_BIN" "$ROOT_DIR/scripts/db-connection.php")"
APP_ENV="$(printf '%s' "$CONFIG" | "$PHP_BIN" -r 'echo json_decode(stream_get_contents(STDIN), true)["app_env"] ?? "";')"
DB_HOST="$(printf '%s' "$CONFIG" | "$PHP_BIN" -r 'echo json_decode(stream_get_contents(STDIN), true)["host"] ?? "";')"
DB_PORT="$(printf '%s' "$CONFIG" | "$PHP_BIN" -r 'echo json_decode(stream_get_contents(STDIN), true)["port"] ?? 3306;')"
DB_DATABASE="$(printf '%s' "$CONFIG" | "$PHP_BIN" -r 'echo json_decode(stream_get_contents(STDIN), true)["database"] ?? "";')"
DB_USERNAME="$(printf '%s' "$CONFIG" | "$PHP_BIN" -r 'echo json_decode(stream_get_contents(STDIN), true)["username"] ?? "";')"
DB_PASSWORD="$(printf '%s' "$CONFIG" | "$PHP_BIN" -r 'echo json_decode(stream_get_contents(STDIN), true)["password"] ?? "";')"
DB_SOCKET="$(printf '%s' "$CONFIG" | "$PHP_BIN" -r 'echo json_decode(stream_get_contents(STDIN), true)["unix_socket"] ?? "";')"

if [ "$APP_ENV" = "production" ]; then
    echo "Refusing to import a dump into a production environment." >&2
    exit 1
fi

if [ -z "$DB_DATABASE" ] || [ -z "$DB_USERNAME" ]; then
    echo "Could not read local mysql credentials from Laravel .env." >&2
    exit 1
fi

echo "This will REPLACE local database '${DB_DATABASE}' on ${DB_HOST} with ${DUMP_FILE}."
if [ "$YES" -ne 1 ]; then
    echo "Re-run with --yes to continue."
    exit 1
fi

MYSQL_ARGS=(-h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME")
if [ -n "$DB_SOCKET" ]; then
    MYSQL_ARGS+=(--socket="$DB_SOCKET")
fi

echo "==> Importing ${DUMP_FILE} into ${DB_DATABASE}"
if [[ "$DUMP_FILE" == *.gz ]]; then
    gzip -dc "$DUMP_FILE" | MYSQL_PWD="$DB_PASSWORD" "$MYSQL_BIN" "${MYSQL_ARGS[@]}" "$DB_DATABASE"
else
    MYSQL_PWD="$DB_PASSWORD" "$MYSQL_BIN" "${MYSQL_ARGS[@]}" "$DB_DATABASE" < "$DUMP_FILE"
fi
unset MYSQL_PWD

echo "==> Running pending migrations on the imported dump"
bash "$ROOT_DIR/scripts/run-artisan.sh" migrate --force

echo "Done. Local database now matches the production dump."
echo "If you also downloaded the media archive:"
echo "  tar -C storage/app -xzf prod-public-storage-YYYYMMDD.tar.gz"
echo "  php artisan storage:link"
