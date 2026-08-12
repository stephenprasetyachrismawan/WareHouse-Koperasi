# Phase 6.4B Failure Matrix

This matrix records the behavior currently implemented or verified by tests. Infrastructure drills that cannot run on this VPS are explicitly marked **NOT VERIFIED**.

| Failure | Expected behavior | Data integrity | Retry / recovery | Signal and owner |
| --- | --- | --- | --- | --- |
| Database unavailable | Web/readiness returns safe 503; liveness remains minimal | No partial workflow commit | Restore DB or fail over, then run smoke/reconciliation | `health.readiness_failed`; database owner |
| Redis unavailable | Features using Redis must follow configured dependency policy; no tenant data is reconstructed from cache | Core DB transactions remain authoritative | Restore Redis; invalidate/rebuild disposable cache | NOT VERIFIED in isolated Redis drill; platform owner |
| Queue worker stopped | Core request transaction completes; notifications/exports wait in durable queue | No duplicate business state on worker restart | Start supervised worker; inspect/retry failed jobs | Queue depth/failed jobs; operations owner |
| Reverb stopped | Dashboard and Inbox remain HTTP-readable; mutations do not depend on realtime | Inbox remains source of truth | Restart Reverb; browser reconnects/polls | Reverb process health; operations owner |
| FCM unavailable | Inbox/realtime persistence remains; delivery row records retryable failure | Business transaction is not rolled back for push failure | Bounded job retry then failed-job review | push delivery failure; notifications owner |
| Private object storage unavailable | Evidence/export operation fails explicitly before claiming a valid file | Database must not claim a persisted object | Restore storage, retry the explicit operation | storage error logs; operations owner |
| Scheduler invocation fails | Other web/worker paths continue; next scheduled run remains available | No silent stock correction | Run command manually after diagnosis | scheduler process log; operations owner |
| Browser disconnect / duplicate request | Idempotent Actions and durable Inbox handle retry | Stock/workflow side effects remain exactly-once where existing keys/state guards apply | Refresh and inspect persistent state | request ID and domain audit; support owner |
| Worker retry | Job reloads authoritative records and uses bounded attempts | Delivery/export jobs are idempotent; core stock Action uses idempotency key | Laravel retry/backoff and failed-job review | `failed_jobs`; queue owner |

## Verified in code/tests

- Stock movement uses a database transaction, row lock, version check, and unique warehouse/idempotency key.
- Notification creation uses a recipient/correlation unique key and queued push delivery uses a durable delivery row.
- Report export generation is queued/idempotent at its report record and private file boundary.
- Stock reconciliation is tenant-by-tenant, lock-protected, returns a non-zero exit code on mismatch, and never mutates `stock_balances`.

## Not verified on this environment

There is no isolated production-like Redis, object-storage provider, fake FCM provider, or disposable PostgreSQL staging target available in this workspace. No production outage claim is made from unit/feature tests alone.
