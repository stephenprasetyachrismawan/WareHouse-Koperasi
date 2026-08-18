# Phase 6.4F Production Readiness Closure Implementation Plan

> For agentic workers: use superpowers:subagent-driven-development or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox syntax for tracking.

**Goal:** Close application-level readiness defects and produce executable, evidence-backed certification for every Phase 6.4F production gate without adding business functionality or starting Machine Learning.

**Architecture:** Preserve the existing Laravel modular monolith, production validator, infrastructure smoke command, tenant boundaries, Actions, Policies, private storage, and operational runbooks. Fix PHPStan at model/relationship roots, use isolated Chrome DevTools evidence for browser behavior, and classify managed-provider work as verified only when an approved target produces evidence.

**Tech Stack:** Laravel 13, PHP 8.4, Livewire 4, Pest 5, Larastan/PHPStan Level 7, Pint, Vite/Tailwind, Chrome DevTools MCP, PostgreSQL, Redis, Reverb, private S3-compatible storage, GitHub Actions.

**Spec:** docs/superpowers/specs/2026-08-18-phase-6-4f-production-readiness-closure-design.md

## Global Constraints

- No new business functionality, UI redesign, schema change, or Phase 7 Machine Learning.
- Preserve warehouse_id, active membership, Policy/Gate authorization, route binding, ownership, and segregation-of-duties checks.
- Preserve append-only stock ledger, atomic balances, immutable approvals, private evidence, and tenant-aware jobs/exports/notifications/broadcasts/cache/locks/search.
- Do not weaken PHPStan Level 7 with a broad baseline, broad ignore rule, removed paths, mixed widening, or cast-only suppression.
- Use RED -> GREEN -> REFACTOR for every production-code behavior or regression change.
- Never read, stage, or commit .env.bak-*; never print secrets, production data, tokens, signed URLs, or credentials.
- Never run migrate:fresh against a persistent environment; never restore destructively over an active target.
- Treat missing managed infrastructure, credentials, alert destinations, canonical DNS approval, and RPO/RTO approval as BLOCKED — EXTERNAL ACTION REQUIRED.
- Keep main untouched; work only on hardening/phase-6-4f-production-readiness.

---

### Task 1: Build a complete PHPStan error inventory

**Files:**
- Read: phpstan.neon
- Read: composer.json
- Create outside repository: /tmp/phase-6-4f-phpstan-inventory.json
- Create outside repository: /tmp/phase-6-4f-phpstan-inventory-summary.txt

- [ ] Step 1: Run composer types:check and record exit code, total errors, and elapsed time.
- [ ] Step 2: Capture machine-readable output in a temporary artifact outside the repository; do not print or commit the full artifact.
- [ ] Step 3: Group findings by identifier, file, domain module, model/relationship root, and runtime-risk classification.
- [ ] Step 4: Identify fan-out candidates before editing call sites. Retain only paths, line numbers, identifiers, and non-sensitive messages in the summary.

Expected result: a confirmed current baseline, not an assumed historical count.

### Task 2: Repair root Eloquent model and relationship types

**Files likely from the baseline error fan-out:**
- Modify: app/Models/PickupRequest.php
- Modify: app/Models/PickupRequestItem.php
- Modify: app/Models/GoodsReceipt.php
- Modify: app/Models/GoodsReceiptItem.php
- Modify: app/Models/QualityInspection.php
- Modify: app/Models/PurchaseRequest.php
- Modify: app/Models/PurchaseRequestItem.php
- Modify: app/Models/PurchaseOrder.php
- Modify: app/Models/PurchaseOrderItem.php
- Modify: app/Models/Approval.php
- Test only when a runtime defect is exposed: the focused Pickup or Procurement test for that behavior

- [ ] Step 1: For each suspected runtime defect, add one focused Pest test and run it to confirm RED.
- [ ] Step 2: Correct concrete BelongsTo, HasMany, HasOne, MorphMany, and HasManyThrough declarations and their generics according to actual relationships.
- [ ] Step 3: Keep warehouse_id, authorization, and relationship runtime behavior unchanged.
- [ ] Step 4: Run the focused Pickup and Procurement suites and confirm GREEN.
- [ ] Step 5: Run composer types:check and record the reduced error count.

Do not broaden a relation to Model, mixed, or an unbounded collection merely to silence analysis.

### Task 3: Repair Action, event, enum, collection, and input contracts

**Files:**
- Modify: app/Actions/Pickup/ApprovePickupRequestAction.php
- Modify: app/Actions/Pickup/FulfillPickupAction.php
- Modify: app/Actions/Pickup/RejectPickupRequestAction.php
- Modify: app/Actions/Pickup/SubmitPickupRequestAction.php
- Modify: app/Actions/Procurement/CompleteQualityInspectionAction.php
- Modify: app/Actions/Procurement/CreatePurchaseOrderAction.php
- Modify: app/Actions/Procurement/CreatePurchaseRequestAction.php
- Modify: app/Actions/Procurement/CreatePurchaseRequestGroupAction.php
- Modify: app/Actions/Procurement/RecordGoodsReceiptAction.php
- Modify only if traced: app/Domain/Pickup/Events and app/Enums/PurchaseRequestSource.php
- Test: existing focused Pickup, Procurement, Security, stock-concurrency, and approval-concurrency tests

- [ ] Step 1: Trace each finding to first(), get(), sole(), a relationship, a request array, an enum, or a numeric database value.
- [ ] Step 2: Add a RED regression test before changing behavior when a finding exposes a runtime defect.
- [ ] Step 3: Use concrete collection generics, correct first/get semantics, explicit array shapes or existing DTOs, valid enum cases, and nullable checks.
- [ ] Step 4: Preserve explicit workflow Actions, tenant filters, Policy checks, locks, transactions, audit events, and idempotency.
- [ ] Step 5: Run the focused Pickup, Procurement, and Security suites.
- [ ] Step 6: Run composer types:check and require zero legitimate Level 7 errors.

If a finding remains, return to Task 1; do not add a suppression.

### Task 4: Run full application quality gates

**Files:**
- Review: composer.json, phpstan.neon, phpunit.xml, package.json, .github/workflows/tests.yml
- Modify: none unless verification exposes a real regression

- [ ] Step 1: Run composer test and require configuration clear, Pint pass, PHPStan pass, and Laravel tests pass.
- [ ] Step 2: Run php artisan test, vendor/bin/pint --test, npm run build, composer audit, npm audit --audit-level=high, scripts/verification/secret-scan.sh ., and git diff --check.
- [ ] Step 3: Run focused security and concurrency coverage:
  php artisan test --compact tests/Feature/Security
  php artisan test --compact tests/Feature/Inventory/StockMovementTest.php tests/Feature/Inventory/StockReconciliationCommandTest.php
  php artisan test --compact tests/Feature/Pickup/PickupSecurityConcurrencyTest.php tests/Feature/Procurement/ProcurementSecurityConcurrencyTest.php
- [ ] Step 4: Record secret scanner unavailability as NOT VERIFIED; never convert it into PASS.
- [ ] Step 5: Commit only the reviewed application-level changes with message: fix: close phase 6.4f static analysis findings.

### Task 5: Authenticated browser and responsive certification

**Files:**
- Use Chrome DevTools MCP in an isolated context.
- Modify only source files that reproduce a verified UI/runtime defect.
- Evidence: docs/verification/phase-6-4f-final-evidence.md

- [ ] Step 1: Establish a safe synthetic browser session without reading cookies, localStorage tokens, or credentials. If unavailable, mark affected flows NOT VERIFIED.
- [ ] Step 2: Verify all six roles: super_admin, app_admin, kepala_gudang, staff_admin, purchasing, koperasi.
- [ ] Step 3: Verify login/logout, inactive membership, forbidden navigation, tenant context, direct URL access, cross-tenant IDs, and Koperasi ownership.
- [ ] Step 4: Verify representative inventory, pickup/approval, procurement/receipt/QC, return/replacement, dashboards/reports, private exports, Inbox, and notification deep links.
- [ ] Step 5: Verify approximately 390px, 768px, and 1280px viewports for drawer, tables, forms, dialogs, sticky actions, focus, overflow, and long labels.
- [ ] Step 6: Record URL, role, tenant, viewport, expected/actual result, HTTP statuses, console, WebSocket, accessibility, and screenshot evidence without private data.
- [ ] Step 7: If a UI source fix is required, add a failing automated regression test, implement the smallest change, rerun the browser flow, affected tests, and build.

### Task 6: Reverb and CSP production browser closure

**Files:**
- Modify only if tests identify a repository defect: resources/js/echo.js
- Modify only if tests identify a repository defect: config/security.php
- Modify only if tests identify a repository defect: app/Http/Middleware/AddSecurityHeaders.php
- Modify tests: tests/Feature/Security/SecurityHeadersTest.php and relevant Reverb/notification tests
- Evidence: docs/verification/phase-6-4f-final-evidence.md

- [ ] Step 1: Reproduce public console/network findings and confirm localhost WSS, Vite dev assets, inline scripts, workers, or unapproved analytics.
- [ ] Step 2: Add failing tests for production Echo/CSP/validator behavior without credentials.
- [ ] Step 3: Keep development Vite support environment-aware; add only exact approved origins and required worker/connect directives.
- [ ] Step 4: Do not add unsafe-eval or broad wildcard sources.
- [ ] Step 5: Run:
  php artisan test --compact tests/Feature/Security/SecurityHeadersTest.php tests/Feature/Operations/ProductionConfigurationCommandTest.php
  npm run build
- [ ] Step 6: Repeat browser console, network, WSS, and accessibility checks. Public WSS remains external evidence until supervised TLS ingress exists.

### Task 7: Execute or classify managed data infrastructure gates

**Files:**
- Use: app/Console/Commands/ValidateProductionConfigurationCommand.php
- Use: app/Console/Commands/VerifyProductionInfrastructureCommand.php
- Evidence: docs/verification/phase-6-4f-final-evidence.md

- [ ] Step 1: Check for approved scoped PostgreSQL, Redis, private S3, secret-manager, and network targets without printing credentials.
- [ ] Step 2: Run php artisan ops:validate-production and php artisan ops:verify-production-infrastructure only against an approved isolated configuration.
- [ ] Step 3: Run storage smoke only with explicit approval:
  php artisan ops:verify-production-infrastructure --storage-smoke --confirm-storage-smoke
- [ ] Step 4: For PostgreSQL, record version, TLS, private network, clean migrations, constraints/indexes, tenant/security/concurrency tests, and runtime denial of schema/role administration.
- [ ] Step 5: For Redis, record authenticated TLS, queue worker, cache/session, locks/idempotency, tenant-aware keys, and restart/failure behavior.
- [ ] Step 6: For storage, record anonymous denial, encryption/versioning, tenant-prefixed synthetic object, authorized retrieval, temporary URL TTL, and cleanup.
- [ ] Step 7: Classify every unavailable resource as BLOCKED — EXTERNAL ACTION REQUIRED with required permission and expected evidence.

### Task 8: Backup, restore, and stock reconciliation evidence

**Files:**
- Use: scripts/backup/create-backup.sh
- Use: scripts/backup/restore-drill.sh
- Use: docs/runbooks/backup-restore.md
- Use: docs/runbooks/stock-reconciliation.md
- Evidence: docs/verification/phase-6-4f-final-evidence.md

- [ ] Step 1: Keep the local SQLite/private-file drill classified as local script safety evidence.
- [ ] Step 2: Run managed encrypted backup/PITR and private-object recovery only against an approved isolated target; never overwrite active data.
- [ ] Step 3: Record backup identifier, retention, encryption, restore timestamps, recovery point, duration, application boot, critical rows, tenant relationships, and private evidence authorization.
- [ ] Step 4: Run php artisan stock:reconcile against restored data and require zero mismatches.
- [ ] Step 5: If managed recovery is unavailable, mark backup, restore, and stock-after-restore BLOCKED — EXTERNAL ACTION REQUIRED.

### Task 9: Capacity, observability, supervision, DNS, TLS, OAuth, and sign-off

**Files:**
- Use: docs/observability/phase-6-4b-operations.md
- Use: docs/resilience/phase-6-4b-failure-matrix.md
- Use: docs/runbooks/deployment.md
- Use: docs/runbooks/reverb.md
- Evidence: docs/verification/phase-6-4f-final-evidence.md

- [ ] Step 1: If an approved environment exists, measure health, authenticated reads, inventory, procurement, reports, export enqueue, queue processing, controlled mutations, and Reverb; record p50/p95/p99, throughput, errors, saturation, queue depth, latency, CPU, memory, and slow queries without inventing an SLO.
- [ ] Step 2: Verify real alert delivery and ownership for readiness, 5xx, exceptions, queue, scheduler, database, Redis, storage, Reverb, backups, certificates, saturation, security, and reconciliation.
- [ ] Step 3: Verify supervised web, workers, scheduler, and Reverb restart/deploy behavior; do not treat composer run dev as production supervision.
- [ ] Step 4: Verify approved canonical DNS, certificate hostname/chain, redirects, APP_URL, cookie domain, Google callback, Reverb WSS host, CSP origins, signed links, invitation-only auth, MFA, and secure cookies.
- [ ] Step 5: Record missing capacity, alerting, supervision, DNS/TLS, OAuth, and RPO/RTO approval as BLOCKED — EXTERNAL ACTION REQUIRED.

### Task 10: Publish final evidence and prepare the PR

**Files:**
- Create: docs/verification/phase-6-4f-final-evidence.md
- Modify: docs/PRODUCTION-READINESS.md
- Review: docs/architecture/0011-managed-production-environment.md
- Review: docs/runbooks/deployment.md
- Review: docs/runbooks/reverb.md
- Review: docs/runbooks/backup-restore.md

- [ ] Step 1: Write the complete matrix for tests, Pint, PHPStan, build, audits, secret scan, security/tenant regression, browser, responsive, CSP, Reverb, PostgreSQL, least privilege, Redis, storage, queue, scheduler, supervision, backup, restore, reconciliation, capacity, monitoring, alerts, RPO/RTO, DNS, TLS, OAuth, validator, and infrastructure smoke.
- [ ] Step 2: For every VERIFIED/PASS include command, environment class, timestamp, result, and safe artifact path. For every BLOCKED include external action, permission, expected evidence, and owner category.
- [ ] Step 3: Choose exactly one final decision; any unresolved mandatory gate yields PRODUCTION READINESS BLOCKED and keeps Phase 7 locked.
- [ ] Step 4: Run fresh final gates:
  composer test
  npm run build
  composer audit
  npm audit --audit-level=high
  scripts/verification/secret-scan.sh .
  git diff --check
  git status
- [ ] Step 5: Run composer run dev and record Laravel, queue, Reverb, Pail/logging, and Vite only as development orchestration evidence.
- [ ] Step 6: Review diff, push the branch, and open a PR with purpose, affected modules, tenant/security impact, schema impact, tests, PHPStan before/after, browser/infrastructure evidence, rollback, and unresolved blockers.

### Task 11: Final standards review and integration decision

**Files:**
- Review: all branch changes and evidence documents

- [ ] Step 1: Review architecture, tenant isolation, authorization, stock/approval invariants, private files, queue behavior, observability, dependency scope, and secret handling.
- [ ] Step 2: Trace every Phase 6.4F requirement to a command result, test, browser artifact, managed-provider artifact, or explicit external blocker.
- [ ] Step 3: Merge only the reviewed PR when no application defect remains and the repository evidence is complete; never represent external blockers as verified.
- [ ] Step 4: Verify no ML files, endpoints, dependencies, tables, UI, or service calls were introduced.
