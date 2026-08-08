# AGENTS.md

## 1. Mandatory Reading Order

Before editing code, read:

1. `PRD.md`
2. `BATASAN.md`
3. `SECURITY-RULES.md`
4. `ARCHITECTURE.md`
5. `UI-RULES.md`
6. `.agent/WORKFLOW.md`

Do not infer permissions, tenant boundaries, or workflow transitions from UI screenshots alone.

## 2. Mandatory Agent Tooling

Install Matt Pocock's engineering skills once per repository:

```bash
npx skills@latest add mattpocock/skills
```

Select the required skills and include `setup-matt-pocock-skills`, then run:

```text
/setup-matt-pocock-skills
```

Install Laravel Boost:

```bash
composer require laravel/boost --dev
php artisan boost:install
```

Refresh generated resources after dependency changes:

```bash
php artisan boost:update
```

Laravel Boost custom project rules live in `.ai/guidelines/warehouse-project.md`.

## 3. Source of Truth

- Business requirements: `PRD.md`.
- Scope and deliberate overrides: `BATASAN.md`.
- Security: `SECURITY-RULES.md`.
- Architecture and boundaries: `ARCHITECTURE.md`.
- UI behavior: `UI-RULES.md`.
- Existing tests and migrations are executable evidence, but they do not silently override documentation. Raise inconsistencies.

## 4. Non-Negotiable Rules

1. Every tenant-owned record is scoped by `warehouse_id`.
2. Every sensitive model/action uses a Policy/Gate and tenant check.
3. UI visibility is never the only authorization layer.
4. Controllers remain thin.
5. Status changes use explicit Actions; never generic status CRUD.
6. Stock movements are append-only ledger entries and atomic balance updates.
7. Approval decisions are immutable and audited.
8. File evidence is private and policy-protected.
9. Queue jobs, cache keys, locks, broadcasts, notifications, exports, and search are tenant-aware.
10. Machine learning remains disabled and unimplemented until the final planned phase.
11. Network calls do not run inside database transactions.
12. New dependencies require justification and review.
13. Never expose secrets, production data, tokens, signed URLs, or credentials in prompts, logs, fixtures, or commits.

## 5. Required Workflow

For each change:

1. Create a dedicated feature branch (`git checkout -b feat/...` or `fix/...`). NEVER commit directly to `main`.
2. Identify requirement IDs and acceptance criteria.
3. Confirm module and security boundary.
4. Write or update a small implementation plan.
5. Add a failing test first for the behavior or regression.
6. Implement the smallest vertical slice.
7. Run focused tests, formatting (`vendor/bin/pint --test`), and asset build (`npm run build`).
8. Push the branch to origin (`git push -u origin <branch>`).
9. Create a Pull Request (`gh pr create`).
10. Merge the Pull Request into `main` (`gh pr merge --merge --delete-branch`).

## 6. Required Test Cases for Tenant Models

Every new tenant model or endpoint must test:

- allowed access in the current warehouse;
- denied access to another warehouse;
- denied access with inactive membership;
- role/permission allow and deny;
- route model binding scope;
- queue/export/file/broadcast behavior if applicable;
- mass-assignment attempts for `warehouse_id`, role, status, and actor fields.

## 7. Code Conventions

- Use PHP strict types for project classes where practical.
- Use backed enums for statuses and controlled types.
- Use immutable DTOs/value objects for sensitive inputs.
- Use Form Requests for validation and initial authorization.
- Use Actions for mutations and Query Objects for complex reads.
- Use Eloquent relationships and explicit eager loading.
- Avoid generic repositories and service classes named only `Manager` or `Helper`.
- Avoid hidden side effects in model observers for critical workflows; explicit actions are preferred.
- Dispatch external side effects after commit through outbox/jobs.
- Use human-readable public IDs and internal IDs safely.

## 8. Prohibited Shortcuts

Do not:

- call `Model::find($id)` for tenant data without scoped resolution;
- trust `warehouse_id`, actor ID, approver ID, or status from the client;
- use `$request->all()` for model creation/update;
- use `$guarded = []` on sensitive models without explicit approval;
- add a universal `super_admin` Gate bypass;
- store return/QC files on a public disk;
- edit/delete ledger or approval history;
- create a PO from an unapproved request;
- allow Staff Admin to approve final decisions;
- let Python ML connect directly to the Laravel database;
- start the ML feature early;
- use production data in local tests.

## 9. Pull Request Expectations

Every PR must state:

- requirement/ticket IDs;
- why the change is needed;
- affected tenant/role/workflow;
- schema and migration impact;
- security impact;
- tests added and commands run;
- screenshots for UI changes;
- rollout/rollback notes;
- unresolved risks.

A PR changing authentication, authorization, tenant resolution, stock, approvals, file access, impersonation, or ML integration requires explicit security review.

## 10. Completion Evidence

Do not claim completion without command output or CI evidence for:

```bash
php artisan test
vendor/bin/pint --test
# configured static analysis command
npm run build
# configured frontend lint command
```

Also run relevant focused tests for concurrency, tenancy, authorization, and browser behavior.
