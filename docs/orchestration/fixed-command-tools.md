# Fixed Command Tools Layer

Antigravity must execute fixed deterministic wrapper tools located in `./automation/warehouse-orchestrator/agent-tools/`:

- `agent-preflight`: Verifies repository readiness.
- `agent-baseline`: Runs baseline test check.
- `agent-tdd-red`: Confirms test failure during RED phase.
- `agent-tdd-green`: Confirms test pass during GREEN phase.
- `agent-test-focused`: Runs focused test filter.
- `agent-test-unit`: Runs unit test suite.
- `agent-test-php`: Runs backend PHP test suite.
- `agent-test-database`: Runs isolated database integration tests.
- `agent-format`: Runs Pint formatting check.
- `agent-static-analysis`: Runs PHPStan analysis.
- `agent-final-test`: Runs complete local quality gate.
- `agent-dev-health`: Checks composer run dev supervisor health.
- `agent-git-status`: Inspects worktree git status.
- `agent-diff-summary`: Summarizes modified diff.
