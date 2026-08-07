#!/usr/bin/env bash
set -euo pipefail

TARGET="${1:-all}"
DETECTOR_SESSION="warehouse-issue-detector"
DEV_SESSION="warehouse-composer-dev"

if [ "${TARGET}" = "detector" ] || [ "${TARGET}" = "all" ]; then
  if tmux has-session -t "${DETECTOR_SESSION}" 2>/dev/null; then
    echo "Stopping tmux session '${DETECTOR_SESSION}'..."
    tmux kill-session -t "${DETECTOR_SESSION}" || true
  else
    echo "Tmux session '${DETECTOR_SESSION}' is not running."
  fi
fi

if [ "${TARGET}" = "dev" ] || [ "${TARGET}" = "all" ]; then
  if tmux has-session -t "${DEV_SESSION}" 2>/dev/null; then
    echo "Stopping tmux session '${DEV_SESSION}'..."
    pkill -f "dev-supervisor.sh" 2>/dev/null || true
    pkill -f "composer dev" 2>/dev/null || true
    tmux kill-session -t "${DEV_SESSION}" || true
  else
    echo "Tmux session '${DEV_SESSION}' is not running."
  fi
fi
