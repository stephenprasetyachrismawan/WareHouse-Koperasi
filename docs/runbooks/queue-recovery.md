# Queue Recovery Runbook

1. Check worker process, queue depth, and `php artisan queue:failed`.
2. Inspect a failed job without exposing its sensitive payload.
3. Confirm the job is idempotent and the core transaction is already durable.
4. Use `php artisan queue:retry <id>` after review; use `queue:forget` only after the incident decision.
5. Confirm notification delivery/export status and run a tenant-scoped smoke check.

Workers must be gracefully restarted after deployment so old code does not process new jobs indefinitely. Push retries are bounded; failed jobs are not permission to duplicate business state.
