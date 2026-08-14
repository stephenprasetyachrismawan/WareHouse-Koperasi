#!/usr/bin/env bash
set -euo pipefail

# Starts only loopback-bound, disposable services for Phase 6.4D evidence.
# The root must be explicitly supplied and live below /tmp or /var/tmp.
: "${DISPOSABLE_SERVICES_ROOT:?DISPOSABLE_SERVICES_ROOT is required}"

root="${DISPOSABLE_SERVICES_ROOT}"
case "${root}" in
  /tmp/*|/var/tmp/*) ;;
  *) echo 'DISPOSABLE_SERVICES_ROOT must be a child of /tmp or /var/tmp' >&2; exit 2 ;;
esac

if [[ -e "${root}" ]]; then
  if [[ ! -d "${root}" || -n "$(find "${root}" -mindepth 1 -print -quit 2>/dev/null)" ]]; then
    echo 'DISPOSABLE_SERVICES_ROOT must be a missing or empty directory' >&2
    exit 2
  fi
else
  mkdir -p "${root}"
fi

pg_port="${DISPOSABLE_PG_PORT:-55432}"
redis_port="${DISPOSABLE_REDIS_PORT:-56379}"
db_user="${DISPOSABLE_DB_USER:-phase64d}"
db_name="${DISPOSABLE_DB_NAME:-phase64d}"

cleanup_on_error() {
  if [[ -d "${root}/postgres" ]] && pg_ctl -D "${root}/postgres" status >/dev/null 2>&1; then
    pg_ctl -D "${root}/postgres" -m immediate -w stop >/dev/null 2>&1 || true
  fi

  if [[ -f "${root}/redis.pid" ]]; then
    redis_pid="$(<"${root}/redis.pid")"
    if [[ "${redis_pid}" =~ ^[0-9]+$ ]] && [[ -r "/proc/${redis_pid}/cmdline" ]]; then
      kill "${redis_pid}" 2>/dev/null || true
    fi
  fi
}
trap cleanup_on_error ERR

for value in "${pg_port}" "${redis_port}"; do
  [[ "${value}" =~ ^[0-9]+$ ]] || { echo 'ports must be numeric' >&2; exit 2; }
done

command -v initdb >/dev/null || { echo 'initdb is required' >&2; exit 2; }
command -v pg_ctl >/dev/null || { echo 'pg_ctl is required' >&2; exit 2; }
redis_server="$(command -v redis6-server || command -v redis-server || true)"
redis_cli="$(command -v redis6-cli || command -v redis-cli || true)"
[[ -n "${redis_server}" && -n "${redis_cli}" ]] || { echo 'Redis server and client are required' >&2; exit 2; }

pg_data="${root}/postgres"
initdb -D "${pg_data}" -U "${db_user}" --auth=trust --no-locale >/dev/null
mkdir -p "${root}/socket"
pg_ctl -D "${pg_data}" -o "-h 127.0.0.1 -p ${pg_port} -k ${root}/socket -c listen_addresses=127.0.0.1" -l "${root}/postgres.log" -w start >/dev/null
createdb -h 127.0.0.1 -p "${pg_port}" -U "${db_user}" "${db_name}"

"${redis_server}" \
  --bind 127.0.0.1 \
  --protected-mode yes \
  --port "${redis_port}" \
  --save '' \
  --appendonly no \
  --dir "${root}" \
  --daemonize yes \
  --pidfile "${root}/redis.pid" \
  --logfile "${root}/redis.log" >/dev/null

printf 'phase=6.4D\npg_port=%s\nredis_port=%s\ndb_user=%s\ndb_name=%s\n' \
  "${pg_port}" "${redis_port}" "${db_user}" "${db_name}" \
  > "${root}/.phase64d-disposable-services"

printf 'DISPOSABLE_SERVICES_ROOT=%s\n' "${root}"
printf 'DB_CONNECTION=pgsql\nDB_HOST=127.0.0.1\nDB_PORT=%s\nDB_DATABASE=%s\nDB_USERNAME=%s\nDB_PASSWORD=\n' "${pg_port}" "${db_name}" "${db_user}"
printf 'REDIS_HOST=127.0.0.1\nREDIS_PORT=%s\n' "${redis_port}"
printf 'DISPOSABLE SERVICES STARTED: postgres=%s redis=%s\n' "${pg_port}" "${redis_port}"
trap - ERR
