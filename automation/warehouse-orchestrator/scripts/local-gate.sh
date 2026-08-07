#!/usr/bin/env bash
set -euo pipefail

WORKTREE_PATH="${1:-$(pwd)}"
EXPECTED_GIT_NAME="${2:-}"
EXPECTED_GIT_EMAIL="${3:-}"

cd "${WORKTREE_PATH}"

echo "=== Running Local Final Gate Verification ==="

# 1. Format Check (Pint)
echo "[Gate 1/5] Checking code formatting (Pint)..."
./automation/warehouse-orchestrator/agent-tools/agent-format

# 2. Static Analysis (PHPStan)
echo "[Gate 2/5] Running static analysis (PHPStan)..."
./automation/warehouse-orchestrator/agent-tools/agent-static-analysis

# 3. Test Suite
echo "[Gate 3/5] Running backend & database tests..."
./automation/warehouse-orchestrator/agent-tools/agent-test-php
./automation/warehouse-orchestrator/agent-tools/agent-test-database

# 4. Commit Identity Check
if [ -n "${EXPECTED_GIT_NAME}" ] && [ -n "${EXPECTED_GIT_EMAIL}" ]; then
  echo "[Gate 4/5] Verifying commit author identity..."
  COMMIT_NAME=$(git log -1 --format="%an")
  COMMIT_EMAIL=$(git log -1 --format="%ae")
  if [ "${COMMIT_NAME}" != "${EXPECTED_GIT_NAME}" ] || [ "${COMMIT_EMAIL}" != "${EXPECTED_GIT_EMAIL}" ]; then
    echo "ERROR: Commit identity mismatch! Expected ${EXPECTED_GIT_NAME} <${EXPECTED_GIT_EMAIL}>, got ${COMMIT_NAME} <${COMMIT_EMAIL}>"
    exit 1
  fi
fi

# 5. Composer Dev Health Check
echo "[Gate 5/5] Verifying composer run dev supervisor health..."
./automation/warehouse-orchestrator/agent-tools/agent-dev-health

echo "=== LOCAL FINAL GATE PASSED ==="
exit 0
