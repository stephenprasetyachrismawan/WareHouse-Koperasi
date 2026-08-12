# Stock Reconciliation Runbook

Run the tenant-aware command from an operational host:

```bash
php artisan stock:reconcile
php artisan stock:reconcile --warehouse=<warehouse-id>
```

Exit code `0` means every checked item reconciles. Exit code `1` means mismatch, lock contention, or no matching warehouse. The command reports Warehouse, Item, ledger total, materialized balance, difference, and status. It never edits `stock_balances`.

On mismatch: preserve logs, stop any questionable correction, validate source transactions and opening-balance semantics, and use an explicit audited compensating/reversal Action after approval. Re-run reconciliation and record the incident. Do not “fix” a mismatch with a direct balance update.
