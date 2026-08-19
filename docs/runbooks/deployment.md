# Deployment Runbook

This runbook has two parts. Read the status line before following either one.

```text
CURRENT DEVELOPMENT DEPLOYMENT: Docker + GitHub CI/CD — see CD.md (authoritative)
PRODUCTION DEPLOYMENT:          NOT ACTIVE — the "Future production target" section
                                 below is aspirational, not something to run today
```

## Current development deployment (normal path)

The development VPS runs an immutable Docker image built and tested entirely by GitHub Actions. The VPS never builds application code.

```text
GitHub CI (quality, integration) — see CI.md
  ↓
GHCR immutable image (trusted main only)
  ↓
manual promotion: workflow_dispatch, run_deploy=true
  ↓
GitHub Environment: development
  ↓
deploy/deploy-development.sh (runs on the VPS, over SSH)
  ↓
health verification (/health/live, /health/ready, homepage smoke)
```

The VPS does **not**, during a normal deployment:

- `git pull` application code
- `composer install`
- `npm run build`
- run PHPStan or the test suite
- `docker build`

All of that already happened on GitHub before an image reaches GHCR. Full detail, including the rollback model and its database-migration limitation, is in [`CD.md`](../../CD.md).

## Break-glass: legacy `composer dev` runtime

The pre-Docker development runtime (`composer dev`, supervised by `automation/warehouse-orchestrator/scripts/dev-supervisor.sh`) is kept only as documented emergency fallback. It is **not** the normal deployment method, and it does not start automatically — `warehouse-orchestrator.service` no longer auto-starts it on boot (see the boot-safety fix in Phase 6.4F-0B). Only invoke this procedure manually, and only when the Docker runtime is genuinely unusable.

**Before starting legacy:** the legacy runtime binds the same ports as Docker (`8000` web, `8080` Reverb, `5173` Vite dev server). Both cannot run at once.

```bash
# 1. Stop Docker first (never start legacy while Docker still holds the ports)
cd /srv/warehouse-koperasi/deploy
sudo -u deploy docker compose -f compose.yaml --env-file .env stop

# 2. Confirm ports are actually released
sudo ss -ltnp | grep -E ':(8000|8080|5173)\s' || echo "released"

# 3. Start the legacy supervisor manually (does not run automatically)
tmux new-session -d -s warehouse-composer-dev \
  "bash /opt/project/WareHouse-Koperasi/automation/warehouse-orchestrator/scripts/dev-supervisor.sh run"

# 4. Wait for HEALTHY, then verify exactly one of each process
cat /srv/warehouse-koperasi/state/dev-health.json
ps aux | grep -E "queue:work|queue:listen" | grep -v grep   # exactly one
ps aux | grep -E "schedule:work|schedule:run" | grep -v grep # exactly one (if applicable)
ps aux | grep "reverb:start" | grep -v grep                  # exactly one

# 5. Verify public health before relying on it
curl -sS -o /dev/null -w '%{http_code}\n' https://wh.stevewithcode.net/
curl -sS -o /dev/null -w '%{http_code}\n' https://wh.stevewithcode.net/health/live
curl -sS -o /dev/null -w '%{http_code}\n' https://wh.stevewithcode.net/health/ready
```

**Restoring Docker afterward (reverse sequence):**

```bash
# 1. Stop legacy deterministically -- kill the supervisor first (no restart
#    loop possible), then the composer-dev process group it owns.
#    (See the tmux/process-group topology recorded in the Phase 6.4F-0
#    cutover evidence if PIDs need to be re-identified.)
tmux kill-session -t warehouse-composer-dev

# 2. Confirm ports released again
sudo ss -ltnp | grep -E ':(8000|8080|5173)\s' || echo "released"

# 3. Restart Docker with the last-known-good digests
cd /srv/warehouse-koperasi/deploy
sudo -u deploy cat current-digest.env   # confirms which digests to use
sudo -u deploy bash -c '
  set -a; source current-digest.env; set +a
  docker compose -f compose.yaml --env-file .env up -d --remove-orphans
'

# 4. Re-verify health exactly as in step 5 above
```

This exact drill (Docker → legacy → Docker) was proven end-to-end during the first VPS Docker cutover — see the cutover evidence in the Phase 6.4F-0 session record.

## Future production target (not active)

Everything below describes the intended production deployment model once managed infrastructure exists (see `docs/PRODUCTION-READINESS.md` for the current blocker list). Do not run this against the development VPS — it assumes a managed PostgreSQL/Redis/object-storage production environment that does not exist yet.

### Pre-deploy

```bash
git fetch origin
git checkout <reviewed-release-commit>
composer install --no-dev --classmap-authoritative
npm ci
npm run build
php artisan ops:validate-production
php artisan ops:verify-production-infrastructure
php artisan migrate --force
php artisan optimize
```

The validator must pass without printing secret values. Use `migrate`, never `migrate:fresh`, for persistent deployments. Review migrations for blocking rewrites and destructive operations before release.

### Required process configuration

- `APP_ENV=production`, `APP_DEBUG=false`, HTTPS, secure HTTP-only SameSite cookies, MFA policy enforced.
- Runtime database user cannot alter schema/roles; migrations use a separate credential.
- Private storage is not publicly served.
- Queue workers, scheduler, Reverb, and web processes are supervised and restart-safe.
- The infrastructure probe is read-only by default: it validates configuration,
  database connectivity, and Redis. Run the private-storage write/read/delete
  probe only in an isolated environment with both
  `--storage-smoke --confirm-storage-smoke`.
- Health checks consume `/health/live` and `/health/ready`.
- Backups, monitoring, alert ownership, and rollback procedure are active.

### Post-deploy smoke

Check login, tenant context, dashboard, Inbox, stock view, Purchase Request view, private evidence authorization, health endpoints, queue freshness, and Reverb connection. Use a synthetic tenant for mutations.

### Rollback

Roll back application code and restart supervised workers/Reverb. Do not automatically roll back a destructive migration. Forward/backward-compatible migrations are required; restore data only through the approved backup/restore runbook.
