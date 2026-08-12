# Phase 6.4B Performance Baseline

## Measurement policy

These are engineering baselines, not contractual business SLOs. A production capacity target requires an agreed dataset, concurrency profile, and infrastructure class.

## Required profiles

1. Read-heavy: dashboard, Inbox, stock list, Purchase Request list, approval queue, reports.
2. Mixed operational: reads plus pickup creation, approval, and stock mutation against an isolated database.
3. Worker: notification delivery, report generation, and scheduled reconciliation with fake external providers.

## Current execution result

The repository has no approved k6/wrk load generator and this VPS has no production-like staging database/provider isolation. Therefore no latency or throughput number is claimed here. A real load result must record:

```text
environment
dataset sizes
concurrency and duration
request count and error rate
p50/p95/p99
DB query count and memory
invariant/concurrency failures
```

The safe executable baseline available now is:

```bash
php artisan test tests/Feature/Dashboard tests/Feature/Reports
php artisan test tests/Feature/Inventory/StockReconciliationCommandTest.php
npm run build
```

No query/index change is claimed in 6.4B without an actual query-plan comparison. Existing dashboard/report tests remain the regression guard while a production-like dataset is provisioned.
