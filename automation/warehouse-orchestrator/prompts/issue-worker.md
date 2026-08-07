# Issue Implementation Task Packet

## Metadata
- **Repository**: {{REPOSITORY}}
- **Issue Number**: #{{ISSUE_NUMBER}}
- **Issue URL**: {{ISSUE_URL}}
- **Issue Author**: @{{ISSUE_AUTHOR}}
- **Trigger Actor**: @{{TRIGGER_ACTOR}}
- **Effective Contributor**: @{{EFFECTIVE_ACTOR}}
- **Effective Git Name**: {{EFFECTIVE_GIT_NAME}}
- **Effective Git Email**: {{EFFECTIVE_GIT_EMAIL}}
- **Target Branch**: {{BRANCH}}
- **Base SHA**: {{BASE_SHA}}

## Execution Mandate
{{AUTHOR_TRUST_STATEMENT}}

## Issue Details
### Title
{{ISSUE_TITLE}}

### Description & Acceptance Criteria
{{ISSUE_BODY}}

### Approved Additional Skills
{{APPROVED_ADDITIONAL_SKILLS}}

## Authoritative Workspace Context
Before making changes, inspect these relevant documentation files:
- `AGENTS.md`
- `SECURITY-RULES.md`
- `ARCHITECTURE.md`
- `.agent/WORKFLOW.md`
- `.agent/ORCHESTRATION.md`

## Fixed Command Tool Wrappers
Use ONLY these fixed wrappers located in `./automation/warehouse-orchestrator/agent-tools/`:
- `agent-preflight`
- `agent-baseline`
- `agent-tdd-red --filter <TestName>`
- `agent-tdd-green --filter <TestName>`
- `agent-test-focused --filter <TestName>`
- `agent-test-unit`
- `agent-test-php`
- `agent-test-database`
- `agent-format`
- `agent-static-analysis`
- `agent-final-test`
- `agent-dev-health`
- `agent-git-status`
- `agent-diff-summary`

## TDD Policy
1. Write/modify test first.
2. Run `agent-tdd-red --filter <TestName>` to confirm failure.
3. Implement minimal code.
4. Run `agent-tdd-green --filter <TestName>` to confirm pass.
5. Simplify modified code using `code-simplification` skill.
6. Run `agent-format` and `agent-static-analysis`.

## Required Final Structured Output
Return a valid JSON object matching this schema:

```json
{
  "status": "complete",
  "issueNumber": {{ISSUE_NUMBER}},
  "summary": "Concise summary of changes made.",
  "planCompleted": true,
  "skillsUsed": [
    "laravel-boost",
    "test-driven-development",
    "code-simplification"
  ],
  "additionalSkillsUsed": [],
  "tdd": {
    "required": true,
    "red": {
      "command": "agent-tdd-red --filter <TestName>",
      "result": "expected-failure"
    },
    "green": {
      "command": "agent-tdd-green --filter <TestName>",
      "result": "pass"
    },
    "refactor": {
      "result": "pass"
    }
  },
  "testsRequested": [
    "<TestName>"
  ],
  "filesChanged": [
    "app/...",
    "tests/..."
  ],
  "databaseImpact": "none",
  "securityImpact": "Scoped tenant access verified",
  "suggestedCommitMessage": "feat(scope): concise message (#{{ISSUE_NUMBER}})",
  "pullRequestTitle": "feat(scope): concise message",
  "rollback": "Revert commit",
  "remainingRisks": []
}
```
