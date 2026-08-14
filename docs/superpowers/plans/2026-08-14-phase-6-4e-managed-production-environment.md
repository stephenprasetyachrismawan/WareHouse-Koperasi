# Phase 6.4E Managed Production Environment Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Prepare a truthful, provider-neutral production contract and executable infrastructure smoke seam while preserving `PRODUCTION READINESS: BLOCKED` until managed resources and evidence exist.

**Architecture:** Keep provider provisioning external because the current host has no valid provider credentials. Extend the existing Laravel production validator and add a safe Artisan smoke command that checks configured PostgreSQL, Redis, and private object storage without business mutations. Record the provider-neutral contract and external actions in an ADR/inventory.

**Tech Stack:** Laravel 13, PHP 8.4, Pest/PHPUnit, PostgreSQL, Redis, S3-compatible private storage, Laravel Reverb, existing Cloudflare Tunnel, GitHub Actions.

## Global Constraints

- Phase 7 Machine Learning remains disabled and no ML code may be added.
- No managed resource may be claimed without executable provider evidence.
- No credentials, tokens, signed URLs, or production data enter Git, logs, tests, or documentation.
- Persistent environments use `php artisan migrate`; no persistent `migrate:fresh`.
- Production validator failure must be safe and actionable without printing secret values.
- Infrastructure smoke is read-only except for an explicitly confirmed synthetic private-storage put/delete probe.
- Readiness remains `PRODUCTION READINESS: BLOCKED` in Phase 6.4E; final sign-off belongs to Phase 6.4F.

---

### Task 1: Record the provider-neutral architecture and current external blockers

**Files:**
- Create: `docs/architecture/0011-managed-production-environment.md`
- Create: `docs/superpowers/plans/2026-08-14-phase-6-4e-managed-production-environment.md`
- Create: `docs/verification/phase-6-4e-production-infrastructure.md`

**Interfaces:**
- Produces the provider-neutral production contract, status vocabulary, external-action table, and evidence checklist.
- Does not claim any managed resource exists.

- [x] **Step 1: Capture actual provider availability**

Record that AWS CLI is present but `aws sts get-caller-identity` cannot authenticate, no provider Terraform/CLI is available, Cloudflare Tunnel is active, and current readiness is blocked.

- [x] **Step 2: Write and review the ADR**

Keep the decision explicit: provider-neutral contract now; provider-specific ADR only after an approved provider and scoped credentials are supplied.

- [x] **Step 3: Commit the design documents**

```bash
git add docs/architecture/0011-managed-production-environment.md \
  docs/superpowers/plans/2026-08-14-phase-6-4e-managed-production-environment.md \
  docs/verification/phase-6-4e-production-infrastructure.md
git commit -m "docs(production): design phase 6.4e managed environment"
```

### Task 2: Extend production configuration validation test-first

**Files:**
- Modify: `app/Console/Commands/ValidateProductionConfigurationCommand.php`
- Modify: `config/security.php`
- Modify: `tests/Feature/Operations/ProductionConfigurationCommandTest.php`

**Interfaces:**
- `ops:validate-production` reports safe named checks and exits nonzero for invalid production dependencies.
- Required production checks include PostgreSQL, TLS mode, Redis-backed queue/cache/session, private S3 storage, HTTPS Reverb, exact origins, no Vite dev origin, and nonempty required public-safe identifiers.

- [x] **Step 1: Add failing tests**

Add tests that configure a valid production contract and then independently reject SQLite, `DB_SSLMODE=prefer`, database queue/cache/session, local private storage, missing S3 bucket, localhost Reverb/Vite, wildcard Reverb origins, and missing Reverb app credentials. Assert only check names and safe messages.

- [x] **Step 2: Run focused tests to prove red**

```bash
php artisan test tests/Feature/Operations/ProductionConfigurationCommandTest.php
```

Expected: the new checks fail because the current validator does not enforce the complete Phase 6.4E contract.

- [x] **Step 3: Implement the smallest validator/config change**

Use config values already loaded by Laravel; do not print credential values. Keep local development valid outside the production command. Treat `VITE_DEV_SERVER_ORIGIN` as required-unset in production, while allowing it in local development.

- [x] **Step 4: Run focused green and formatting**

```bash
php artisan test tests/Feature/Operations/ProductionConfigurationCommandTest.php
vendor/bin/pint --test
```

- [x] **Step 5: Commit the validator slice**

```bash
git add app/Console/Commands/ValidateProductionConfigurationCommand.php config/security.php tests/Feature/Operations/ProductionConfigurationCommandTest.php
git commit -m "security: validate managed production dependencies"
```

### Task 3: Add safe infrastructure smoke command test-first

**Files:**
- Create: `app/Console/Commands/VerifyProductionInfrastructureCommand.php`
- Modify: `app/Console/Kernel.php` only if command auto-discovery is not active
- Create: `tests/Feature/Operations/ProductionInfrastructureCommandTest.php`
- Modify: `README.md` or deployment docs with exact invocation

**Interfaces:**
- Command signature: `ops:verify-production-infrastructure {--storage-smoke : Write/delete one synthetic object} {--confirm-storage-smoke : Confirm the synthetic object probe}`.
- Default mode checks validator, database `select 1`, and Redis ping; no business mutation.
- Storage mode requires both flags, writes a unique `phase-6-4e/smoke/<uuid>` object to the configured private disk, reads it, deletes it, and reports the operation without printing the key.
- Any failure returns exit code 1 and names the failed component.

- [x] **Step 1: Add failing command tests**

Cover: validator failure short-circuits; database/Redis success is reported; storage smoke refuses missing confirmation; successful storage smoke writes/reads/deletes a synthetic object; no business table is changed. Use Laravel fakes for external boundaries and assert output contains no secret fixture values.

- [x] **Step 2: Run focused tests to prove red**

```bash
php artisan test tests/Feature/Operations/VerifyProductionInfrastructureCommandTest.php
```

- [x] **Step 3: Implement minimal command**

Use the database connection and Redis facade for read-only probes. Inject/use the private filesystem disk for the confirmed synthetic object lifecycle. Catch provider exceptions into safe component failures; do not expose exception messages containing connection details.

- [x] **Step 4: Run focused green and format**

```bash
php artisan test tests/Feature/Operations/VerifyProductionInfrastructureCommandTest.php
vendor/bin/pint --test
```

- [x] **Step 5: Commit the smoke seam**

```bash
git add app/Console/Commands/VerifyProductionInfrastructureCommand.php tests/Feature/Operations/VerifyProductionInfrastructureCommandTest.php README.md
git commit -m "ops: add safe production infrastructure smoke command"
```

### Task 4: Update environment contract, inventory, and CI documentation

**Files:**
- Modify: `.env.example`
- Modify: `README.md`
- Create/modify: `docs/verification/phase-6-4e-production-infrastructure.md`
- Modify: `docs/PRODUCTION-READINESS.md`
- Modify: `.github/workflows/tests.yml` only if a safe validator contract check can run without provider secrets

**Interfaces:**
- Documentation names required variable keys but contains no values.
- Inventory uses only `IMPLEMENTED`, `VERIFIED`, `NOT VERIFIED`, `BLOCKED`, and `EXTERNAL ACTION REQUIRED`.
- Readiness remains `PRODUCTION READINESS: BLOCKED` and Phase 7 remains locked.

- [x] **Step 1: Add safe provider-neutral contract documentation**

Document `DB_SSLMODE`, Redis TLS/URL expectations, production storage disk, canonical app/Reverb host, exact Reverb origins, and secret-manager ownership. Keep local defaults unchanged.

- [x] **Step 2: Document external actions**

Include managed PostgreSQL/TLS/users, Redis/TLS/auth, private object storage/encryption/versioning, DNS/TLS/Reverb ingress, secret manager, process supervision, backup/PITR, monitoring/alerts, and RPO/RTO owner/sign-off evidence.

- [x] **Step 3: Update readiness evidence**

State that repository preparation is implemented, while actual managed resources are not verified and Phase 6.4F gates remain pending.

- [x] **Step 4: Run documentation and secret review**

```bash
git diff --check
rg -n "password|secret|token|BEGIN .*PRIVATE KEY|AKIA" docs .env.example
```

Expected: only variable names/placeholders and no credential values.

- [x] **Step 5: Commit documentation**

```bash
git add .env.example README.md docs/verification/phase-6-4e-production-infrastructure.md docs/PRODUCTION-READINESS.md .github/workflows/tests.yml
git commit -m "docs(production): record phase 6.4e infrastructure contract"
```

### Task 5: Review and complete the repository branch

**Files:**
- Review all branch changes against `origin/main`.

- [ ] **Step 1: Run standards and specification review**

Check tenant/security boundaries, no secret leakage, safe command behavior, provider-neutral scope, no ML files, and exact Phase 6.4E/6.4F boundary.

- [ ] **Step 2: Run full verification**

```bash
php artisan test
vendor/bin/pint --test
npm run build
composer audit
npm audit --audit-level=high
```

Run `composer test` and record PHPStan failure if it remains; do not suppress it.

- [ ] **Step 3: Run repository secret scan**

```bash
bash scripts/verification/secret-scan.sh
```

- [ ] **Step 4: Review diff and commit list**

```bash
git diff --check
git diff origin/main...HEAD
git log origin/main..HEAD --oneline
```

- [ ] **Step 5: Push and open PR**

```bash
git push -u origin hardening/managed-production-environment
gh pr create --base main --head hardening/managed-production-environment
```

- [ ] **Step 6: Merge only the reviewed PR**

```bash
PR_NUMBER="$(gh pr list --head hardening/managed-production-environment --state open --json number --jq '.[0].number')"
gh pr merge "$PR_NUMBER" --merge --delete-branch
git switch main
git pull --ff-only origin main
```

### Task 6: Post-merge build, dev, and public verification

**Files:**
- No repository edits expected unless verification exposes a real regression.

- [ ] **Step 1: Build before dev orchestration**

```bash
npm run build
```

- [ ] **Step 2: Start local development orchestration**

```bash
composer run dev
```

Confirm Laravel, queue, Reverb, logs, and Vite start. This is local-DX evidence, not production supervision.

- [ ] **Step 3: Probe public tunnel and assets**

Verify the configured `.net` application/health endpoints and Vite asset origin. Verify `.com` separately and keep it blocked if DNS/TLS fails.

- [ ] **Step 4: Run the smoke command only when a real production-like environment exists**

```bash
php artisan ops:verify-production-infrastructure
```

Do not run storage write mode against an unapproved persistent environment.

- [ ] **Step 5: Report remaining 6.4F gates**

List managed restore, object restore, load/capacity, RPO/RTO, static-analysis, full authenticated browser, and final readiness sign-off as pending or blocked with evidence.
