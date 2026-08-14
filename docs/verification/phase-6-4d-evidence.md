# Phase 6.4D Verification Evidence

Date: 2026-08-14 UTC
Branch: `hardening/production-environment-verification`

## Verification status vocabulary

- `VERIFIED`: executed against the named environment and evidence is recorded.
- `IMPLEMENTED`: code/documentation exists but execution is not yet proof.
- `NOT VERIFIED`: the required target or tool was unavailable.
- `BLOCKED`: a mandatory gate cannot close because infrastructure or approval is missing.
- `FAIL`: executable verification found a broken critical control.

## Security and supply chain

| Control | Result | Evidence |
|---|---|---|
| Security/tenant regression | VERIFIED | PostgreSQL + Redis compatibility suite: 450 passed / 1,013 assertions; SQLite full suite: 606 passed / 1,458 assertions |
| Production validator | VERIFIED | Focused tests: 4 passed / 12 assertions; rejects local Vite/Reverb endpoints, wildcard origins, non-TLS Reverb, and local private storage driver |
| Composer audit | VERIFIED | `composer audit`: no security vulnerability advisories |
| npm audit | VERIFIED | `npm audit --audit-level=high`: 0 vulnerabilities after lockfile update to `nanoid` 3.3.18 |
| Secret scan | VERIFIED | Gitleaks v8.30.1, checksum verified from official release, repository/history scan passed using exact reviewed historical documentation allowlist |
| Static analysis | BLOCKED | `composer test` fails because `phpstan analyse` exceeds the 300-second Composer timeout; no broad suppression/baseline was added |
| Formatting | VERIFIED | `vendor/bin/pint --test` passed |

The only historical Gitleaks finding was a fake documentation example in commit `e445fc051cf6e08448ac9f8c4fc53e3c07244234`, path `.claude/skills/laravel-best-practices/rules/config.md`. The current example contains `<external-secret-reference>`. The exact commit/path decision is recorded in `.gitleaks.toml` and `docs/verification/ci-gates.md`; no active credential was found.

## Backup and restore

| Control | Result | Evidence |
|---|---|---|
| Local synthetic backup | VERIFIED | Existing SQLite/local-private drill created an archive and printed an explicit local-only warning |
| Local synthetic restore | VERIFIED | Isolated restore reported `RESTORE VERIFIED`, SQLite `integrity_check=ok`, row and private evidence marker restored |
| PostgreSQL backup | BLOCKED | No managed backup/PITR provider or approved encrypted PostgreSQL backup target is configured |
| Private object backup | BLOCKED | No S3-compatible private bucket/versioning/replication target is available |
| Backup encryption | BLOCKED | Production encryption/key-management evidence is unavailable |
| Backup failure alert | BLOCKED | No connected production monitoring/alert target is available |
| Full application restore | BLOCKED | PostgreSQL plus private object restore into an isolated application instance cannot be executed on this host |

The local drill is not counted as production recovery evidence.

## Load baseline

An ApacheBench read-only probe against the local development Laravel health endpoint was run with 200 requests and concurrency 10:

| Metric | Result |
|---|---:|
| Failed requests | 0 |
| Requests/sec | 37.55 |
| Mean total time | 256 ms |
| p50 | 242 ms |
| p95 | 409 ms |
| p99 | 440 ms |

This is a local development engineering baseline only. It is not a production SLO, capacity test, or evidence for authenticated dashboards, mixed mutations, exports, Reverb connections, or worker saturation. No approved isolated load environment/tool for those profiles is available; the production-like capacity gate remains `BLOCKED`.

## Public/browser evidence

| Probe | Result |
|---|---|
| `https://wh.stevewithcode.net/` | VERIFIED HTTP 200; Laravel page and built/Vite assets loaded |
| `https://wh.stevewithcode.net/health/live` | VERIFIED HTTP 200 |
| `https://wh.stevewithcode.net/health/ready` | VERIFIED HTTP 200 |
| `https://vite-warehouse.stevewithcode.net/@vite/client` | VERIFIED HTTP 200; asset origin works |
| `https://vite-warehouse.stevewithcode.net/` | NOT VERIFIED as an application page; Vite root returns expected 404/landing response |
| `https://wh.stevewithcode.com/` | BLOCKED; DNS resolution failed |

The public `.net` browser run previously showed CSP warnings for development-only Boost logging/Cloudflare Insights and a browser Reverb target of `localhost:8080`. The homepage was not blank and assets loaded, but public production Reverb/CSP configuration is not closed.

## RPO/RTO and ownership

Proposed engineering targets remain RPO 15 minutes and RTO 60 minutes. No authorised operations sign-off or measured managed restore exists, so both gates are `BLOCKED`. Named production operational ownership and contact escalation are also not provisioned in this repository environment.

## Current classification

```text
PRODUCTION READINESS: BLOCKED
```

Phase 7 Machine Learning remains locked.
