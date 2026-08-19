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

## Local Verification

Run focused tests first, then repository quality gates. For workflow changes, demonstrate:

- happy path;
- rejection/error path;
- stale/double-submit path;
- unauthorised role;
- cross-tenant denial;
- audit/notification output;
- rollback or compensating action.

Local verification narrows down what's broken before pushing. It does not authorize a merge — see `CI.md`.

## Pull Request

Push the branch and open a Pull Request. State requirement IDs, affected tenant/role/workflow, schema/migration impact, security impact, tests added, and rollout/rollback notes (`AGENTS.md` §9).

## GitHub CI Verification

Wait for GitHub Actions to reach a terminal state (`gh pr checks --watch`). Inspect the actual result — do not infer it from the push succeeding or from local output. If a required check fails, follow the failure rule in `CI.md` §7: inspect the job, inspect logs, fix the real root cause, push, wait again. Never merge with a required check pending or red.

## Merge

Merge only after every required check is green (`AGENTS.md` §5). Prefer merge-via-PR over any direct push to `main`.

## Deployment Verification (if applicable)

If the change results in a deployment — directly, or because it lands on `main` and a deployment is later triggered — verify the actual result per `CD.md`, not just that a workflow step "ran":

- GitHub Environment and workflow run
- published image digest(s)
- migration result
- health result (`/health/live`, `/health/ready`)
- smoke result
- rollback result, if a rollback occurred

A deployment job still running, queued, or `workflow_dispatch`-pending is not completion evidence.

## Review

Review the diff twice:

1. **Standards review:** architecture, security, maintainability, test quality.
2. **Specification review:** exact fidelity to PRD and acceptance criteria.

Do not merge with unresolved high-risk findings.

## Completion

Report completion using the evidence format in `AGENTS.md` §10 and `CI.md` §8 (and `CD.md` §8 when a deployment applies): branch, commit SHA, PR number, workflow run, per-job results, and remaining risks. Do not report completion on local evidence alone.
