# Autonomous Orchestration Rules for Claude Code

This document outlines the rules and boundaries for Claude Code when operating as the coding agent within the `WareHouse-Koperasi` GitHub Issue Orchestration Pipeline.

## 1. High-Level Pipeline Architecture

The orchestration system is a hybrid deterministic-AI architecture:
- **Deterministic Orchestrator (Node.js/TypeScript + tmux + SQLite)**: Manages issue polling, label state transitions, contributor identity resolution, Git worktree isolation, pre/post local testing gates, `composer run dev` process supervision, commit creation, PR creation, immediate squash-merge, and worktree cleanup.
- **Claude Code Agent**: Executes headless (`claude -p`, model `claude-sonnet-5`, effort `low`) inside an isolated Git worktree to perform code inspection, TDD, implementation, and code simplification.

```text
GitHub Issue (agent:run)
    ↓
Deterministic Orchestrator (Worktree setup, Identity resolution, Baseline checks)
    ↓
Claude Code Agent (Planning -> TDD Implementation -> Code Simplification)
    ↓
Deterministic Local Gate (Pint, PHPUnit/Pest, Static Analysis, Composer Dev Health)
    ↓
Immediate Squash-Merge to Main & Post-Merge Verification
```

## 2. Fixed Skill Policy

The primary agent should favor these workspace skills, which already exist under `.claude/skills/` and load automatically — no forced agent/tool restriction is applied; Claude Code selects the applicable ones based on the issue:
1. `laravel-best-practices`: Enforces Laravel conventions, Pest/PHPUnit, Pint, policies, and tenant scoping.
2. `test-driven-development`: Enforces strict RED -> GREEN -> REFACTOR cycle.
3. `code-simplification`: Runs post-green to eliminate unnecessary complexity without altering domain behavior.

Optional skills may only be activated if explicitly listed in the issue's `### Additional Skills` section and present in `automation/warehouse-orchestrator/config/allowed-additional-skills.json` (maximum 2).

## 3. Fixed Command Wrappers

Claude Code MUST NOT construct or execute raw `git push`, `gh pr`, `composer run dev`, `systemctl`, `sudo`, or direct database commands — these are blocked at the tool layer via `--disallowedTools` on every invocation. All operational checks must use the deterministic wrappers located in `./automation/warehouse-orchestrator/agent-tools/`:

- `agent-preflight`: Verifies baseline workspace readiness.
- `agent-baseline`: Runs baseline smoke test.
- `agent-tdd-red`: Confirms a test fails for the expected missing feature.
- `agent-tdd-green`: Confirms the test passes after implementation.
- `agent-test-focused`: Runs specific test filters.
- `agent-test-unit`: Runs unit test suite.
- `agent-test-php`: Runs backend PHP test suite.
- `agent-test-database`: Runs isolated database integration tests.
- `agent-format`: Runs Pint formatter.
- `agent-static-analysis`: Runs PHPStan analysis.
- `agent-final-test`: Runs complete local quality checks.
- `agent-dev-health`: Queries `composer run dev` supervisor status.
- `agent-git-status`: Inspects clean worktree status.
- `agent-diff-summary`: Summarizes modified lines and files.

## 4. TDD Cycle Requirements

1. **RED**: Write/modify a test in `tests/`. Execute `agent-tdd-red --filter TestName`. Ensure it fails specifically due to the missing implementation.
2. **GREEN**: Implement minimal code changes. Execute `agent-tdd-green --filter TestName`. Ensure it passes cleanly.
3. **REFACTOR**: Invoke `code-simplification` skill. Clean up code structure. Re-verify with `agent-test-focused --filter TestName`.

## 5. Prohibited Operations for Claude Code

Claude Code is prohibited from:
- Running `git push`, `git force-push`, `git reset --hard`, or `git clean -fd`.
- Running `gh pr create` or `gh pr merge`.
- Modifying issue labels or posting direct GitHub comments.
- Accessing user tokens, secrets, or global Git configs.
- Invoking subagents or parallel agents.
- Executing browser testing, Playwright, Cypress, or Selenium.
- Modifying workflow files under `.github/workflows/`.

## 6. Final Structured Result Schema

Upon completing work, Claude Code must return a JSON response adhering to the schema:

```json
{
  "status": "complete",
  "issueNumber": 42,
  "summary": "Implemented feature description.",
  "planCompleted": true,
  "skillsUsed": ["laravel-boost", "test-driven-development", "code-simplification"],
  "additionalSkillsUsed": [],
  "tdd": {
    "required": true,
    "red": { "command": "agent-tdd-red --filter TestName", "result": "expected-failure" },
    "green": { "command": "agent-tdd-green --filter TestName", "result": "pass" },
    "refactor": { "result": "pass" }
  },
  "testsRequested": ["TestName"],
  "filesChanged": ["app/Models/Example.php", "tests/Feature/ExampleTest.php"],
  "databaseImpact": "none",
  "securityImpact": "Scoped tenant access verified",
  "suggestedCommitMessage": "feat(scope): concise description (#42)",
  "pullRequestTitle": "feat(scope): concise description",
  "rollback": "Revert commit",
  "remainingRisks": []
}
```
