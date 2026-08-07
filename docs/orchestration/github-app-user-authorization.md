# GitHub App User Authorization

Each registered contributor must authorize their GitHub identity once using Device Flow.

## Operational Commands
```bash
# Authorize user
npm run auth:user -- stephenprasetyachrismawan
npm run auth:user -- wiladahtulawaliah2002-maker
npm run auth:user -- zhafirzidann

# Check authorization status
npm run auth:status
```

Tokens are saved in SQLite database under `/srv/warehouse-koperasi/state/orchestrator.sqlite`.
