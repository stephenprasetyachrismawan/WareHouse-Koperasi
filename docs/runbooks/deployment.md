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
