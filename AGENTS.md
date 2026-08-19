# AGENTS.md

## 1. Mandatory Reading Order

Before editing code, read:

1. `PRD.md`
2. `BATASAN.md`
3. `SECURITY-RULES.md`
4. `ARCHITECTURE.md`
5. `UI-RULES.md`
6. `CI.md`
7. `CD.md`
8. `.agent/WORKFLOW.md`

Do not infer permissions, tenant boundaries, or workflow transitions from UI screenshots alone. `CI.md` and `CD.md` are normative sources of truth for how changes get merged and how the development environment is actually deployed — read them even for changes that don't touch `.github/workflows/` or `deploy/`, since they define the merge gate every change must pass.

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
- Continuous integration / merge governance: `CI.md`.
- Deployment / release / runtime-delivery governance: `CD.md`.
- Existing tests and migrations are executable evidence, but they do not silently override documentation. Raise inconsistencies.
- If an agent's own memory or a prior conversation implies a different CI/CD process than what `CI.md`/`CD.md` currently describe, the repository documentation wins. Re-read `CI.md`/`CD.md` rather than trusting recollection — they are updated whenever the real pipeline changes.

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
10. Wait for GitHub Actions to reach a terminal state (`gh pr checks --watch`). Do not merge while a required check is pending or red — see `CI.md`.
11. Merge the Pull Request into `main` only after required checks are green (`gh pr merge --merge --delete-branch`).
12. If the change affects a deployed environment, verify the actual deployment result (digest, health, smoke) before reporting the task complete — see `CD.md`. A deployment still running or pending is not completion evidence.

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

## 11. CI/CD Mandatory Agent Behaviour

For **every** future code change — even if the user says nothing about CI/CD — an agent must, without being reminded:

1. Read `CI.md` and `CD.md` before starting.
2. Create a dedicated branch (§5).
3. Follow the appropriate TDD loop (`.agent/WORKFLOW.md`).
4. Run local gates before pushing.
5. Push the branch.
6. Open a Pull Request.
7. Inspect actual GitHub Actions results (`gh pr checks`, `gh run view`) — never assume from the push alone.
8. Do not merge while a required check is red or still pending.
9. Merge only after required checks are green.
10. If the change results in a deployment, inspect the actual CD result — workflow run, digest, health, smoke — per `CD.md`.
11. Do not claim something is deployed until that health evidence exists.

The user should never need to remind an agent to follow this — it applies by default to every change in this repository.

## 12. Documentation Drift

Any PR that changes:

- `.github/workflows/**`
- `Dockerfile`
- `deploy/**`
- `compose*.yaml`
- health endpoints
- registry strategy (GHCR naming, tagging, digest handling)
- the GitHub Environment contract (secrets/variables consumed)
- deployment transport (SSH, keys, known-hosts handling)
- rollback flow

**must** explicitly review `CI.md` and `CD.md` for needed updates in the same PR. A workflow/infrastructure change that leaves these documents stale is incomplete, even if the workflow itself passes.
