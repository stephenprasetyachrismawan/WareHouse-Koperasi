#!/usr/bin/env bash
set -euo pipefail

: "${RESTORE_ROOT:?RESTORE_ROOT is required}"
: "${RESTORE_CONFIRM:?RESTORE_CONFIRM=YES is required}"

if [[ "${RESTORE_CONFIRM}" != "YES" ]]; then
  echo "restore drill requires RESTORE_CONFIRM=YES" >&2
  exit 1
fi

archive="${1:?usage: restore-drill.sh <unencrypted-archive.tar>}"
if [[ "${archive}" == *.gpg ]]; then
  echo "decrypt the backup with the managed key before an isolated restore drill" >&2
  exit 1
fi

case "$(cd "${RESTORE_ROOT}" 2>/dev/null && pwd || true)" in
  "$(pwd)"|"$(pwd)"/*)
    echo "restore target cannot be the active workspace" >&2
    exit 1
    ;;
esac

if [[ ! -f "${archive}" ]]; then
  echo "backup archive does not exist: ${archive}" >&2
  exit 1
fi

if ! command -v sqlite3 >/dev/null 2>&1; then
  echo "sqlite3 is required for the SQLite restore drill" >&2
  exit 1
fi

if [[ -e "${RESTORE_ROOT}" ]] && [[ -n "$(find "${RESTORE_ROOT}" -mindepth 1 -print -quit 2>/dev/null)" ]]; then
  echo "restore target must be missing or empty" >&2
  exit 1
fi

mkdir -p "${RESTORE_ROOT}/private-storage"
tar -xf "${archive}" -C "${RESTORE_ROOT}"

[[ -f "${RESTORE_ROOT}/database.sqlite" ]] || { echo 'database.sqlite missing from backup' >&2; exit 1; }
[[ -f "${RESTORE_ROOT}/private-storage.tar" ]] || { echo 'private-storage.tar missing from backup' >&2; exit 1; }
[[ -f "${RESTORE_ROOT}/manifest.json" ]] || { echo 'manifest.json missing from backup' >&2; exit 1; }

integrity="$(sqlite3 "${RESTORE_ROOT}/database.sqlite" 'PRAGMA integrity_check;')"
[[ "${integrity}" == 'ok' ]] || { echo "SQLite integrity check failed: ${integrity}" >&2; exit 1; }
tar -xf "${RESTORE_ROOT}/private-storage.tar" -C "${RESTORE_ROOT}/private-storage"

printf 'RESTORE VERIFIED: database_integrity=%s private_storage=restored manifest=present\n' "${integrity}"
