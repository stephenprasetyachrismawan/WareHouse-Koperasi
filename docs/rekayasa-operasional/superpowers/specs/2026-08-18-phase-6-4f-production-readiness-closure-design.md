# Phase 6.4F Production Readiness Closure Design

**Date:** 2026-08-18
**Status:** Design approved in conversation; implementation pending spec review
**Branch:** `hardening/phase-6-4f-production-readiness`

## Goal

Close, verify, or truthfully classify the production-readiness gates deferred by Phase 6.4D and Phase 6.4E. The final result must distinguish application-level evidence from managed-infrastructure evidence and must choose exactly one of:

```text
PRODUCTION READY
CONDITIONALLY READY
PRODUCTION READINESS BLOCKED
```

This phase does not add business functionality and does not begin Phase 7 Machine Learning.

## Current baseline

The baseline was executed from `main` at `a64a9762b7bdab7b6c09188e7aa6c58ed5de1d0c`, which matches `origin/main`.

| Gate | Current result |
|---|---|
| Laravel tests | 615 passed, 1,496 assertions |
| Pint | Passed |
| PHPStan Level 7 | Failed with 410 errors |
| Frontend build | Passed; optional `fontaine` warning only |
| Composer audit | No advisories |
| npm audit | 0 vulnerabilities |
| Secret scan | Not verified; Gitleaks unavailable |
| Production validator | Failed closed on local development configuration |
| Infrastructure smoke | Blocked by production configuration failure |
| Public `.net` browser smoke | Page and health endpoints reachable, but CSP/Vite/Reverb findings remain |
| Canonical `.com` | DNS unresolved in the current probe |

The local untracked `.env.bak-20260812T143641Z` remains outside this work and must never be staged, read into documentation, or committed.

## Architectural boundaries

- The Laravel application remains a modular monolith with existing Actions, Policies, Form Requests, Query Objects, Livewire components, jobs, notifications, broadcasts, and private storage boundaries.
- No new business workflow, status, tenant model, prediction model, or ML integration is introduced.
- Existing `ops:validate-production` and `ops:verify-production-infrastructure` commands remain the production configuration and connectivity seams.
- `warehouse_id`, active membership, Policy/Gate authorization, ownership, and segregation-of-duties checks remain mandatory.
- Stock remains an append-only ledger with atomic materialized balances.
- Approval decisions remain immutable and audited.
- Queue jobs, exports, notifications, broadcasts, cache keys, locks, files, and searches remain tenant-aware.
- Persistent databases are never reset; `migrate:fresh` is prohibited outside explicitly isolated automated test environments.
- External providers are never inferred from configuration support. A provider is `VERIFIED` only after an approved target produces executable evidence.

## Phase slices

### 6.4F-1 — Static analysis closure

Reduce PHPStan Level 7 findings by fixing root causes in model and relationship typing, Builder/Collection generics, enum contracts, array shapes, nullable values, and scalar quantity contracts. The implementation must preserve runtime behavior and tenant/security boundaries.

The first diagnostic pass must capture the complete error inventory in a non-repository temporary artifact and group findings by identifier, file, domain module, and likely fan-out root. No baseline, broad ignore rule, path removal, `mixed` widening, or cast-only suppression is allowed.

If a finding reveals a runtime defect, the sequence is: reproduce, add a focused failing test, verify RED, implement the smallest fix, verify GREEN, run the relevant security/domain suite, then rerun PHPStan.

Completion evidence is `composer types:check` with zero legitimate errors, followed by `composer test` completing Pint, PHPStan, and Laravel tests.

### 6.4F-2 — Authenticated browser and responsive certification

Use the available Chrome DevTools MCP in an isolated browser context. Do not add a browser dependency merely to create a checkbox suite. Use existing synthetic/dev data and never mutate a production target.

The test matrix covers `super_admin`, `app_admin`, `kepala_gudang`, `staff_admin`, `purchasing`, and `koperasi`, including tenant context, inactive membership, direct navigation, forbidden actions, cross-tenant identifiers, and ownership boundaries.

Representative flows cover authentication/security, warehouse administration, inventory, pickup/approval, procurement/receipt/QC, return/replacement, dashboards/reports/private exports, and Inbox/notification navigation. Mutations must use synthetic tenants and be checked for duplicate or cross-tenant side effects.

Run representative screens at approximately 390px, 768px, and at least 1280px. Record layout, focus, dialog, table, form, loading, empty, error, action-bar, and navigation findings. A flow is not `VERIFIED` when only the screenshot looks correct; console, network, authorization, and accessibility evidence are required.

If an authenticated session cannot be safely established, the affected matrix is `NOT VERIFIED` or `BLOCKED — EXTERNAL ACTION REQUIRED`, never fabricated as pass.

### 6.4F-3 — Reverb, CSP, and production browser closure

Close the observed public-browser problems without globally weakening CSP:

- public builds must not target `localhost` for Reverb;
- production must use the approved public WSS host and exact HTTPS origin allowlist;
- development Vite and browser logging must not require production CSP exceptions;
- `worker-src`, `connect-src`, script sources, and any Cloudflare resource must be explicitly classified as required, development-only, or unnecessary;
- `unsafe-eval` and broad wildcard sources remain prohibited unless a documented, reviewed exception is unavoidable;
- persistent Inbox/HTTP reads remain the source of truth when Reverb is unavailable.

Repository-level configuration or CSP changes require tests first. Public WSS, TLS termination, reverse proxy, and process supervision remain external evidence unless an approved environment is available.

### 6.4F-4 — Managed data infrastructure certification

Against an approved staging or production-equivalent target only, verify PostgreSQL version, private networking, TLS, clean migration, constraints/indexes, tenant isolation, stock concurrency, approval concurrency, and runtime least privilege. Migration and runtime identities must be separate, and runtime privilege denial must be recorded.

Verify managed Redis with authenticated TLS for queue, cache, session, locks, safe synthetic queue work, idempotency, restart/failure behavior, and tenant-aware key behavior.

Verify private S3-compatible storage with synthetic objects: anonymous denial, encryption, versioning/recovery controls, tenant-prefixed paths, upload/retrieval, authorization-gated temporary URLs, TTL, and cleanup.

If no approved target and scoped credentials exist, each gate remains `BLOCKED — EXTERNAL ACTION REQUIRED`.

### 6.4F-5 — Backup, restore, and stock recovery certification

Use managed encrypted PostgreSQL backup/PITR and private-object recovery in an isolated restore target. Never overwrite the active environment. Record backup identifier, start/end timestamps, encryption and retention evidence, restore duration, observed recovery point, application boot, critical tenant relationships, private evidence authorization, and failures.

Run the existing stock reconciliation command against the restored data. Ledger and materialized balances must match; reconciliation must report mismatches without silently repairing them.

The existing local SQLite/private-file drill remains useful as a script safety check but cannot close this managed gate.

### 6.4F-6 — Capacity, observability, and supervision certification

Against a safe production-like environment, measure representative health, authenticated dashboard, inventory, procurement, reports, export enqueue, queue processing, controlled mutation, and Reverb profiles. Record p50, p95, p99, throughput, error rate, database/Redis saturation, queue depth, worker latency, memory, CPU, and slow-query observations without inventing an SLO.

Verify actual monitoring and alert delivery for availability, readiness, 5xx/errors, queues, scheduler, PostgreSQL, Redis, storage, Reverb, backups, certificates, saturation, security events, and stock reconciliation failures.

Verify supervised web, queue workers, scheduler, and Reverb restart and deployment behavior. `composer run dev` remains development orchestration evidence only.

### 6.4F-7 — DNS, TLS, OAuth, and operations sign-off

Verify the approved canonical hostname, DNS, HTTPS certificate chain and hostname, redirects, `APP_URL`, cookie domain, CSP origins, Reverb WSS host, signed URLs, notification links, Google OAuth callback, invitation-only access, MFA, privileged step-up MFA, and secure session settings.

RPO/RTO remain `PROPOSED` until a product/operations owner explicitly approves them. Once a managed drill exists, measured recovery point and duration are compared with the approved values.

### 6.4F-8 — Evidence and final decision

Create `docs/verification/phase-6-4f-final-evidence.md` with the complete gate matrix. Update `docs/PRODUCTION-READINESS.md` using the existing evidence vocabulary and retain unresolved external actions with responsible ownership and expected evidence.

The final decision is `PRODUCTION READY` only when all mandatory production-equivalent gates are evidenced. Any unresolved mandatory technical, security, recovery, or operational gate produces `PRODUCTION READINESS BLOCKED`.

## Verification strategy

Every code change follows RED → GREEN → REFACTOR:

1. Add one focused failing test for the behavior or regression.
2. Run the test and confirm the failure is meaningful.
3. Implement the smallest root-cause fix.
4. Run the focused test and relevant tenant/security/concurrency tests.
5. Run Pint, PHPStan, frontend build, and the applicable broader suite.
6. Use Chrome DevTools for browser-facing changes and record console/network/accessibility evidence.
7. Run all final gates freshly before any completion claim or PR.

Documentation-only evidence updates do not require a new application behavior test, but their command output and status must be traceable to fresh execution or explicitly marked historical/external.

## Security and data handling

- Never print or commit credentials, signed URLs, tokens, production records, or backup contents.
- Never use the untracked environment backup as a configuration source.
- Never run `migrate:fresh` or destructive restore against a persistent target.
- Storage smoke writes are allowed only with explicit confirmation against an approved isolated target.
- Browser JavaScript inspection is read-only and must not access cookies, localStorage tokens, or authentication material.
- All cross-tenant, inactive-membership, role-deny, route-binding, export, private-file, broadcast, queue, stock-concurrency, and approval-concurrency checks remain required.

## Non-goals and locked scope

- No new business functionality.
- No UI redesign.
- No new ML endpoints, prediction tables, Python calls, ML dependencies, forecasting UI, or prediction menus.
- No provider-specific infrastructure provisioning without an approved provider decision and scoped credentials.
- No false `VERIFIED` status based only on code support, local emulation, Cloudflare Tunnel reachability, or historical documentation.
