# ADR 0011 — Managed Production Environment Preparation

## Status

Accepted for Phase 6.4E preparation; managed provider provisioning remains externally blocked.

## Context

Phase 6.4D verified Laravel compatibility against disposable PostgreSQL and Redis, but the host has no usable managed-provider credential, private object-storage target, production secret manager, or monitoring destination. The active Cloudflare Tunnel exposes loopback Laravel/Vite origins; it is not evidence of a managed production topology.

The application architecture requires PostgreSQL, Redis, private S3-compatible storage, supervised web/queue/scheduler/Reverb processes, external secrets, encrypted backups, and a canonical HTTPS origin. Phase 6.4E must prepare and validate those boundaries without inventing a provider or claiming a resource exists.

## Decision

Use a provider-neutral production contract until the SaaS owner supplies an approved provider and scoped credentials. The repository will provide:

- a secret-free production configuration validator;
- a non-destructive infrastructure smoke command for DB, Redis, and synthetic private-storage probes;
- an explicit environment variable contract in `.env.example` and deployment documentation;
- an infrastructure inventory with `IMPLEMENTED`, `VERIFIED`, `NOT VERIFIED`, and `BLOCKED` status;
- an external-action table that identifies the resource, required permission, configuration, and evidence needed to close each gate.

The first provider-specific implementation must map the same contract to managed PostgreSQL with TLS and separate migration/runtime identities, managed Redis with TLS/authentication, private encrypted object storage with recovery protection, external secret management, supervised processes, canonical DNS/TLS, and connected monitoring. It must be reviewed as a separate provider decision before credentials are used.

## Production contract

The production validator will require, without printing values:

```text
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<canonical-host>
DB_CONNECTION=pgsql
DB_SSLMODE=require|verify-full
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
FILESYSTEM_DISK=s3-compatible-private-disk
PRIVATE_STORAGE_DRIVER=s3
REVERB_SCHEME=https
REVERB_HOST=<public-wss-host>
REVERB_ALLOWED_ORIGINS=<exact-https-origin>
VITE_DEV_SERVER_ORIGIN unset
```

The exact provider credentials remain external secrets. `APP_KEY`, OAuth credentials, Redis credentials, Reverb credentials, object-storage credentials, and provider monitoring credentials must never enter Git.

## Smoke command boundary

`php artisan ops:verify-production-infrastructure` performs safe connectivity checks against the configured environment:

1. runs the production configuration validator;
2. executes a read-only database ping;
3. pings the configured Redis connection;
4. optionally writes and deletes a uniquely prefixed synthetic object on the private disk only when the operator passes an explicit confirmation option;
5. reports component status without host passwords, tokens, URLs containing secrets, or payloads.

It does not migrate, seed, mutate business records, send notifications, publish Reverb events, or perform a restore. Phase 6.4F remains responsible for restore, load, browser, RPO/RTO, and final readiness certification.

## Rejected alternatives

- **AWS-specific provisioning now:** rejected because no AWS credential is available and VPC/RDS/Redis/S3/Secrets Manager ownership is not established.
- **Local Docker/MinIO emulation:** rejected as a substitute for managed acceptance; it can be added later as a disposable test harness but cannot close Phase 6.4E managed gates.
- **Cloudflare Tunnel as production:** rejected because tunnel ingress alone does not provide managed database, Redis, private storage, process supervision, backups, or alerting.

## Consequences

The repository can become deployment-ready and provide repeatable checks without making false infrastructure claims. Managed PostgreSQL, Redis, object storage, DNS/TLS, secret management, backup monitoring, and operational ownership remain `EXTERNAL ACTION REQUIRED` until the provider owner supplies access and evidence. Readiness remains `BLOCKED`, and Phase 7 remains locked.
