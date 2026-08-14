# Production Readiness — Phase 6.4

Last evaluated: 2026-08-14 UTC

## Final decision

```text
PRODUCTION READINESS: BLOCKED
```

The code-level 6.4A/6.4B gates and the available local PostgreSQL/Redis compatibility lane are green, but required managed production-environment evidence is not available. Phase 7 Machine Learning must not start.

Phase 6.4D implementation merge: `7eb1d809280f3b9bac730aa43ca33df5360bf9d8` (PR #45). Latest `main`, including the post-merge evidence update: `03797345eb1be9eab90685db5e16acf0189f0353` (PR #47).

## Environment assumptions

- Current workspace runtime uses local development configuration; the verification lane additionally used disposable PostgreSQL 16.14 and Redis 6.2.20 on loopback.
- Current runtime is development configuration (`APP_ENV=local`, `APP_DEBUG=true`); it is not a production target.
- No managed PostgreSQL staging/backup/PITR, private S3-compatible object storage, fake FCM provider, or production-like load environment is available here.
- Browser smoke check: the post-merge isolated browser loaded `https://wh.stevewithcode.net/` with a non-empty accessibility tree, visible navigation/hero/services/testimonials/FAQ/footer content, and the expected Laravel title. `/health/live` and `/health/ready` returned 200. Console findings are recorded as environment blockers below; they are not treated as a clean production-browser result.

## Slice evidence

| Slice | PR / merge | Result |
| --- | --- | --- |
| 6.4A Security & Tenant Isolation | PR #35 / `67cafa7` | Merged; focused security and 585 feature tests passed |
| 6.4B Resilience, Performance & Observability | PR #36 / `f3d9a94` | Merged; 593 feature tests passed |
| 6.4C Backup, Restore & Gate | [PR #37](https://github.com/stephenprasetyachrismawan/WareHouse-Koperasi/pull/37) / `51cba80` | Merged; final gate BLOCKED |
| Seeder/data integrity correction | [PR #43](https://github.com/stephenprasetyachrismawan/WareHouse-Koperasi/pull/43) / `347c0aa` | Merged; full seed and approval UUID regression fixed |
| Phase 6.4D environment verification | [PR #45](https://github.com/stephenprasetyachrismawan/WareHouse-Koperasi/pull/45) / `7eb1d80` | Merged; local compatibility evidence recorded, managed gates remain blocked |

## Latest post-merge verification

- `php artisan test`: 606 passed / 606, 1,458 assertions.
- `tests/Feature/Seeders/DatabaseSeederCompletenessTest`: full business seed passed twice without duplicate core records; both `WH-PUSAT` and `WH-BARAT` are present and no seeded approval has a null UUID.
- PostgreSQL 16.14: clean migrations and two seed runs passed; 2 warehouses, 12 users, 48 items, 45 balances, 58 transactions, 16 pickups, 33 purchase requests, 19 purchase orders, 11 receipts, 9 inspections, 12 returns, 61 notifications, 0 null approval UUIDs.
- PostgreSQL stock reconciliation: 45 balances healthy; controlled mismatch was detected with exit 1 and was not silently repaired.
- PostgreSQL/Redis focused regression: 450 passed / 1,013 assertions with Redis cache and real loopback services.
- Redis queue worker: real export job on `exports` queue changed `pending` to `completed`.
- `vendor/bin/pint --test`: passed.
- `npm run build`: passed.
- `composer audit`: no advisories; `npm audit --audit-level=high`: 0 vulnerabilities.
- Gitleaks v8.30.1: history/worktree scan passed with one exact reviewed false-positive documentation allowlist; no active credential found.
- `composer run dev`: passed after stopping the pre-existing dev session that owned ports 8000, 8080, and 5173; Laravel, queue, Reverb, log tail, and Vite then started successfully in the intended order.
- Post-merge `composer run dev`: passed after `npm run build`; Laravel served on loopback `:8000`, Vite on `:5173`, queue listener, Pail, and Reverb on `:8080` started. This is development orchestration evidence, not production supervision evidence.
- Browser smoke at `https://wh.stevewithcode.net`: page rendered with non-empty DOM and screenshot; Vite `@vite/client`, `resources/js/app.js`, and `resources/js/welcome.js` returned 200; `/health/live` and `/health/ready` returned 200.
- `https://vite-warehouse.stevewithcode.net/` returned the normal Vite development-server landing response (root is not the Laravel application); the Laravel page successfully loaded its Vite assets from that hostname.
- `https://wh.stevewithcode.com`: **NOT VERIFIED** — DNS returned `ERR_NAME_NOT_RESOLVED`. The hostname exists in `/etc/cloudflared/config.yml`, but the DNS record is an external Cloudflare action and remains a blocker.
- Browser observed configuration failures: Laravel Boost's development browser logger was rejected by CSP, Cloudflare Insights was not in `script-src`, and Reverb attempted `wss://localhost:8080` from the public page and failed. These do not make the public homepage blank—the asset requests returned 200 and the page rendered—but require environment-specific production configuration before sign-off.
- Post-merge PostgreSQL/seeder rerun: clean PostgreSQL 16.14 `php artisan migrate --force` passed, `php artisan db:seed --force` passed twice, and counts remained `warehouses=2`, `users=12`, `items=48`, `stock_balances=45`, `stock_transactions=58`, `pickup_requests=16`, `purchase_requests=33`, `purchase_orders=19`, `goods_receipts=11`, `quality_inspections=9`, `return_requests=12`, `inbox_notifications=61`, `null_approval_uuids=0`. Persistent development `php artisan migrate --no-interaction` reported `Nothing to migrate`.
- `composer test`: Pint passed, but PHPStan exceeded the 300-second Composer process timeout. This remains an explicit static-analysis blocker; no suppression or new baseline was added.

## Security and correctness

- Tenant UUID/path swaps, inactive membership, privilege escalation, private export/evidence, broadcast authorization, headers, and request correlation tests pass in the available suites.
- App Admin permission fallback now fails closed.
- Stock reconciliation detects ledger/materialized balance differences and does not mutate stock.
- Full feature suite on 6.4B: 593 passed / 593; final 6.4C suite: 597 passed / 597.
- Composer audit: no advisories. npm audit: 0 vulnerabilities after the lockfile update to nanoid 3.3.18.
- PHPStan: command/process timeout; no suppressions added.
- Secret scanning: **VERIFIED** with Gitleaks v8.30.1. The exact historical documentation false positive and decision are recorded in `.gitleaks.toml`.

## Backup and restore evidence

```text
Backup executed: YES — isolated synthetic SQLite/local-private drill
Restore executed: YES — isolated disposable target
Environment: local SQLite and synthetic private object
Data checks: SQLite integrity_check=ok; sample row restored; private evidence marker restored
Stock reconciliation: VERIFIED for the disposable PostgreSQL seeded dataset; NOT VERIFIED against a managed production backup
Evidence object checks: synthetic private object restored and readable in isolated target
Encrypted production backup: BLOCKED — no managed provider/key evidence
PostgreSQL/PITR: BLOCKED — no managed provider/restore target
```

The local drill is executable evidence for the script safety path, not proof of production recovery.

The latest local drill completed on 2026-08-13 UTC with `backup executed: YES`, `restore executed: YES`, `database_integrity=ok`, a restored synthetic row, and a restored synthetic private evidence object. It is not production recovery evidence.

## RPO / RTO

Business-approved values are not present in repository requirements. Proposed engineering targets requiring explicit operations sign-off are:

- RPO: 15 minutes for relational business data; evidence-object RPO must match the storage replication policy.
- RTO: 60 minutes for web/database recovery; queue/realtime providers may recover as a separately documented degraded service.

Because these targets lack sign-off and the production backup provider is not configured, the RPO/RTO gate is **BLOCKED**.

## Deployment and operational controls

Runbooks exist for deployment, backup/restore, queue, stock reconciliation, Reverb, and security incidents. The production validator requires debug off, HTTPS, secure session cookies, non-sync queue, private storage, Reverb TLS, and pinned origins. It fails safely on this local configuration.

Required before PASS: production secrets managed externally, runtime DB least privilege, encrypted database and object backups, scheduled backup monitoring, isolated restore drill with representative PostgreSQL/private evidence, production-like staging, load profile evidence, approved RPO/RTO, production Reverb/CSP/DNS, successful static-analysis gate, and named operational owners.

## Phase 6.4D blocker table

| Blocker | Previous state | Verification | Result |
|---|---|---|---|
| PostgreSQL migration/concurrency | SQLite-only evidence | PostgreSQL 16.14 migration, seed, focused domain/security suite | VERIFIED for disposable compatibility; managed target NOT VERIFIED |
| PostgreSQL/private object restore | Not demonstrated | No managed PostgreSQL/PITR or S3-compatible private target available | BLOCKED |
| Encrypted backup automation | Not demonstrated | No managed provider/key/retention evidence | BLOCKED |
| Backup-failure alert | Not connected | No monitoring target available | BLOCKED |
| Load/capacity | Missing | Local health-only ApacheBench baseline: 200 requests, c10, 0 failures, p50 242 ms, p95 409 ms, p99 440 ms | BLOCKED for production-like capacity |
| RPO/RTO | Proposed only | 15-minute RPO / 60-minute RTO have no authorised sign-off or measured managed restore | BLOCKED |
| Secret scan | Not verified | Gitleaks v8.30.1 checksum-verified scan passed; exact historical docs false positive reviewed | VERIFIED |
| Production configuration | Development runtime | `APP_ENV=local`, `APP_DEBUG=true`; validator now rejects local endpoints/private local disk in production mode; browser saw `wss://localhost:8080` | BLOCKED |
| Static analysis | Existing debt | `composer test` timed out in PHPStan after Pint passed | BLOCKED |
| Public `.com` hostname | DNS unresolved | `wh.stevewithcode.com` still fails DNS; `.net` Laravel/health/assets work | BLOCKED |

## Blockers

1. Production-like backup and restore are not demonstrated for PostgreSQL and private object storage.
2. Encrypted backup automation and backup-failure alerting are not connected to production infrastructure.
3. Load/capacity evidence is not verified in an isolated production-like environment.
4. RPO/RTO have no explicit operations sign-off.
5. `composer test` cannot complete because PHPStan exceeds the configured process timeout.
6. Current workspace is not a production configuration (`APP_DEBUG=true`); public Reverb/CSP endpoint validation is not closed, with browser evidence of failed `wss://localhost:8080` and CSP violations.
7. `wh.stevewithcode.com` remains DNS-unresolved.

## Accepted risks

No critical risk is accepted as a production sign-off in this evaluation. The above items remain blockers.
