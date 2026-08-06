#!/usr/bin/env bash
set -euo pipefail

BRANCH="${1:-}"
WORKTREE_PATH="${2:-}"
GIT_NAME="${3:-}"
GIT_EMAIL="${4:-}"
CONTROL_REPO="${5:-}"
BASE_REF="${6:-HEAD}"

if [ -z "${BRANCH}" ] || [ -z "${WORKTREE_PATH}" ]; then
  echo "Usage: prepare-worktree.sh <branch> <worktree_path> [git_name] [git_email] [control_repo] [base_ref]"
  exit 1
fi

if [ -z "${CONTROL_REPO}" ] || [ ! -d "${CONTROL_REPO}/.git" ]; then
  CONTROL_REPO="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
fi

echo "=== Preparing Worktree: ${WORKTREE_PATH} ==="
echo "Branch: ${BRANCH}"
echo "Identity: ${GIT_NAME} <${GIT_EMAIL}>"

# 1. Enable worktreeConfig extension on control repository
git -C "${CONTROL_REPO}" config extensions.worktreeConfig true || true

# 2. Safely remove existing worktree and stale branch reference
if [ -d "${WORKTREE_PATH}" ]; then
  echo "Worktree path ${WORKTREE_PATH} already exists. Removing worktree..."
  git -C "${CONTROL_REPO}" worktree remove --force "${WORKTREE_PATH}" 2>/dev/null || true
  rm -rf "${WORKTREE_PATH}"
fi

git -C "${CONTROL_REPO}" worktree prune || true
git -C "${CONTROL_REPO}" branch -D "${BRANCH}" 2>/dev/null || true

# 3. Add fresh worktree
git -C "${CONTROL_REPO}" worktree add -b "${BRANCH}" "${WORKTREE_PATH}" "${BASE_REF}"

# 4. Configure worktree-specific Git identity
git -C "${WORKTREE_PATH}" config --worktree user.useConfigOnly true
if [ -n "${GIT_NAME}" ]; then
  git -C "${WORKTREE_PATH}" config --worktree user.name "${GIT_NAME}"
fi
if [ -n "${GIT_EMAIL}" ]; then
  git -C "${WORKTREE_PATH}" config --worktree user.email "${GIT_EMAIL}"
fi

# Verify worktree configuration
VERIFIED_NAME=$(git -C "${WORKTREE_PATH}" config --get user.name || echo "unset")
VERIFIED_EMAIL=$(git -C "${WORKTREE_PATH}" config --get user.email || echo "unset")
echo "Verified Name: ${VERIFIED_NAME}"
echo "Verified Email: ${VERIFIED_EMAIL}"

# Ensure database directory and sqlite file exist inside worktree
mkdir -p "${WORKTREE_PATH}/database"
touch "${WORKTREE_PATH}/database/database.sqlite"

# Symlink/copy vendor, node_modules, automation, and .agents for speed & completeness
if [ -d "${CONTROL_REPO}/automation" ] && [ ! -d "${WORKTREE_PATH}/automation" ]; then
  cp -r "${CONTROL_REPO}/automation" "${WORKTREE_PATH}/automation"
fi
if [ -d "${CONTROL_REPO}/.agents" ] && [ ! -d "${WORKTREE_PATH}/.agents" ]; then
  cp -r "${CONTROL_REPO}/.agents" "${WORKTREE_PATH}/.agents"
fi
if [ -d "${CONTROL_REPO}/.agent" ] && [ ! -d "${WORKTREE_PATH}/.agent" ]; then
  cp -r "${CONTROL_REPO}/.agent" "${WORKTREE_PATH}/.agent"
fi
if [ -d "${CONTROL_REPO}/vendor" ] && [ ! -d "${WORKTREE_PATH}/vendor" ]; then
  cp -r "${CONTROL_REPO}/vendor" "${WORKTREE_PATH}/vendor"
fi
if [ -d "${CONTROL_REPO}/node_modules" ] && [ ! -d "${WORKTREE_PATH}/node_modules" ]; then
  cp -r "${CONTROL_REPO}/node_modules" "${WORKTREE_PATH}/node_modules"
fi

echo "Worktree preparation complete."
exit 0
