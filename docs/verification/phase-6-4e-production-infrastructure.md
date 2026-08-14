# Phase 6.4E Production Infrastructure Inventory

Date: 2026-08-14 UTC
Baseline `main`: `1fa16c9ef8459a89a04ef5bac7506b18578b0348`
Readiness before Phase 6.4E: `PRODUCTION READINESS: BLOCKED`

This is a provider-neutral preparation record. No managed resource is claimed unless a provider connection and executable evidence are recorded.

## Current host evidence

| Component | Status | Evidence |
|---|---|---|
| Laravel application | VERIFIED | Existing Laravel 13.24.0 application and Phase 6.4D regression evidence |
| Cloudflare Tunnel client | VERIFIED | `cloudflared.service` is active and maps `.net` Laravel/Vite origins to loopback |
| Managed PostgreSQL | BLOCKED | AWS CLI is present but `aws sts get-caller-identity` cannot authenticate; no approved managed DB provider is configured |
| Managed Redis | BLOCKED | No provider credential or approved managed Redis endpoint is available |
| Private object storage | BLOCKED | No approved S3-compatible bucket/provider or storage credentials are available |
| Secret management | BLOCKED | No provider secret-manager credential or configured production secret store is available |
| Monitoring/alert destination | BLOCKED | No connected production monitoring or alert destination is available |
| Production process supervision | NOT VERIFIED | Current host runs local development orchestration; no approved production topology is configured |
| Canonical `.com` DNS | BLOCKED | `wh.stevewithcode.com` remains unresolved; `.net` is the currently reachable tunnel hostname |

## Repository preparation status

| Capability | Status | Evidence/next verification |
|---|---|---|
| Production configuration contract | IMPLEMENTED | ADR 0011 and `ops:validate-production` checks |
| Infrastructure smoke command | IMPLEMENTED | `ops:verify-production-infrastructure`; default mode is read-only |
| Synthetic private-storage probe | IMPLEMENTED | Requires explicit confirmation and must run only against an approved target |
| Provider-specific provisioning | NOT VERIFIED | Requires provider choice, account access, network plan, and scoped credentials |
| PostgreSQL migration on managed target | BLOCKED | Run `php artisan migrate --force` after managed staging exists |
| Runtime/migration DB least privilege | BLOCKED | Create and test separate provider identities |
| Redis queue/cache/session on managed target | BLOCKED | Run smoke command with production Redis configuration |
| Private object upload/retrieval | BLOCKED | Run synthetic QC/return object probe on private bucket |
| Reverb public WSS | BLOCKED | Configure supervised ingress and browser `wss://` endpoint |
| Encrypted backup/PITR | BLOCKED | Configure provider backup and retention; certify in Phase 6.4F |

## External action required

| Resource | Required action | Permission needed | Evidence required |
|---|---|---|---|
| Managed PostgreSQL | Provision compatible PostgreSQL with private networking/TLS and two users | Provider DB/network administration | Host/version/TLS probe, migration output, runtime privilege-denial probe |
| Managed Redis | Provision authenticated TLS Redis for queue/cache/session/locks | Provider cache/network administration | `REDIS` connection smoke, queue worker and lock evidence |
| Private object storage | Create private encrypted bucket with versioning/recovery protection | Storage administration | Anonymous denial, synthetic put/get/delete, encryption/versioning evidence |
| Secret manager | Store APP/DB/Redis/Reverb/storage/OAuth secrets externally | Secret-manager administration | Secret references/config injection evidence without values |
| Canonical DNS/TLS | Resolve approved canonical app and Reverb hosts | DNS/Cloudflare administration | Independent DNS, HTTPS, WSS, and certificate probes |
| Process supervision | Run web, workers, scheduler, and Reverb under managed/system supervisor | Host/platform administration | Restart/reboot/deploy lifecycle evidence |
| Backup/monitoring | Configure encrypted DB backup/PITR, object recovery, and failure alerts | DB/storage/observability administration | Backup job, alert delivery, retention, and Phase 6.4F restore evidence |
| RPO/RTO | Approve proposed RPO 15m/RTO 60m or provide alternatives | Product/operations owner | Signed approval with owner/date/reference |

## Phase boundary

This phase does not declare production readiness. Phase 6.4F must still execute managed PostgreSQL/object restore, load/capacity, RPO/RTO validation, static-analysis closure, full authenticated browser verification, and final readiness sign-off. Phase 7 Machine Learning remains locked.
