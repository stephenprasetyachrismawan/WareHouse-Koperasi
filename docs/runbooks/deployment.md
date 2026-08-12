# Deployment Runbook

## Pre-deploy

```bash
git fetch origin
git checkout <reviewed-release-commit>
composer install --no-dev --classmap-authoritative
npm ci
npm run build
php artisan ops:validate-production
php artisan migrate --force
php artisan optimize
```

The validator must pass without printing secret values. Use `migrate`, never `migrate:fresh`, for persistent deployments. Review migrations for blocking rewrites and destructive operations before release.

## Required process configuration

- `APP_ENV=production`, `APP_DEBUG=false`, HTTPS, secure HTTP-only SameSite cookies, MFA policy enforced.
- Runtime database user cannot alter schema/roles; migrations use a separate credential.
- Private storage is not publicly served.
- Queue workers, scheduler, Reverb, and web processes are supervised and restart-safe.
- Health checks consume `/health/live` and `/health/ready`.
- Backups, monitoring, alert ownership, and rollback procedure are active.

## Post-deploy smoke

Check login, tenant context, dashboard, Inbox, stock view, Purchase Request view, private evidence authorization, health endpoints, queue freshness, and Reverb connection. Use a synthetic tenant for mutations.

## Rollback

Roll back application code and restart supervised workers/Reverb. Do not automatically roll back a destructive migration. Forward/backward-compatible migrations are required; restore data only through the approved backup/restore runbook.
