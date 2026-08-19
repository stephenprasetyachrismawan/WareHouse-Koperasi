#!/usr/bin/env bash
# Deterministic deployment of an already-built, already-published GHCR image
# to the development VPS. This script never builds anything -- it only
# pulls, migrates, and swaps containers. Run from /srv/warehouse-koperasi/deploy
# on the VPS (this file is synced there, not executed from a git checkout).
#
# Usage: deploy-development.sh <app-image-ref> <web-image-ref>
#   e.g. deploy-development.sh \
#          ghcr.io/stephenprasetyachrismawan/warehouse-koperasi-app@sha256:... \
#          ghcr.io/stephenprasetyachrismawan/warehouse-koperasi-web@sha256:...
#
# Both image refs MUST be digest-pinned (contain "@sha256:"). `latest` or a
# mutable tag is refused -- deployment truth is always a digest.

set -euo pipefail

DEPLOY_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$DEPLOY_DIR"

LOCK_FILE="$DEPLOY_DIR/.deploy.lock"
STATE_FILE="$DEPLOY_DIR/current-digest.env"
PREVIOUS_STATE_FILE="$DEPLOY_DIR/previous-digest.env"
COMPOSE_FILE="$DEPLOY_DIR/compose.yaml"
ENV_FILE="$DEPLOY_DIR/.env"

log() { printf '[%s] %s\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)" "$*"; }
fail() { log "DEPLOY FAILED: $*"; exit 1; }

[ -f "$COMPOSE_FILE" ] || fail "compose file not found at $COMPOSE_FILE"
[ -f "$ENV_FILE" ] || fail ".env not found at $ENV_FILE -- copy from .env.docker.example and fill in real values first"

NEW_APP_IMAGE="${1:?usage: deploy-development.sh <app-image-ref@sha256:...> <web-image-ref@sha256:...>}"
NEW_WEB_IMAGE="${2:?usage: deploy-development.sh <app-image-ref@sha256:...> <web-image-ref@sha256:...>}"

case "$NEW_APP_IMAGE" in *"@sha256:"*) ;; *) fail "APP image ref must be digest-pinned (contain @sha256:), got: $NEW_APP_IMAGE" ;; esac
case "$NEW_WEB_IMAGE" in *"@sha256:"*) ;; *) fail "WEB image ref must be digest-pinned (contain @sha256:), got: $NEW_WEB_IMAGE" ;; esac

exec 9>"$LOCK_FILE"
if ! flock -n 9; then
    fail "another deployment is already in progress (lock held on $LOCK_FILE)"
fi
log "deployment lock acquired"

# --- record current digest before touching anything ------------------------
if [ -f "$STATE_FILE" ]; then
    cp "$STATE_FILE" "$PREVIOUS_STATE_FILE"
    # shellcheck disable=SC1090
    . "$PREVIOUS_STATE_FILE"
    log "previous deployment recorded: APP=${APP_IMAGE:-none} WEB=${WEB_IMAGE:-none}"
else
    log "no previous deployment state found (first deploy on this host)"
    : > "$PREVIOUS_STATE_FILE"
fi

# --- pull the exact requested images -----------------------------------------
export APP_IMAGE="$NEW_APP_IMAGE"
export WEB_IMAGE="$NEW_WEB_IMAGE"
log "pulling APP=$APP_IMAGE"
log "pulling WEB=$WEB_IMAGE"
docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" pull app web \
    || fail "image pull failed"

# --- backup persistent SQLite before migrating -------------------------------
BACKUP_DIR="$DEPLOY_DIR/sqlite-backups"
mkdir -p "$BACKUP_DIR"
BACKUP_FILE="$BACKUP_DIR/database.sqlite.$(date -u +%Y%m%dT%H%M%SZ).bak"
SQLITE_VOLUME="warehouse-koperasi-sqlite"

if docker volume inspect "$SQLITE_VOLUME" >/dev/null 2>&1; then
    docker run --rm \
        -v "$SQLITE_VOLUME":/data:ro \
        -v "$BACKUP_DIR":/backup \
        alpine:3.20 \
        sh -c 'if [ -f /data/database.sqlite ]; then cp /data/database.sqlite "/backup/$(basename '"$BACKUP_FILE"')"; fi' \
        || fail "SQLite backup failed -- refusing to migrate without a backup"

    if [ -f "$BACKUP_FILE" ]; then
        # Verify the backup is a well-formed SQLite file, not a truncated copy.
        docker run --rm -v "$BACKUP_DIR":/backup:ro alpine:3.20 \
            sh -c "apk add --no-cache sqlite >/dev/null 2>&1; sqlite3 /backup/$(basename "$BACKUP_FILE") 'PRAGMA integrity_check;'" \
            | grep -q "^ok$" || fail "SQLite backup integrity check failed for $BACKUP_FILE"
        log "SQLite backup verified: $BACKUP_FILE"
    else
        log "no existing database.sqlite to back up yet (first deploy)"
    fi
else
    log "sqlite-data volume does not exist yet (first deploy) -- nothing to back up"
fi

# --- migrate in a one-shot container -----------------------------------------
log "running migrations (php artisan migrate --force)"
docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" run --rm app migrate \
    || fail "migration failed -- database was backed up at $BACKUP_FILE, containers not yet swapped"

# --- swap application containers ---------------------------------------------
log "starting containers with new images"
docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" up -d --remove-orphans \
    || fail "container startup failed"

# --- health verification ------------------------------------------------------
HEALTH_URL="${DEPLOY_HEALTH_BASE_URL:-http://127.0.0.1:8000}"
RETRIES=15
SLEEP_SECONDS=2

wait_for_200() {
    local path="$1"
    local i=0
    while [ "$i" -lt "$RETRIES" ]; do
        if curl -fsS -o /dev/null -w '%{http_code}' "$HEALTH_URL$path" 2>/dev/null | grep -q '^200$'; then
            log "$path OK"
            return 0
        fi
        i=$((i + 1))
        sleep "$SLEEP_SECONDS"
    done
    return 1
}

if ! wait_for_200 /health/live; then
    log "DEPLOYMENT FAILED: /health/live did not return 200 after $((RETRIES * SLEEP_SECONDS))s"
    "$DEPLOY_DIR/rollback-development.sh" "health/live check failed"
    exit 1
fi

if ! wait_for_200 /health/ready; then
    log "DEPLOYMENT FAILED: /health/ready did not return 200 after $((RETRIES * SLEEP_SECONDS))s"
    "$DEPLOY_DIR/rollback-development.sh" "health/ready check failed"
    exit 1
fi

# --- runtime process verification --------------------------------------------
UNHEALTHY=$(docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" ps --format '{{.Name}} {{.State}}' \
    | awk '$2 != "running" { print }')
if [ -n "$UNHEALTHY" ]; then
    log "DEPLOYMENT FAILED: containers not running: $UNHEALTHY"
    "$DEPLOY_DIR/rollback-development.sh" "container(s) not running: $UNHEALTHY"
    exit 1
fi
log "all containers running"

# --- Reverb verification (port + process only here; real WS handshake is a
#     separate, explicit verification step run after this script) -----------
if ! docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" exec -T reverb \
        sh -c 'exit 0' >/dev/null 2>&1; then
    log "DEPLOYMENT FAILED: reverb container is not responsive"
    "$DEPLOY_DIR/rollback-development.sh" "reverb container not responsive"
    exit 1
fi
log "reverb container responsive"

# --- minimal smoke -------------------------------------------------------------
if ! curl -fsS -o /dev/null -w '%{http_code}' "$HEALTH_URL/" 2>/dev/null | grep -q '^200$'; then
    log "DEPLOYMENT FAILED: homepage smoke check did not return 200"
    "$DEPLOY_DIR/rollback-development.sh" "homepage smoke check failed"
    exit 1
fi
log "homepage smoke check OK"

# --- record success ------------------------------------------------------------
{
    echo "APP_IMAGE=$NEW_APP_IMAGE"
    echo "WEB_IMAGE=$NEW_WEB_IMAGE"
    echo "DEPLOYED_AT=$(date -u +%Y-%m-%dT%H:%M:%SZ)"
} > "$STATE_FILE"

log "DEPLOYMENT SUCCEEDED: APP=$NEW_APP_IMAGE WEB=$NEW_WEB_IMAGE"
