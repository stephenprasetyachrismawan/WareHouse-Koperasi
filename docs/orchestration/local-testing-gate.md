# Local Testing Gate

All pull request creation and merge decisions depend on the local VPS testing gate (`automation/warehouse-orchestrator/scripts/local-gate.sh`).

## Executed Checks
1. Pint formatting check (`vendor/bin/pint --test`)
2. PHPStan static analysis (`vendor/bin/phpstan analyse`)
3. Backend PHP & SQLite database tests (`php artisan test`)
4. Commit author identity verification
5. Composer run dev supervisor health check
