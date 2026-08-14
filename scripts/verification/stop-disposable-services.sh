#!/usr/bin/env bash
set -euo pipefail

: "${DISPOSABLE_SERVICES_ROOT:?DISPOSABLE_SERVICES_ROOT is required}"
root="${DISPOSABLE_SERVICES_ROOT}"
case "${root}" in
  /tmp/*|/var/tmp/*) ;;
  *) echo 'DISPOSABLE_SERVICES_ROOT must be a child of /tmp or /var/tmp' >&2; exit 2 ;;
esac

marker="${root}/.phase64d-disposable-services"
[[ -f "${marker}" ]] || { echo 'refusing to stop an unmarked directory' >&2; exit 2; }

pg_data="${root}/postgres"
if [[ -d "${pg_data}" ]] && pg_ctl -D "${pg_data}" status >/dev/null 2>&1; then
  pg_ctl -D "${pg_data}" -m fast -w stop >/dev/null
fi

if [[ -f "${root}/redis.pid" ]]; then
  redis_pid="$(<"${root}/redis.pid")"
  redis_port="$(awk -F= '$1 == "redis_port" { print $2 }' "${marker}")"
  redis_cli="$(command -v redis6-cli || command -v redis-cli || true)"
  if [[ "${redis_pid}" =~ ^[0-9]+$ ]] && [[ -r "/proc/${redis_pid}/cmdline" ]] && tr '\0' ' ' < "/proc/${redis_pid}/cmdline" | grep -Fq "redis6-server 127.0.0.1:${redis_port}"; then
    "${redis_cli}" -h 127.0.0.1 -p "${redis_port}" shutdown nosave >/dev/null 2>&1 || kill "${redis_pid}" 2>/dev/null || true
  fi
fi

printf 'DISPOSABLE SERVICES STOPPED: root=%s logs_retained=yes\n' "${root}"
