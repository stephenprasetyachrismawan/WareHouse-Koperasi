# Phase 6.4D Production Environment Verification Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Verify available production-like controls, close only evidence-backed blockers, and publish an honest Phase 6.4D readiness decision without implementing Phase 7.

**Architecture:** Use a disposable local PostgreSQL/Redis compatibility lane where the host supports it, while keeping managed backup/PITR, private object-storage recovery, DNS ownership, load capacity, and RPO/RTO approval as separate gates. Improve the existing production validator and CI documentation only at explicit seams; do not add business workflows or hide static-analysis debt.

**Tech Stack:** Laravel 13, PHP 8.4, Pest/PHPUnit, PostgreSQL 16, Redis 6, Laravel queue/cache/Reverb, Cloudflare Tunnel, GitHub Actions, existing shell backup scripts.

## Global Constraints

- Phase 7 Machine Learning remains disabled and no ML code may be added.
- Every test environment uses synthetic data and disposable database/storage namespaces.
- `php artisan migrate` is used for persistent deployment verification; `migrate:fresh` is restricted to disposable test databases.
- SQLite, local Redis, local PostgreSQL, and a development Vite server are compatibility evidence, not managed production evidence.
- No production secret, database password, tunnel token, private key, signed URL, or real user/evidence data may enter code, logs, fixtures, or commits.
- Every result is labeled IMPLEMENTED, VERIFIED, NOT VERIFIED, BLOCKED, or FAIL.
- The required workflow is branch from latest `main`, test-first for behavior changes, focused verification, commit, push, PR, review, merge, then post-merge build/dev/browser/seeder checks.

---

### Task 1: Reproduce and record the current environment blockers

**Files:**
- Create: `docs/verification/phase-6-4d-environment-inventory.md`
- Test: existing command/runbook probes; no application test required for read-only inventory

**Interfaces:**
- Consumes: OS/package/service/DNS/Cloudflare state and current readiness document.
- Produces: dated, secret-free inventory with exact commands and results.

- [ ] **Step 1: Capture baseline without secrets**

Run:

```bash
git rev-parse origin/main
git log -1 --format='%H %s' origin/main
php artisan about --only=environment,database
systemctl is-active cloudflared
ss -ltnp
```

Record only safe values, never environment secrets.

- [ ] **Step 2: Probe infrastructure availability**

Run:

```bash
command -v psql pg_isready redis-cli gitleaks trufflehog k6 minio mc
docker ps
curl -k -I https://wh.stevewithcode.net/
curl -k -I https://vite-warehouse.stevewithcode.net/
curl -k -I https://wh.stevewithcode.com/
```

Classify each component as available, unavailable, or externally blocked.

- [ ] **Step 3: Record Cloudflare and configuration facts**

Record ingress hostnames and local origin ports from `/etc/cloudflared/config.yml` without copying credentials. Record DNS/TLS/HTTP results separately for `.com` and `.net`.

- [ ] **Step 4: Review the inventory**

Search the generated document for secrets, unresolved placeholders, and claims that equate local evidence with production evidence. Remove any such claim.

- [ ] **Step 5: Commit the inventory**

```bash
git add docs/verification/phase-6-4d-environment-inventory.md
git commit -m "docs: record phase 6.4d environment inventory"
```

### Task 2: Provision disposable PostgreSQL and Redis compatibility services

**Files:**
- Create: `scripts/verification/start-disposable-services.sh`
- Create: `scripts/verification/stop-disposable-services.sh`
- Create: `docs/verification/phase-6-4d-postgresql-redis.md`
- Test: `tests/Feature/Operations/ProductionConfigurationCommandTest.php` only if validator behavior changes

**Interfaces:**
- `start-disposable-services.sh` creates a private temporary PostgreSQL cluster and Redis process only when required binaries exist, binds to loopback, and prints non-secret connection metadata.
- `stop-disposable-services.sh` stops only the recorded disposable PIDs/cluster; it never targets system services or production ports.
- The document records service versions, isolated ports, migration result, seed result, and cleanup result.

- [ ] **Step 1: Probe package/runtime support**

Confirm `postgres`, `initdb`, `pg_ctl`, `redis-server`, `redis-cli`, PHP `pdo_pgsql`, and PHP Redis support. If missing, record the exact unavailable gate and do not install unreviewed binaries.

- [ ] **Step 2: Write the disposable-service safety test/probe first**

The probe must reject non-loopback hosts, reject a non-empty persistent target directory, require an explicit temporary root, and fail if a PID file does not belong to the current run.

- [ ] **Step 3: Implement the smallest lifecycle scripts**

Use a `mktemp -d` root, loopback-only ports, random local credentials held only in the current process environment, and a trap/explicit stop command. Do not write credentials to the repository.

- [ ] **Step 4: Run clean PostgreSQL migration and Redis ping**

Use a disposable database and Redis namespace. Run:

```bash
php artisan migrate --force
php artisan db:seed --force
php artisan stock:reconcile
redis-cli -h 127.0.0.1 -p "$REDIS_PORT" ping
```

Record actual exit codes and counts.

- [ ] **Step 5: Run PostgreSQL-focused domain suites**

Run the existing stock, tenant, approval, goods receipt/QC, returns/replacement, notification, and export tests against the disposable PostgreSQL connection where the test harness supports it. Do not report SQLite results as PostgreSQL results.

- [ ] **Step 6: Stop and verify cleanup**

Stop only the disposable services and verify their ports are closed. Record failures as NOT VERIFIED/BLOCKED.

- [ ] **Step 7: Commit**

```bash
git add scripts/verification docs/verification/phase-6-4d-postgresql-redis.md tests/Feature/Operations/ProductionConfigurationCommandTest.php
git commit -m "ops: add disposable postgres and redis verification lane"
```

### Task 3: Harden production configuration validation for actual endpoint dependencies

**Files:**
- Modify: `app/Console/Commands/ValidateProductionConfigurationCommand.php`
- Modify: `config/security.php`
- Modify: `config/reverb.php`
- Test: `tests/Feature/Operations/ProductionConfigurationCommandTest.php`

**Interfaces:**
- `ops:validate-production` remains secret-free and fails when production uses a localhost Vite/Reverb endpoint, wildcard origin, insecure scheme, sync queue, or non-private storage.
- Tests can inject config values and assert safe pass/fail output without changing `.env`.

- [ ] **Step 1: Add failing tests**

Cover production rejection for `VITE_DEV_SERVER_ORIGIN` containing localhost, `REVERB_HOST=localhost`, wildcard origins, and a missing production storage driver. Keep the existing valid-config test explicit about a non-local WSS host.

- [ ] **Step 2: Run the focused test red**

```bash
php artisan test tests/Feature/Operations/ProductionConfigurationCommandTest.php
```

Expected: new invalid production configurations are not yet rejected.

- [ ] **Step 3: Implement minimal validation**

Add only checks required by the failing tests. Never print host credentials or full configuration values. Keep local development configuration valid outside the production command.

- [ ] **Step 4: Run green and formatting**

```bash
php artisan test tests/Feature/Operations/ProductionConfigurationCommandTest.php
vendor/bin/pint --test
```

- [ ] **Step 5: Commit**

```bash
git add app/Console/Commands/ValidateProductionConfigurationCommand.php config/security.php config/reverb.php tests/Feature/Operations/ProductionConfigurationCommandTest.php
git commit -m "security: reject local realtime endpoints in production validation"
```

### Task 4: Add CI security and production regression gates without hiding debt

**Files:**
- Modify: `.github/workflows/tests.yml`
- Create: `scripts/verification/secret-scan.sh`
- Create: `docs/verification/ci-gates.md`
- Test: `tests/Feature/Operations/ProductionConfigurationCommandTest.php` if command integration is added

**Interfaces:**
- The CI workflow runs lockfile installation, Pint, PHPStan, Laravel tests, frontend build, dependency audit, and security-focused tests on pull requests and pushes to `main`.
- `secret-scan.sh` uses an installed approved scanner when present and exits nonzero when required scanner tooling is unavailable in a security gate; it never treats unavailable scanning as clean.
- PHPStan errors remain visible; no `ignoreErrors: .*` or broad baseline is introduced.

- [ ] **Step 1: Write the scanner behavior test/probe first**

Verify the script fails with `NOT VERIFIED` when no approved scanner exists and succeeds only on a clean scanner result. Use a temporary synthetic fixture containing a clearly fake marker, never a real credential.

- [ ] **Step 2: Implement the scanner wrapper and CI wiring**

Use a pinned scanner version/action approved by the repository policy. Keep audit and scan commands deterministic and avoid automatic dependency upgrades.

- [ ] **Step 3: Run local gates**

```bash
composer audit
npm audit --audit-level=high
vendor/bin/pint --test
php artisan test tests/Feature/Security tests/Feature/Operations
npm run build
```

- [ ] **Step 4: Record static-analysis status**

Run `vendor/bin/phpstan analyse`, capture the count and changed-file impact, and classify existing errors. Do not claim `composer test` passes unless the complete command actually succeeds.

- [ ] **Step 5: Commit**

```bash
git add .github/workflows/tests.yml scripts/verification/secret-scan.sh docs/verification/ci-gates.md
git commit -m "ci: enforce production security verification gates"
```

### Task 5: Execute backup, restore, load, and browser evidence

**Files:**
- Create: `docs/verification/phase-6-4d-evidence.md`
- Modify: `docs/PRODUCTION-READINESS.md`
- Test: existing backup, reconciliation, health, security, seeder, and browser seams

**Interfaces:**
- Evidence document contains exact environment, dataset, command, timestamps, metrics, and result for every attempted gate.
- It distinguishes local synthetic backup/restore from PostgreSQL/PITR and private object-storage recovery.

- [ ] **Step 1: Run backup/restore only in isolated targets**

Run the existing local drill and, if a real provider is available, a PostgreSQL/private-object restore into a new isolated target. Verify application boot, representative entities, reconciliation, tenant isolation, evidence policy access, and no external side effects.

- [ ] **Step 2: Run load profiles only on disposable staging**

If an approved load tool and isolated services exist, record read-heavy, mixed operational, reporting, and realtime profiles with p50/p95/p99, errors, resource saturation, queue backlog, and invariants. Otherwise record NOT VERIFIED/BLOCKED.

- [ ] **Step 3: Run production-style browser checks**

Verify login-safe synthetic flow, dashboard, Inbox, health, Vite asset loading, CSP, cookies, no localhost asset/Reverb references, export/evidence authorization where authenticated staging exists, and cross-tenant denial.

- [ ] **Step 4: Verify seeders twice**

Run `migrate` and `db:seed` twice against an isolated non-persistent database. Assert both warehouses, all core workflow counts, no null approval UUIDs, and no duplicate core records.

- [ ] **Step 5: Update blocker table**

For PostgreSQL, Redis, object storage, backups, alerting, load, RPO/RTO, secret scan, production config, PHPStan, public hostname, browser smoke, and seeds, record previous state, verification, result, owner, and whether the gate remains blocked.

- [ ] **Step 6: Commit**

```bash
git add docs/verification/phase-6-4d-evidence.md docs/PRODUCTION-READINESS.md
git commit -m "docs: record phase 6.4d verification evidence"
```

### Task 6: Review, integrate, and post-merge verify

**Files:**
- Review all branch changes from `origin/main`

- [ ] **Step 1: Run code/spec review**

Check correctness, security, simplicity, architecture, performance, and every Phase 6.4D requirement. Resolve critical findings before publishing.

- [ ] **Step 2: Run final pre-PR commands**

```bash
git diff --check
vendor/bin/pint --test
php artisan test
npm run build
composer audit
npm audit --audit-level=high
```

Report PHPStan/composer-test failure honestly if it remains.

- [ ] **Step 3: Push and create PR**

```bash
git push -u origin hardening/production-environment-verification
gh pr create --base main --head hardening/production-environment-verification --title "chore(production): verify Phase 6.4 blockers" --body-file /tmp/phase-6-4d-pr.md
```

- [ ] **Step 4: Merge the reviewed PR**

```bash
gh pr merge <number> --merge --delete-branch
git switch main
git pull --ff-only origin main
```

- [ ] **Step 5: Run post-merge build before dev**

```bash
npm run build
composer run dev
```

Confirm Laravel, queue, Reverb, logs, and Vite start; stop conflicting stale process groups safely before retrying.

- [ ] **Step 6: Run post-merge Cloudflare/browser/seeder checks**

Verify `wh.stevewithcode.net`, `vite-warehouse.stevewithcode.net`, health endpoints, actual JS asset requests, browser console/network, and two-run isolated seed. Test `wh.stevewithcode.com` and keep it BLOCKED if DNS/TLS does not resolve.

- [ ] **Step 7: Final classification**

Set exactly `PASS`, `PASS WITH ACCEPTED RISKS`, `BLOCKED`, or `FAIL`. Unlock Phase 7 only for PASS or explicitly approved non-critical accepted risks; otherwise state Phase 7 remains locked.
