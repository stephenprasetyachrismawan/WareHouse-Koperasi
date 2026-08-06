---
name: test-driven-development
description: Test-Driven Development (Red-Green-Refactor) workflow for WareHouse-Koperasi
---

# Test-Driven Development (TDD) Skill

All feature additions and bug fixes must follow strict TDD:

## RED Phase
1. Write or modify a test in `tests/Feature/` or `tests/Unit/` covering the required behavior or bug fix.
2. Execute `./automation/warehouse-orchestrator/agent-tools/agent-tdd-red --filter <TestName>`.
3. Confirm that the test fails for the intended missing logic (not due to syntax error or broken setup).

## GREEN Phase
1. Implement the minimal code change in `app/` or `routes/`.
2. Execute `./automation/warehouse-orchestrator/agent-tools/agent-tdd-green --filter <TestName>`.
3. Confirm the test passes cleanly.

## REFACTOR Phase
1. Clean up implementation details while keeping tests green.
2. Rerun `./automation/warehouse-orchestrator/agent-tools/agent-test-focused --filter <TestName>` to verify no regressions.
