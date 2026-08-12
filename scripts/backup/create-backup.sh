#!/usr/bin/env bash
set -euo pipefail

: "${BACKUP_ROOT:?BACKUP_ROOT is required}"
: "${BACKUP_DATABASE_PATH:?BACKUP_DATABASE_PATH is required}"
: "${BACKUP_PRIVATE_ROOT:?BACKUP_PRIVATE_ROOT is required}"

if [[ ! -f "${BACKUP_DATABASE_PATH}" ]]; then
  echo "database backup source does not exist: ${BACKUP_DATABASE_PATH}" >&2
  exit 1
fi

if [[ ! -d "${BACKUP_PRIVATE_ROOT}" ]]; then
  echo "private storage root does not exist: ${BACKUP_PRIVATE_ROOT}" >&2
  exit 1
fi

if ! command -v sqlite3 >/dev/null 2>&1; then
  echo "sqlite3 is required for the SQLite backup path" >&2
  exit 1
fi

if [[ "${BACKUP_REQUIRE_ENCRYPTION:-true}" == "true" && -z "${BACKUP_GPG_RECIPIENT:-}" ]]; then
  echo "encryption is required; set BACKUP_GPG_RECIPIENT or use a managed encrypted backup path" >&2
  exit 1
fi

mkdir -p "${BACKUP_ROOT}"
timestamp="$(date -u +%Y%m%dT%H%M%SZ)"
staging="$(mktemp -d "${BACKUP_ROOT}/.staging.XXXXXX")"
archive="${BACKUP_ROOT}/warehouse-${timestamp}.tar"
cleanup() { rm -rf "${staging}"; }
trap cleanup EXIT

sqlite3 "${BACKUP_DATABASE_PATH}" ".backup '${staging}/database.sqlite'"
tar -C "${BACKUP_PRIVATE_ROOT}" -cf "${staging}/private-storage.tar" .

cat > "${staging}/manifest.json" <<EOF
{"format":"warehouse-backup-v1","created_at":"${timestamp}","database":"sqlite","private_storage":true,"encrypted":$(if [[ -n "${BACKUP_GPG_RECIPIENT:-}" ]]; then echo true; else echo false; fi)}
EOF

tar -C "${staging}" -cf "${archive}" database.sqlite private-storage.tar manifest.json

if [[ -n "${BACKUP_GPG_RECIPIENT:-}" ]]; then
  if ! command -v gpg >/dev/null 2>&1; then
    echo "gpg is required when BACKUP_GPG_RECIPIENT is set" >&2
    exit 1
  fi

  gpg --batch --yes --trust-model always --recipient "${BACKUP_GPG_RECIPIENT}" --encrypt --output "${archive}.gpg" "${archive}"
  rm -f "${archive}"
  archive="${archive}.gpg"
else
  echo "UNENCRYPTED LOCAL DRILL: this archive must not be used for production recovery"
fi

printf 'BACKUP_ARCHIVE=%s\n' "${archive}"
