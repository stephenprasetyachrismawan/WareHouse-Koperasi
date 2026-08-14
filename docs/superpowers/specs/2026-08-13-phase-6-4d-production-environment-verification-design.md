# Phase 6.4D Production Environment Verification Design

## Goal

Re-verify every Phase 6.4 production blocker against the actual current host and close only the blockers supported by executable evidence. Phase 7 Machine Learning remains out of scope and locked.

## Current baseline

- Base: latest `main` at the start of this phase.
- Required final decision: exactly `PASS`, `PASS WITH ACCEPTED RISKS`, `BLOCKED`, or `FAIL`.
- Existing readiness decision is `PRODUCTION READINESS: BLOCKED`.
- The host currently exposes the Laravel application and Vite through a Cloudflare tunnel, but the required production-like PostgreSQL, Redis, private object backup, load, scanner, and operational sign-off evidence are not assumed to exist.

## Approved approach

Use a two-layer verification model:

1. Provision disposable local staging components only when the host has the package/runtime support to do so. These components may prove compatibility and behavior, but never prove managed production backup, PITR, DNS ownership, RPO/RTO approval, or production capacity.
2. Add or repair repository-level gates that are safe to execute in CI and local staging: production configuration validation, secret/dependency scanning hooks, PostgreSQL-targeted migration/test commands, backup/restore evidence capture, and browser/Cloudflare smoke procedures.

Every result is labeled `IMPLEMENTED`, `VERIFIED`, `NOT VERIFIED`, `BLOCKED`, or `FAIL`. Unavailable external infrastructure remains a blocker.

## Workstreams

### A. Environment and dependency inventory

Record OS, package/runtime availability, service state, Cloudflare ingress, DNS/TLS responses, current ports, and safe dependency versions without printing secrets. Use an isolated temporary directory and synthetic data for all test artifacts.

### B. Production-like compatibility

If disposable PostgreSQL, Redis, and S3-compatible storage can be provisioned safely, run clean migrations, seeded synthetic data, stock reconciliation, tenant isolation, queue/worker, scheduler, private object access, and failure probes against those services. If any component cannot be provisioned, retain `BLOCKED` for the corresponding gate.

### C. Security and deployment gates

Review the current production validator, actual public headers/cookies/assets, CSP/Reverb host behavior, dependency audits, secret scanning, and static-analysis status. Add only focused CI/config changes supported by repository conventions; do not hide PHPStan errors behind a broad baseline.

### D. Recovery evidence

Run the existing local backup/restore scripts where safe. For PostgreSQL/PITR and private object-storage backup/restore, use provider evidence only if actually available. Validate restored entities, stock reconciliation, tenant isolation, and private evidence authorization.

### E. Final report and integration

Update `docs/PRODUCTION-READINESS.md` with a blocker table containing previous state, verification command/environment, evidence, and current result. Commit on the dedicated branch, push, open a PR to `main`, review the diff, merge, then run build-before-dev, `composer run dev`, seeder idempotency, health, Cloudflare, and browser checks from the merged `main`.

## Explicit non-goals

- No ML code, prediction, Python service, or forecasting workflow.
- No production DNS or provider mutation without an explicit provider credential and a reversible, verified change path.
- No destructive migration or restore over persistent developer/production data.
- No claim that SQLite, local Redis, local MinIO, or a development Vite server is production infrastructure.

## Failure and decision rules

- A critical executable failure is `FAIL` until fixed.
- An unavailable required environment or approval is `BLOCKED`.
- A public hostname that does not resolve remains `BLOCKED`.
- `APP_DEBUG=true` in a production target remains `BLOCKED`.
- Missing PostgreSQL/private-object restore, encrypted backup monitoring, secret scan, load evidence, or RPO/RTO sign-off prevents PASS.

## Verification seams

- Artisan production validator and health endpoints.
- Migrations and database queries against a disposable PostgreSQL database.
- Existing public Actions, Policies, jobs, scheduler, stock reconciliation, and seeder chain.
- Existing backup/restore scripts and private storage controllers.
- Actual HTTP/browser requests through the configured Cloudflare hostnames.
- CI workflow checks and dependency/scanner commands.
