#!/usr/bin/env bash
set -euo pipefail

if [[ -f ".env" ]]; then
  set -a
  # shellcheck disable=SC1091
  source ".env"
  set +a
fi

REMOTE_ENV_FILE="${REMOTE_ENV_FILE:-.env.remote}"
if [[ -f "$REMOTE_ENV_FILE" ]]; then
  set -a
  # shellcheck disable=SC1090
  source "$REMOTE_ENV_FILE"
  set +a
fi

: "${SSH_HOST:?Set SSH_HOST (e.g. test.softimps.com) in .env.remote}"
: "${SSH_USER:?Set SSH_USER (e.g. test.softimps.com_xxx) in .env.remote}"
SSH_PORT="${SSH_PORT:-22}"

: "${REMOTE_DB_HOST:?Set REMOTE_DB_HOST in .env.remote (often localhost)}"
: "${REMOTE_DB_NAME:?Set REMOTE_DB_NAME in .env.remote}"
: "${REMOTE_DB_USER:?Set REMOTE_DB_USER in .env.remote}"
: "${REMOTE_DB_PASSWORD:?Set REMOTE_DB_PASSWORD in .env.remote}"

: "${OLD_URL:?Set OLD_URL (remote site URL) in .env.remote}"
NEW_URL="${NEW_URL:-${WP_HOME:-}}"
: "${NEW_URL:?Set NEW_URL or WP_HOME (local site URL)}"

DB_NAME="${DB_NAME:-bedrock}"
DB_USER="${DB_USER:-bedrock}"
DB_PASSWORD="${DB_PASSWORD:-bedrock}"
DB_ROOT_PASSWORD="${DB_ROOT_PASSWORD:-root}"

# Performance/health toggles
ANALYZE_AFTER_IMPORT="${ANALYZE_AFTER_IMPORT:-1}"

DUMP_DIR="${DUMP_DIR:-./.db-dumps}"
mkdir -p "$DUMP_DIR"

STAMP="$(date +%Y%m%d-%H%M%S)"
DUMP_FILE="$DUMP_DIR/${SSH_HOST}_${REMOTE_DB_NAME}_${STAMP}.sql.gz"

SSH_TARGET="${SSH_USER}@${SSH_HOST}"

SSH_BASE_ARGS=("-p" "$SSH_PORT")
if [[ -n "${SSH_KEY_PATH:-}" ]]; then
  SSH_BASE_ARGS+=("-i" "$SSH_KEY_PATH")
fi

SSH_CMD=(ssh "${SSH_BASE_ARGS[@]}" "$SSH_TARGET")
if [[ -n "${SSH_PASSWORD:-}" ]]; then
  if ! command -v sshpass >/dev/null 2>&1; then
    echo "!! SSH_PASSWORD is set but sshpass is not installed."
    echo "   On macOS: brew install sshpass"
    exit 2
  fi
  SSH_CMD=(sshpass -p "$SSH_PASSWORD" ssh "${SSH_BASE_ARGS[@]}" -o StrictHostKeyChecking=accept-new "$SSH_TARGET")
fi

echo "==> Dumping remote DB over SSH -> $DUMP_FILE"
# NOTE: Using --password on the remote side may expose it to other users via process listing.
# If that is a concern, create a remote ~/.my.cnf with credentials and remove --password.
"${SSH_CMD[@]}" \
  "mysqldump --single-transaction --quick --skip-lock-tables \
    -h'$REMOTE_DB_HOST' -u'$REMOTE_DB_USER' --password='$REMOTE_DB_PASSWORD' '$REMOTE_DB_NAME' \
    | gzip -c" \
  > "$DUMP_FILE"

if [[ ! -s "$DUMP_FILE" ]]; then
  echo "!! Remote dump failed or produced an empty file: $DUMP_FILE"
  echo "   Check SSH access and that mysqldump exists on the server."
  echo "   Tip: try manually: ssh -p $SSH_PORT $SSH_TARGET 'which mysqldump && mysqldump --version'"
  rm -f "$DUMP_FILE" || true
  exit 1
fi

echo "==> Restoring into local Docker DB (this will wipe local DB: $DB_NAME)"

docker compose exec -T db mariadb \
  -uroot -p"$DB_ROOT_PASSWORD" \
  -e "DROP DATABASE IF EXISTS \`$DB_NAME\`; CREATE DATABASE \`$DB_NAME\` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Import dump (use gzip directly; macOS /usr/bin/zcat can behave differently)
gzip -dc "$DUMP_FILE" | docker compose exec -T db mariadb \
  -uroot -p"$DB_ROOT_PASSWORD" \
  "$DB_NAME"

if [[ "$ANALYZE_AFTER_IMPORT" != "0" ]]; then
  echo "==> Refreshing DB statistics (ANALYZE TABLE)"
  if docker compose exec -T db sh -lc 'command -v mariadb-check >/dev/null 2>&1'; then
    docker compose exec -T db mariadb-check \
      -uroot -p"$DB_ROOT_PASSWORD" \
      --analyze \
      --databases "$DB_NAME" \
      >/dev/null
  else
    TABLES="$(docker compose exec -T db mariadb -uroot -p"$DB_ROOT_PASSWORD" --batch --skip-column-names \
      -e "SELECT table_name FROM information_schema.tables WHERE table_schema='${DB_NAME}' AND table_type='BASE TABLE';")"
    while IFS= read -r table; do
      [[ -z "$table" ]] && continue
      docker compose exec -T db mariadb -uroot -p"$DB_ROOT_PASSWORD" \
        -e "ANALYZE TABLE \`${DB_NAME}\`.\`${table}\`;" \
        >/dev/null
    done <<< "$TABLES"
  fi
fi

# Ensure local app user exists and has access (idempotent)
docker compose exec -T db mariadb \
  -uroot -p"$DB_ROOT_PASSWORD" \
  -e "CREATE USER IF NOT EXISTS '$DB_USER'@'%' IDENTIFIED BY '$DB_PASSWORD'; GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'%'; FLUSH PRIVILEGES;"

echo "==> Running WP search-replace: $OLD_URL -> $NEW_URL"
if docker compose exec -T php test -x ./vendor/bin/wp; then
  docker compose exec -T php ./vendor/bin/wp search-replace \
    "$OLD_URL" "$NEW_URL" \
    --all-tables \
    --precise \
    --recurse-objects \
    --skip-columns=guid \
    --allow-root \
    --quiet
else
  echo "!! WP-CLI not found at ./vendor/bin/wp inside php container."
  echo "   Run: composer install (with dev deps) or: composer require --dev wp-cli/wp-cli-bundle"
  exit 2
fi

echo "==> Done. Dump saved at: $DUMP_FILE"
