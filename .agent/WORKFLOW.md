# Agent Development Workflow

## Intake

1. Resolve the exact requirement and actor.
2. Identify the active warehouse/tenant boundary.
3. Identify the model state before and after the action.
4. Identify permissions and segregation-of-duties constraints.
5. Identify audit, notification, file, queue, and integration side effects.
6. Identify failure, retry, idempotency, and concurrency behavior.

## Plan

A plan must list:

- files/modules likely to change;
- migrations and backfills;
- Actions/Policies/Form Requests/Queries;
- tests;
- UI states;
- observability;
- rollout/rollback.

Keep implementation slices small. Prefer a vertical slice that can be demonstrated and tested end-to-end.

## TDD Loop

1. Write a failing test for the intended behavior.
2. Confirm the failure is meaningful.
3. Implement the minimum code.
4. Confirm the test passes.
5. Refactor while keeping tests green.
6. Add deny-path and cross-tenant tests.
7. Run the relevant broader suite.

## Security Review Questions

- Can a different tenant reach this object by changing an ID?
- Can a lower role call the endpoint directly?
- Can the client assign warehouse, actor, status, role, or approver?
- Can a retry create duplicate stock, approval, PO, file, or notification?
- Is a network call inside a transaction?
- Does a queued job restore tenant context?
- Is a file private and policy-protected?
- Is an audit event written with the real and impersonating actor?
- Does error output reveal another tenant or internal details?

## UI Review Questions

- Does it work on mobile/tablet?
- Are loading, empty, error, conflict, and success states implemented?
- Is the next actor/status obvious?
- Are destructive actions contextual and confirmed?
- Is keyboard and screen-reader behavior valid?
- Is sensitive data absent from the HTML/Livewire payload unless authorised?

## Verification

Run focused tests first, then repository quality gates. For workflow changes, demonstrate:

- happy path;
- rejection/error path;
- stale/double-submit path;
- unauthorised role;
- cross-tenant denial;
- audit/notification output;
- rollback or compensating action.

## Review

Review the diff twice:

1. **Standards review:** architecture, security, maintainability, test quality.
2. **Specification review:** exact fidelity to PRD and acceptance criteria.

Do not merge with unresolved high-risk findings.
