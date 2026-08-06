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
  pkill -P $$ 2>/dev/null || true
  pkill -f "composer dev" 2>/dev/null || true
  pkill -f "php artisan serve" 2>/dev/null || true
  sleep 2
}

run_supervisor_loop() {
  local current_target=""
  local restart_count=0

  echo "[dev-supervisor] Supervisor started at $(date)" >> "${SUPERVISOR_LOG}"

  trap cleanup_child_processes EXIT INT TERM

  while true; do
    local new_target
    new_target=$(cat "${TARGET_FILE}" 2>/dev/null || echo "${DEFAULT_TARGET}")
    
    if [ ! -d "${new_target}" ]; then
      new_target="${DEFAULT_TARGET}"
    fi

    echo "[dev-supervisor] Target worktree: ${new_target}" >> "${SUPERVISOR_LOG}"
    current_target="${new_target}"

    # Record startup marker timestamp
    date +%s > "${MARKER_FILE}"

    cd "${current_target}"

    export APP_ENV=local
    export DB_CONNECTION=sqlite
    export DB_DATABASE="${current_target}/database/database.sqlite"

    write_health "STARTING" $$ "${current_target}" "${restart_count}" 0

    # Start composer dev in background
    composer dev >> "${SUPERVISOR_LOG}" 2>&1 &
    COMPOSER_PID=$!

    # Give warm-up period
    local warmup_remaining=45
    while [ ${warmup_remaining} -gt 0 ]; do
      sleep 1
      warmup_remaining=$((warmup_remaining - 1))
      
      # Check target file change
      local target_check
      target_check=$(cat "${TARGET_FILE}" 2>/dev/null || echo "${current_target}")
      if [ "${target_check}" != "${current_target}" ]; then
        echo "[dev-supervisor] Target worktree changed during warmup. Restarting..." >> "${SUPERVISOR_LOG}"
        kill -9 "${COMPOSER_PID}" 2>/dev/null || true
        wait "${COMPOSER_PID}" 2>/dev/null || true
        break
      fi
    done

    # Check process health and log errors after marker
    if kill -0 "${COMPOSER_PID}" 2>/dev/null; then
      local fatal_count=0
      if [ -f "${MARKER_FILE}" ]; then
        fatal_count=$(grep -E -c "Fatal error|Uncaught|SQLSTATE|npm ERR!|Vite error|Build failed|Address already in use" "${SUPERVISOR_LOG}" || echo "0")
      fi

      if [ "${fatal_count}" -eq 0 ]; then
        write_health "HEALTHY" "${COMPOSER_PID}" "${current_target}" "${restart_count}" "${fatal_count}"
      else
        write_health "UNHEALTHY" "${COMPOSER_PID}" "${current_target}" "${restart_count}" "${fatal_count}"
      fi
    else
      restart_count=$((restart_count + 1))
      write_health "FAILED" 0 "${current_target}" "${restart_count}" 1
      echo "[dev-supervisor] Process exited unexpectedly. Restarting in 5s..." >> "${SUPERVISOR_LOG}"
      sleep 5
    fi

    # Monitor loop while process is running
    while kill -0 "${COMPOSER_PID}" 2>/dev/null; do
      sleep 5
      local target_check
      target_check=$(cat "${TARGET_FILE}" 2>/dev/null || echo "${current_target}")
      if [ "${target_check}" != "${current_target}" ]; then
        echo "[dev-supervisor] Target worktree changed. Switching to ${target_check}..." >> "${SUPERVISOR_LOG}"
        kill -15 "${COMPOSER_PID}" 2>/dev/null || true
        sleep 2
        kill -9 "${COMPOSER_PID}" 2>/dev/null || true
        wait "${COMPOSER_PID}" 2>/dev/null || true
        break
      fi

      local fatal_count=0
      fatal_count=$(grep -E -c "Fatal error|Uncaught|SQLSTATE|npm ERR!|Vite error|Build failed|Address already in use" "${SUPERVISOR_LOG}" || echo "0")
      if [ "${fatal_count}" -eq 0 ]; then
        write_health "HEALTHY" "${COMPOSER_PID}" "${current_target}" "${restart_count}" "${fatal_count}"
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
