#!/usr/bin/env bash
set -euo pipefail

BRANCH="${1:-}"
WORKTREE_PATH="${2:-}"
GIT_NAME="${3:-}"
GIT_EMAIL="${4:-}"
CONTROL_REPO="${5:-/srv/warehouse-koperasi/control}"

if [ -z "${BRANCH}" ] || [ -z "${WORKTREE_PATH}" ] || [ -z "${GIT_NAME}" ] || [ -z "${GIT_EMAIL}" ]; then
  echo "Usage: prepare-worktree.sh <branch> <worktree_path> <git_name> <git_email> [control_repo]"
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

BASE_REF="${6:-HEAD}"

# 2. Add worktree
if [ -d "${WORKTREE_PATH}" ]; then
  echo "Worktree path ${WORKTREE_PATH} already exists. Pruning old references..."
  git -C "${CONTROL_REPO}" worktree prune || true
  rm -rf "${WORKTREE_PATH}"
fi

git -C "${CONTROL_REPO}" worktree add -b "${BRANCH}" "${WORKTREE_PATH}" "${BASE_REF}"

# 3. Configure worktree-specific Git identity
git -C "${WORKTREE_PATH}" config --worktree user.useConfigOnly true
git -C "${WORKTREE_PATH}" config --worktree user.name "${GIT_NAME}"
git -C "${WORKTREE_PATH}" config --worktree user.email "${GIT_EMAIL}"

# 4. Copy environment file & setup SQLite testing DB
if [ -f "${CONTROL_REPO}/.env" ]; then
  cp "${CONTROL_REPO}/.env" "${WORKTREE_PATH}/.env"
fi
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

# 5. Verify identity configuration
CONF_NAME=$(git -C "${WORKTREE_PATH}" config --worktree --get user.name)
CONF_EMAIL=$(git -C "${WORKTREE_PATH}" config --worktree --get user.email)

echo "Verified Name: ${CONF_NAME}"
echo "Verified Email: ${CONF_EMAIL}"

if [ "${CONF_NAME}" != "${GIT_NAME}" ] || [ "${CONF_EMAIL}" != "${GIT_EMAIL}" ]; then
  echo "ERROR: Git identity mismatch in worktree!"
  exit 1
fi

echo "Worktree preparation complete."
