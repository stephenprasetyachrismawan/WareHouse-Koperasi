# Backup and Restore Runbook

## Production architecture

Use the managed PostgreSQL backup/PITR mechanism when the deployment provider supplies it, with encryption at rest/in transit and least-privilege restore access. Protect private evidence storage with versioning/replication or an encrypted object backup. Redis, sessions, queues, and generated exports are not authoritative business backups.

Required ownership: database/platform on-call. Required alert: backup job failure and restore-drill failure.

## Local synthetic drill

The scripts never target the active workspace for restore. The local path is intentionally unencrypted and must not be used for production recovery:

```bash
BACKUP_ROOT=/tmp/warehouse-backups \
BACKUP_DATABASE_PATH=/path/to/synthetic.sqlite \
BACKUP_PRIVATE_ROOT=/path/to/private \
BACKUP_REQUIRE_ENCRYPTION=false \
bash scripts/backup/create-backup.sh

RESTORE_ROOT=/tmp/warehouse-restore \
RESTORE_CONFIRM=YES \
bash scripts/backup/restore-drill.sh /tmp/warehouse-backups/warehouse-<timestamp>.tar
```

Expected output includes `RESTORE VERIFIED`, SQLite integrity `ok`, and restored private storage.

## Production backup

Set `BACKUP_REQUIRE_ENCRYPTION=true` and use a managed encrypted backup or a configured GPG recipient. Never put a passphrase, private key, database password, or object-storage secret in the repository or command line. Decrypt only into an isolated restore environment.

## Validation after restore

1. Verify the database integrity and representative Warehouses, memberships, Items, stock ledger/balances, procurement, QC, returns, Inbox, and report metadata.
2. Restore private evidence into an isolated private prefix and verify Policy-protected temporary access.
3. Run `php artisan stock:reconcile` and require zero mismatches.
4. Run tenant isolation and smoke tests.
5. Record start/end time, backup identifier, checks, failures, and escalation.

Never overwrite production during a drill. Never treat a database-only restore as complete while evidence objects are missing.
