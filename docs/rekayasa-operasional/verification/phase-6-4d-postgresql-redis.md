# Phase 6.4D PostgreSQL and Redis Compatibility Evidence

Date: 2026-08-14 UTC

These results use disposable loopback services created by `scripts/verification/start-disposable-services.sh`. The services were stopped after each run. They are compatibility evidence, not managed production infrastructure.

## PostgreSQL

| Gate | Result | Evidence |
|---|---|---|
| Clean schema migration | VERIFIED | PostgreSQL 16.14; `php artisan migrate --force` completed on an isolated database |
| Seeder repeatability | VERIFIED | `php artisan db:seed --force` completed twice without duplicate-core failures |
| Seed completeness | VERIFIED | 2 warehouses, 12 users, 48 items, 45 balances, 58 transactions, 16 pickups, 33 purchase requests, 19 purchase orders, 11 receipts, 9 inspections, 12 returns, 61 inbox notifications, 0 null approval UUIDs |
| Healthy reconciliation | VERIFIED | 45 materialized balances matched their ledger totals; all reported differences were 0 |
| Mismatch handling | VERIFIED | Synthetic balance mismatch produced exit 1 and `status=MISMATCH`; balance remained unrepaired |
| PostgreSQL hardening suite | VERIFIED | Operations/Security/Inventory/Procurement/Returns/Notifications/Reports: 450 passed, 1,013 assertions |
| Runtime least privilege | NOT VERIFIED | No separate managed migration/runtime database identities are provisioned on this host |
| Database TLS | NOT VERIFIED | The disposable service is loopback-only and does not represent a remote TLS deployment |

During the first PostgreSQL seeding attempt, PostgreSQL correctly rejected `FOR UPDATE` on an aggregate `COUNT(*)`. A failing compatibility test was added and the three sequence generators now lock the tenant warehouse row and count locked records instead. The corrected migration/seeder and focused PostgreSQL test pass.

## Redis

| Gate | Result | Evidence |
|---|---|---|
| Redis server/client | VERIFIED | Redis 6.2.20 and PHP Redis extension 6.2.0 |
| Cache path | VERIFIED | Targeted PostgreSQL suite ran with Redis cache and passed |
| Queue worker | VERIFIED | Real `GenerateOperationalReportExportJob` dispatched to `exports`, processed by `queue:work redis`, status changed `pending` to `completed` |
| Tenant-aware prefix | VERIFIED | Disposable run used an isolated `phase64d-queue-` prefix |
| Redis failure behavior | NOT VERIFIED | No managed failure-injection environment is available; production dependency classification remains documented risk |

## Boundaries

No MinIO/S3-compatible server, managed PostgreSQL backup/PITR, private object-storage bucket, FCM sandbox, or production-like staging topology is available on this host. Those gates remain `NOT VERIFIED` rather than being inferred from local compatibility results.
