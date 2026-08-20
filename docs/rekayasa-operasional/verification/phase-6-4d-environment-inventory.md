# Phase 6.4D Environment Inventory

Date: 2026-08-13 UTC
Branch: `hardening/production-environment-verification`
Baseline `origin/main`: `f3a99e7f85b6b5b7a8bd9c56f5349ef5ae1b70c3`

This inventory is evidence of the environment available to the verification run. It is not a claim that this host is a managed production or staging environment.

## Application and ingress

| Check | Result | Evidence |
|---|---|---|
| Laravel/PHP | IMPLEMENTED/VERIFIED | Laravel 13.24.0, PHP 8.4.24 |
| Current runtime | VERIFIED | `php artisan about`: local environment, debug enabled |
| Cloudflare Tunnel service | VERIFIED | `systemctl is-active cloudflared` = active; enabled at boot |
| Laravel origin | VERIFIED | Cloudflare ingress maps `wh.stevewithcode.net` to loopback Laravel port 8000 |
| Vite origin | VERIFIED | Cloudflare ingress maps `vite-warehouse.stevewithcode.net` to loopback port 5173 |
| Reverb origin | NOT VERIFIED | Local Reverb listens on port 8080; public browser configuration must be checked separately |

No tunnel credentials or environment secret values are included in this document.

## Tooling and services

The host initially lacked PostgreSQL, Redis, and approved secret/load scanner binaries. A disposable compatibility lane was then provisioned with the distribution packages below:

| Component | Result | Version/evidence |
|---|---|---|
| PostgreSQL server/client | AVAILABLE | PostgreSQL 16.14 Amazon Linux packages; `psql`, `pg_isready`, `initdb`, `pg_ctl` available |
| Redis server/client | AVAILABLE | Redis 6.2.20 package; binaries are named `redis6-server` and `redis6-cli` |
| PHP PostgreSQL driver | AVAILABLE | `pdo_pgsql`, `pgsql` loaded |
| PHP Redis extension | AVAILABLE | `redis` loaded, package 6.2.0 |
| Docker/Podman | UNAVAILABLE | No executable found during probe |
| MinIO/S3-compatible server | NOT VERIFIED | No local binary/package found |
| Gitleaks/TruffleHog | NOT VERIFIED | No approved scanner executable found during probe |
| k6/wrk | NOT VERIFIED | No approved load generator found; ApacheBench is available |

System PostgreSQL/Redis services were not enabled for this evidence. Disposable services must bind to loopback and use temporary isolated data directories only.

## Public probes

| URL | Result | Evidence |
|---|---|---|
| `https://wh.stevewithcode.net/` | VERIFIED | HTTP 200; security headers present; Laravel page served |
| `https://wh.stevewithcode.net/health/live` | VERIFIED | HTTP 200 |
| `https://wh.stevewithcode.net/health/ready` | VERIFIED | HTTP 200 |
| `https://vite-warehouse.stevewithcode.net/@vite/client` | VERIFIED | HTTP 200; Vite client asset served |
| `https://vite-warehouse.stevewithcode.net/` | NOT VERIFIED as app page | HTTP 404 is expected for the Vite dev server root; it is an asset origin, not the Laravel application |
| `https://wh.stevewithcode.com/` | BLOCKED | DNS resolution failed during the probe |

Cookie values and response bodies containing session material were intentionally excluded from this record.

## Gate interpretation

The local PostgreSQL/Redis lane can provide compatibility evidence. It cannot close the managed production gates for private object storage, encrypted automated backups, backup failure alerting, PITR/RPO, RTO approval, or public `.com` DNS ownership. Those remain separate gates until executable evidence is available.
