# Phase 6.4 Production Hardening Design

**Date:** 2026-08-12
**Base:** `main` at `bbd8ce3122ac169d65d8801bcd172bc450fe6718`
**Scope:** Final core Laravel hardening phase before Machine Learning

## Goal

Establish executable evidence for security, tenant isolation, concurrency integrity, failure resilience, performance, observability, backup, restore, deployment safety, and operational recovery without adding new business workflows or starting Machine Learning.

The final gate will report exactly one of `PASS`, `PASS WITH ACCEPTED RISKS`, `BLOCKED`, or `FAIL`. Phase 7 must not start unless the gate is `PASS` or an explicitly approved `PASS WITH ACCEPTED RISKS`.

## Current baseline

- Phase 6.3 is merged in PR #34 and is present on `main`.
- The application is a Laravel modular monolith with Livewire, Reverb, database queue/cache defaults, private local evidence paths in the current development environment, and tenant context middleware.
- Production infrastructure is not assumed to be available locally. PostgreSQL, Redis, FCM, S3-compatible storage, load generation, and managed backup services will be marked `NOT VERIFIED` when they cannot be executed safely.
- Existing static-analysis findings are treated as baseline findings until this phase proves they affect a hardening path; they will not be hidden with ignores or baselines.

## Design principles

1. Every security or integrity claim is backed by an executable test, command output, or an explicit environment limitation.
2. Tests exercise public HTTP, Livewire, Artisan, queue, broadcast, file, and storage seams rather than implementation details.
3. Tenant identity is explicit in query, job, cache, lock, broadcast, notification, export, and file boundaries.
4. Core transactions remain valid when optional side effects fail. Inbox/database state remains the source of truth for notifications.
5. Reconciliation detects and reports mismatches; it never silently edits a materialized stock balance.
6. Production documentation describes the actual deployment model and never claims infrastructure that was not verified.
7. Each slice is independently reviewable and merged before the next slice begins.

## Slice 6.4A — Security and tenant isolation

**Branch:** `hardening/security-tenant-isolation`

The first slice creates a central security regression suite and closes evidenced tenant/security gaps. It will cover:

- cross-warehouse denial for major tenant-owned domains, including route binding, Livewire actions, search, downloads, exports, queues, notifications, and private broadcasts;
- multi-warehouse users, stale tenant state, inactive memberships, and app-admin/super-admin privilege boundaries;
- explicit tenant restoration in jobs and tenant-aware cache, lock, rate-limit, notification, and broadcast keys;
- mass-assignment and generic status mutation audits;
- private evidence and report-export authorization, expiry, storage paths, and path/UUID swaps;
- MFA/session/CSRF/security-header behavior where the existing authentication seam supports executable verification;
- safe correlation IDs and production-safe exception responses;
- dependency audit and secret-scan evidence;
- a documented RLS assessment and runtime/migration database privilege strategy.

The implementation will prefer existing Laravel middleware, Policies, Form Requests, Actions, events, and query objects. New shared infrastructure will be introduced only where an existing public seam cannot express the control.

## Slice 6.4B — Resilience, performance, and observability

**Branch:** `hardening/resilience-performance-observability`

This slice depends on merged 6.4A. It will add or verify:

- an explicit tenant-by-tenant stock reconciliation command/service with mismatch diagnostics, audit-safe output, bounded processing, and tenant-aware overlap locks;
- regression coverage for concurrent stock movement, approvals, receipts/QC, pickup/replacement completion, notification dedupe, and report-export dedupe;
- a failure matrix and executable tests for queue workers, Redis-backed optional services, Reverb, FCM, object storage, scheduler retries, and browser disconnects;
- request/job correlation logging that excludes secrets, signed URLs, evidence bodies, OAuth/FCM tokens, and full sensitive request payloads;
- minimal liveness/readiness health surfaces with safe public output and explicit optional-versus-required dependency semantics;
- a reproducible performance baseline for representative dashboard, list, inbox, report, and export seams, including query counts and latency where the local environment allows it;
- query/index changes only when supported by measured evidence.

No destructive load test will run against the live developer or public environment. Any load fixture will use isolated synthetic data and an isolated database/storage namespace.

## Slice 6.4C — Backup, restore, and production-readiness gate

**Branch:** `hardening/backup-restore-production-readiness`

This slice depends on merged 6.4B. It will provide:

- backup scope for relational data, private evidence objects, and required configuration metadata;
- supported encrypted backup automation or an explicit infrastructure blocker when the repository cannot operate the required provider;
- proposed engineering RPO/RTO values clearly labelled until product/operations sign-off;
- an isolated restore-drill procedure and executable evidence. A database import without representative data checks, stock reconciliation, tenant isolation, and private evidence verification is not a successful restore;
- deployment, rollback, queue, scheduler, Reverb, security-incident, storage, Redis, and stock-reconciliation runbooks;
- migration-safety review, production environment validation, CI/CD security gates, and post-deploy smoke-test procedure;
- `docs/PRODUCTION-READINESS.md` containing environment assumptions, evidence, risks, blockers, owners, and the final exact classification.

If the current host only provides SQLite/local storage, the final report will say so and classify PostgreSQL/object-storage backup and restore as `BLOCKED` or a formally accepted risk; it will not simulate a production backup while calling it verified.

## Test seams

The approved public seams are:

- Laravel HTTP routes and responses;
- Livewire component actions and rendered state;
- Laravel Policies/Gates and tenant context middleware;
- Action classes invoked through their public `execute`/`handle` contract;
- queued Job `handle` behavior with explicit serialized tenant context;
- Artisan commands and scheduler registration;
- broadcast channel authorization and event payloads;
- private file download/temporary access controllers;
- cache/lock/rate-limit stores at their public facade contracts;
- health and diagnostics endpoints;
- backup/restore scripts and documented operational commands.

Tests will use the real disposable test database and filesystem wherever possible. Mocks are limited to external boundaries such as FCM, Google, object storage, and unavailable infrastructure providers.

## Branch and integration protocol

For each slice:

1. branch from the latest merged `main`;
2. identify requirement IDs and acceptance criteria in the PR description;
3. write a failing test before implementation for each vertical slice;
4. run focused tests, Pint, static analysis, build, and relevant audits;
5. perform standards and specification review;
6. commit with an imperative message;
7. push the branch and create a PR to `main`;
8. resolve critical/required review findings;
9. merge with `gh pr merge --merge --delete-branch`;
10. fetch the resulting `main`, verify it, and use it as the base for the next slice.

No direct commits to `main` are allowed. The dev supervisor must point at the current checked-out workspace after each integration step, and the final evidence must show `composer run dev`/the repository's equivalent process group, web server, Vite, queue, and Reverb status.

## Final decision rule

The final document must separate these dimensions:

```text
Security
Correctness and concurrency
Performance
Resilience
Observability
Recoverability
Deployment safety
```

Any cross-tenant access, privilege escalation, stock lost update/double count, duplicate terminal side effect, public evidence leak, active critical secret, `APP_DEBUG=true` production configuration, missing working backup, or unverified required restore is a blocker. The final report must not claim production readiness while any blocker remains.
