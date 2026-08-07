#!/usr/bin/env bash
set -euo pipefail

STATE_DIR="/srv/warehouse-koperasi/state"
LOG_DIR="/srv/warehouse-koperasi/logs/composer-dev"
mkdir -p "${STATE_DIR}" "${LOG_DIR}"

TARGET_FILE="${STATE_DIR}/active-dev-worktree"
HEALTH_FILE="${STATE_DIR}/dev-health.json"
MARKER_FILE="${STATE_DIR}/dev-startup-marker.txt"
SUPERVISOR_LOG="${LOG_DIR}/dev-supervisor.log"

DEFAULT_TARGET="/srv/warehouse-koperasi/control"
if [ ! -d "${DEFAULT_TARGET}" ]; then
  DEFAULT_TARGET="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
fi

if [ ! -f "${TARGET_FILE}" ]; then
  echo "${DEFAULT_TARGET}" > "${TARGET_FILE}"
fi

write_health() {
  local status="$1"
  local pid="$2"
  local target="$3"
  local restart_count="$4"
  local fatal_logs="$5"
  local ts
  ts=$(date -u +"%Y-%m-%dT%H:%M:%SZ")

  cat << EOF > "${HEALTH_FILE}"
{
  "timestamp": "${ts}",
  "status": "${status}",
  "composerPid": ${pid:-0},
  "targetWorktree": "${target}",
  "restartCount": ${restart_count:-0},
  "fatalLogCount": ${fatal_logs:-0}
}
EOF
}

cleanup_child_processes() {
  echo "[dev-supervisor] Cleaning up child processes..." >> "${SUPERVISOR_LOG}"
  # composer dev (via concurrently) spawns serve/queue/pail/vite as
  # grandchildren, not direct children of this script — pkill -P $$ alone
  # never reaches them, which is why ports stayed stuck across restarts.
  # COMPOSER_PID is started via setsid, so it's also its process-group
  # leader: killing the negative PID takes the whole tree down in one shot.
  if [ -n "${COMPOSER_PID:-}" ]; then
    kill -TERM -- "-${COMPOSER_PID}" 2>/dev/null || true
    sleep 1
    kill -9 -- "-${COMPOSER_PID}" 2>/dev/null || true
  fi
  pkill -P $$ 2>/dev/null || true
  pkill -9 -f "composer dev" 2>/dev/null || true
  pkill -9 -f "php artisan serve" 2>/dev/null || true
  fuser -k 8000/tcp 2>/dev/null || true
  sleep 2
}

count_fatal_errors() {
  local count
  # "Address already in use" is excluded: php artisan serve retries on the
  # next port automatically and does come up healthy, so that line is a
  # transient retry notice, not a fatal condition — treating it as fatal
  # was permanently flagging otherwise-healthy cycles as UNHEALTHY/FAILED.
  count=$(grep -E -c "Fatal error|Uncaught|npm ERR!|Vite error|Build failed" "${SUPERVISOR_LOG}" 2>/dev/null | awk '{s+=$1} END {print s+0}')
  echo "${count}"
}

run_supervisor_loop() {
  local current_target=""
  local restart_count=0

  trap cleanup_child_processes EXIT INT TERM

  while true; do
    local new_target
    new_target=$(cat "${TARGET_FILE}" 2>/dev/null || echo "${DEFAULT_TARGET}")
    
    if [ ! -d "${new_target}" ]; then
      new_target="${DEFAULT_TARGET}"
    fi

    echo "[dev-supervisor] Supervisor started at $(date) for ${new_target}" > "${SUPERVISOR_LOG}"
    current_target="${new_target}"

    date +%s > "${MARKER_FILE}"

    # Same race class as the .env bootstrap below: current_target can vanish
    # between the directory check above and here if a worktree is being
    # deleted/recreated concurrently. Skip this cycle instead of exiting.
    if ! cd "${current_target}" 2>>"${SUPERVISOR_LOG}"; then
      echo "[dev-supervisor] ${current_target} disappeared before startup; retrying shortly..." >> "${SUPERVISOR_LOG}"
      sleep 2
      continue
    fi

    # Ensure SQLite database file exists in target worktree
    mkdir -p "${current_target}/database" 2>>"${SUPERVISOR_LOG}" || true
    touch "${current_target}/database/database.sqlite" 2>>"${SUPERVISOR_LOG}" || true

    # .env is gitignored, so it never comes from git reset/clean or from
    # prepare-worktree.sh's file copies — a target missing it boots far
    # enough to look alive, then fatals on the first real request
    # (MissingAppKeyException), which is invisible until agent-dev-health
    # actually gets hit. Bootstrap it unconditionally so this can't recur.
    # This whole script runs under `set -e`; a target caught mid git-worktree
    # recreation (its .env.example briefly absent) previously took the
    # *entire* supervisor process down on a single failed `cp`, taking the
    # tmux session with it — the opposite of "always stays on". Every step
    # in this block is now independently guarded so a transient miss here
    # just skips bootstrapping this cycle instead of killing the supervisor.
    if [ ! -f "${current_target}/.env" ] && [ -f "${current_target}/.env.example" ]; then
      echo "[dev-supervisor] No .env in ${current_target}; bootstrapping from .env.example..." >> "${SUPERVISOR_LOG}"
      if cp "${current_target}/.env.example" "${current_target}/.env" 2>>"${SUPERVISOR_LOG}"; then
        # .env.example's stock DB_CONNECTION is pgsql, but this project runs
        # on sqlite everywhere (no Postgres server exists on this box). Plain
        # `php artisan <command>` calls (migrate:fresh in agent-test-database,
        # for instance) read .env directly and aren't covered by phpunit.xml's
        # test-runner env overrides, so a wrong file here breaks them even
        # though `php artisan test` itself stays fine. Without any .env at all
        # Laravel's own config/database.php default (sqlite) silently wins —
        # match that explicitly now that a file exists to override it.
        sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=sqlite/' "${current_target}/.env" 2>>"${SUPERVISOR_LOG}" || true
        sed -i '/^DB_HOST=/d; /^DB_PORT=/d; /^DB_USERNAME=/d; /^DB_PASSWORD=/d' "${current_target}/.env" 2>>"${SUPERVISOR_LOG}" || true
        sed -i "s|^DB_DATABASE=.*|DB_DATABASE=${current_target}/database/database.sqlite|" "${current_target}/.env" 2>>"${SUPERVISOR_LOG}" || true
        php "${current_target}/artisan" key:generate --force >> "${SUPERVISOR_LOG}" 2>&1 || true
      fi
    fi

    export APP_ENV=local
    export DB_CONNECTION=sqlite
    export DB_DATABASE="${current_target}/database/database.sqlite"

    write_health "STARTING" $$ "${current_target}" "${restart_count}" 0

    # Start composer dev in its own process group (setsid) so the whole
    # tree — serve/queue/pail/vite — can be killed together on cleanup.
    setsid composer dev >> "${SUPERVISOR_LOG}" 2>&1 &
    COMPOSER_PID=$!

    # 10 second warm-up check
    local warmup_remaining=10
    while [ ${warmup_remaining} -gt 0 ]; do
      sleep 1
      warmup_remaining=$((warmup_remaining - 1))
      
      local target_check
      target_check=$(cat "${TARGET_FILE}" 2>/dev/null || echo "${current_target}")
      if [ "${target_check}" != "${current_target}" ]; then
        echo "[dev-supervisor] Target worktree changed during warmup. Restarting..." >> "${SUPERVISOR_LOG}"
        kill -9 "${COMPOSER_PID}" 2>/dev/null || true
        wait "${COMPOSER_PID}" 2>/dev/null || true
        break
      fi
    done

    if kill -0 "${COMPOSER_PID}" 2>/dev/null; then
      local fatal_count
      fatal_count=$(count_fatal_errors)

      if [ "${fatal_count}" -eq 0 ]; then
        write_health "HEALTHY" "${COMPOSER_PID}" "${current_target}" "${restart_count}" 0
      else
        write_health "UNHEALTHY" "${COMPOSER_PID}" "${current_target}" "${restart_count}" "${fatal_count}"
      fi
    else
      restart_count=$((restart_count + 1))
      write_health "FAILED" 0 "${current_target}" "${restart_count}" 1
      echo "[dev-supervisor] Process exited. Restarting in 3s..." >> "${SUPERVISOR_LOG}"
      sleep 3
    fi

    # Monitor loop
    while kill -0 "${COMPOSER_PID}" 2>/dev/null; do
      sleep 5
      local target_check
      target_check=$(cat "${TARGET_FILE}" 2>/dev/null || echo "${current_target}")
      if [ "${target_check}" != "${current_target}" ]; then
        echo "[dev-supervisor] Target worktree changed. Switching to ${target_check}..." >> "${SUPERVISOR_LOG}"
        cleanup_child_processes
        break
      fi

      local fatal_count
      fatal_count=$(count_fatal_errors)
      if [ "${fatal_count}" -eq 0 ]; then
        write_health "HEALTHY" "${COMPOSER_PID}" "${current_target}" "${restart_count}" 0
      else
        write_health "UNHEALTHY" "${COMPOSER_PID}" "${current_target}" "${restart_count}" "${fatal_count}"
      fi
    done

    sleep 2
  done
}

case "${1:-start}" in
  status)
    if [ -f "${HEALTH_FILE}" ]; then
      cat "${HEALTH_FILE}"
    else
      echo '{"status":"STOPPED"}'
    fi
    ;;
  run)
    run_supervisor_loop
    ;;
  *)
    run_supervisor_loop
    ;;
esac
