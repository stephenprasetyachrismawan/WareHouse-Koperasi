#!/usr/bin/env bash
set -euo pipefail

DETECTOR_SESSION="warehouse-issue-detector"
DEV_SESSION="warehouse-composer-dev"

echo "=== Tmux Sessions Status ==="
if tmux has-session -t "${DETECTOR_SESSION}" 2>/dev/null; then
  echo "[-] ${DETECTOR_SESSION}: RUNNING"
else
  echo "[-] ${DETECTOR_SESSION}: STOPPED"
fi

if tmux has-session -t "${DEV_SESSION}" 2>/dev/null; then
  echo "[-] ${DEV_SESSION}: RUNNING"
else
  echo "[-] ${DEV_SESSION}: STOPPED"
fi

echo ""
echo "=== Active Tmux Listing ==="
tmux list-sessions 2>/dev/null || echo "No active tmux sessions."
