#!/usr/bin/env bash
# Restores the previously-recorded known-good image digests and verifies
# health. Invoked automatically by deploy-development.sh on a failed
# deployment, or manually: rollback-development.sh "<reason>"
#
# Rollback restores the application IMAGE only. It does NOT roll back the
# database schema -- a migration that already committed stays committed.
# If the new deployment's migration was destructive/incompatible with the
# previous image, this script will bring back old code running against a
# newer schema, which may itself fail health checks. That combination is
# reported honestly, not silently treated as success.

set -euo pipefail

DEPLOY_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$DEPLOY_DIR"

REASON="${1:-manual rollback}"
STATE_FILE="$DEPLOY_DIR/current-digest.env"
PREVIOUS_STATE_FILE="$DEPLOY_DIR/previous-digest.env"
COMPOSE_FILE="$DEPLOY_DIR/compose.yaml"
ENV_FILE="$DEPLOY_DIR/.env"

log() { printf '[%s] %s\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)" "$*"; }

log "ROLLBACK STARTING — reason: $REASON"

if [ ! -s "$PREVIOUS_STATE_FILE" ]; then
    log "ROLLBACK FAILED: no previous deployment digest recorded (this was the first deploy on this host) -- nothing to roll back to. Manual intervention required."
    exit 1
fi

# shellcheck disable=SC1090
. "$PREVIOUS_STATE_FILE"

if [ -z "${APP_IMAGE:-}" ] || [ -z "${WEB_IMAGE:-}" ]; then
    log "ROLLBACK FAILED: previous-digest.env is malformed (missing APP_IMAGE/WEB_IMAGE)"
    exit 1
fi

log "restoring previous images: APP=$APP_IMAGE WEB=$WEB_IMAGE"
export APP_IMAGE
export WEB_IMAGE

docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" up -d --remove-orphans app web queue scheduler reverb \
    || {
        log "ROLLBACK FAILED: could not start previous-image containers"
        exit 1
    }

HEALTH_URL="${DEPLOY_HEALTH_BASE_URL:-http://127.0.0.1:8000}"
RETRIES=15
SLEEP_SECONDS=2

wait_for_200() {
    local path="$1"
    local i=0
    while [ "$i" -lt "$RETRIES" ]; do
        if curl -fsS -o /dev/null -w '%{http_code}' "$HEALTH_URL$path" 2>/dev/null | grep -q '^200$'; then
            return 0
        fi
        i=$((i + 1))
        sleep "$SLEEP_SECONDS"
    done
    return 1
}

if ! wait_for_200 /health/live || ! wait_for_200 /health/ready; then
    log "ROLLBACK FAILED: previous image restored but health checks still fail -- this may indicate a schema incompatibility from the failed deploy's migration. Manual investigation required. DO NOT assume the system is healthy."
    exit 1
fi

# Restore state file to reflect the rolled-back-to digest as current.
cp "$PREVIOUS_STATE_FILE" "$STATE_FILE"

log "DEPLOYMENT FAILED — ROLLBACK SUCCEEDED (reason for original failure: $REASON)"
