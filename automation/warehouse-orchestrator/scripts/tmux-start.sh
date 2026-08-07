#!/usr/bin/env bash
set -euo pipefail

ORCHESTRATOR_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
REPO_DIR="$(cd "${ORCHESTRATOR_DIR}/../.." && pwd)"

DETECTOR_SESSION="warehouse-issue-detector"
DEV_SESSION="warehouse-composer-dev"

mkdir -p /srv/warehouse-koperasi/logs/detector /srv/warehouse-koperasi/logs/composer-dev /srv/warehouse-koperasi/state

echo "=== Starting Tmux Sessions ==="

# 1. Start Issue Detector Session
if tmux has-session -t "${DETECTOR_SESSION}" 2>/dev/null; then
  echo "Tmux session '${DETECTOR_SESSION}' is already running."
else
  echo "Starting tmux session '${DETECTOR_SESSION}'..."
  tmux new-session -d -s "${DETECTOR_SESSION}" "cd '${ORCHESTRATOR_DIR}' && npm run detector 2>&1 | tee -a /srv/warehouse-koperasi/logs/detector/detector.log"
  echo "Started '${DETECTOR_SESSION}'."
fi

# 2. Start Composer Dev Session
if tmux has-session -t "${DEV_SESSION}" 2>/dev/null; then
  echo "Tmux session '${DEV_SESSION}' is already running."
else
  echo "Starting tmux session '${DEV_SESSION}'..."
  tmux new-session -d -s "${DEV_SESSION}" "bash '${ORCHESTRATOR_DIR}/scripts/dev-supervisor.sh' run 2>&1 | tee -a /srv/warehouse-koperasi/logs/composer-dev/dev-supervisor.log"
  echo "Started '${DEV_SESSION}'."
fi

echo "=== Current Tmux Sessions ==="
tmux list-sessions || true
