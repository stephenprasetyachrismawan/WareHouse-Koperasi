#!/usr/bin/env bash
set -euo pipefail

source_root="${1:-.}"
config_file="${GITLEAKS_CONFIG:-${source_root}/.gitleaks.toml}"
scanner="${SECRET_SCANNER_BIN:-gitleaks}"

if ! command -v "${scanner}" >/dev/null 2>&1; then
  echo 'SECRET SCAN: NOT VERIFIED (approved scanner is unavailable)' >&2
  exit 2
fi

if [[ ! -f "${config_file}" ]]; then
  echo "SECRET SCAN: NOT VERIFIED (config not found: ${config_file})" >&2
  exit 2
fi

"${scanner}" detect \
  --source "${source_root}" \
  --config "${config_file}" \
  --redact \
  --no-banner \
  --exit-code 1

echo 'SECRET SCAN: PASS'
