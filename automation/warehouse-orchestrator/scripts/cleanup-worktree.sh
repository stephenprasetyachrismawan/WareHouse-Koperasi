#!/usr/bin/env bash
set -euo pipefail

WORKTREE_PATH="${1:-}"
BRANCH_NAME="${2:-}"
CONTROL_REPO="${3:-/srv/warehouse-koperasi/control}"

if [ -z "${WORKTREE_PATH}" ]; then
  echo "Usage: cleanup-worktree.sh <worktree_path> [branch_name] [control_repo]"
  exit 1
fi

if [ -z "${CONTROL_REPO}" ] || [ ! -d "${CONTROL_REPO}/.git" ]; then
  CONTROL_REPO="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
fi

echo "=== Cleaning Up Worktree: ${WORKTREE_PATH} ==="

if [ -d "${WORKTREE_PATH}" ]; then
  git -C "${CONTROL_REPO}" worktree remove --force "${WORKTREE_PATH}" 2>/dev/null || rm -rf "${WORKTREE_PATH}"
fi

git -C "${CONTROL_REPO}" worktree prune || true

if [ -n "${BRANCH_NAME}" ]; then
  git -C "${CONTROL_REPO}" branch -D "${BRANCH_NAME}" 2>/dev/null || true
fi

echo "Worktree cleanup complete."
