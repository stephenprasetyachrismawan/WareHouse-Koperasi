# CD.md — Continuous Delivery / Deployment Contract

```text
DEVELOPMENT CD: ACTIVE (manual promotion)
PRODUCTION CD:  NOT ACTIVE
```

This is the authoritative contract for how built artifacts reach a running environment. If this document and `.github/workflows/ci-cd.yml` / `deploy/*` ever disagree, the scripts are the ground truth for behavior; treat the disagreement as a bug in this document.

## 1. Core rule

```text
GITHUB BUILDS. THE VPS RUNS.
```

The development VPS never builds application artifacts. It pulls exact, digest-pinned images that GitHub already built and tested, and runs them. This was a hard requirement of the first cutover (Phase 6.4F-0 / Section 41) and remains a standing rule for every deployment after it. The VPS must never be used as a normal CI/build machine.

## 2. Architecture

```text
GitHub
  ↓
GitHub-hosted runner
  ↓
quality / integration (PostgreSQL + Redis) — see CI.md
  ↓
immutable Docker image (runtime + web targets)
  ↓
GHCR (ghcr.io/stephenprasetyachrismawan/warehouse-koperasi[-web])
  ↓
GitHub Environment: development
  ↓
SSH deploy (deploy-development.sh, run on the VPS)
  ↓
development VPS Docker runtime
```

Deployment truth is the exact digest published by `image-publish` in the same workflow run that triggers `deploy-development` — never a re-derived or manually chosen digest, and never a mutable tag.

## 3. Normal deployment contract

Development deployment is **manual promotion**, not automatic-on-merge:

```text
workflow_dispatch on ci-cd.yml, with run_deploy=true
  ↓
needs: image-publish (so a deploy always uses a digest from the same run)
  ↓
deploy-development job (environment: development)
  ↓ SSH, using secrets.DEPLOY_HOST/DEPLOY_USER/DEPLOY_SSH_KEY/DEPLOY_KNOWN_HOSTS
deploy-development.sh <app-image@sha256:...> <web-image@sha256:...>
  ↓
flock deployment lock (.deploy.lock — refuses concurrent deploys)
  ↓
record previous digest → previous-digest.env
  ↓
docker compose pull app web (exact digests only; refuses non-digest refs)
  ↓
SQLite backup (throwaway container, sqlite3 .backup) + integrity check (refuses to proceed without a verified backup)
  ↓
php artisan migrate --force (one-shot container)
  ↓
docker compose up -d --remove-orphans (replace containers)
  ↓
/health/live (up to 15 retries × 2s)
  ↓
/health/ready
  ↓
all containers running (docker compose ps)
  ↓
reverb container responsive
  ↓
homepage smoke (GET / → 200)
  ↓
current-digest.env recorded
```

**Never, under any circumstance:**
- `php artisan migrate:fresh` or `migrate:refresh`
- `docker compose down -v`
- `docker volume rm` on `warehouse-koperasi-sqlite` or `warehouse-koperasi-private-storage`
- `docker build` on the VPS as part of a deployment

Any step failing after the backup calls `rollback-development.sh` automatically — see §5.

## 4. Development runtime

The active development runtime (`deploy/compose.yaml`) is five containers, all sharing the same `app`/`web` immutable images built by `image-publish`:

| Service | Image target | Role |
| --- | --- | --- |
| `web` | `web` (nginx) | Public HTTP entrypoint, `127.0.0.1:8000` |
| `app` | `runtime` (php-fpm) | Application process |
| `queue` | `runtime` | `php artisan queue:work` |
| `scheduler` | `runtime` | `php artisan schedule:work` |
| `reverb` | `runtime` | `php artisan reverb:start`, `127.0.0.1:8080` |

**Development persistence is currently:**
- SQLite, in the named Docker volume `warehouse-koperasi-sqlite` (mounted at `storage/database`, never at `database/` — that path ships baked-in migration files in the image and must not be shadowed by a volume mount)
- Private file storage, in the named Docker volume `warehouse-koperasi-private-storage` (mounted at `storage/app/private`)
- Cache, queue, and session: **database-backed** (matching SQLite, not Redis)

Do not describe development as a PostgreSQL/Redis runtime — that is the CI-proven production target (see [CI.md §5](CI.md)), not what's actually running on the VPS today. Migrating the development VPS to PostgreSQL/Redis is a distinct, not-yet-approved future change.

## 5. Reverb networking split

Proven end-to-end during the first cutover, including two real bugs found and fixed along the way (a Cloudflare Tunnel ingress-rule ordering issue, and a `REVERB_ALLOWED_ORIGINS` scheme-prefix bug — see `.env.docker.example` and PR #59).

**Server side** (container-to-container, internal Docker network):
```text
app / queue / scheduler  →  reverb:8080  (REVERB_HOST=reverb, REVERB_SCHEME=http)
```

**Browser side** (public internet):
```text
browser  →  wss://wh.stevewithcode.net/app/{key}  →  Cloudflare Tunnel  →  127.0.0.1:8080  →  Reverb
```

The Cloudflare Tunnel for `wh.stevewithcode.net` is **remotely managed** (token-based) — its ingress rules live in the Cloudflare dashboard/API, not in the local `/etc/cloudflared/config.yml` file on the VPS. Rule order matters: a path-specific rule (`/app`, `/apps` → `localhost:8080`) must be listed **before** the general catch-all hostname rule, or the catch-all shadows it and every WebSocket handshake fails with a 502 at the Cloudflare edge. This was discovered and fixed live during the first cutover.

`REVERB_ALLOWED_ORIGINS` must be **host-only, no scheme** (`wh.stevewithcode.net`, not `https://wh.stevewithcode.net`) — `Laravel\Reverb\Protocols\Pusher\Server::verifyOrigin()` compares against `parse_url($originHeader, PHP_URL_HOST)`, which never includes a scheme.

**Secrets:**
- The public app key (`REVERB_APP_KEY` / `VITE_REVERB_APP_KEY`) is browser-facing by design and is baked into the frontend build via a Docker build ARG (`vars.REVERB_APP_KEY`, a GitHub Actions *variable*, not a secret — it's public).
- `REVERB_APP_SECRET` is server-only. It must never be compiled into VITE assets, never passed as a Docker build ARG, and never appear in a build log.

## 6. Rollback

There are two distinct rollback classes. Do not conflate them.

### A. First-cutover legacy fallback (historical, retired)
Before the first Docker deployment, no previous Docker digest existed to roll back to. The proven fallback at the time was the pre-Docker legacy `composer dev` runtime. This was exercised as a full drill during the first cutover: Docker stopped → legacy restarted (`.env`/`APP_KEY` confirmed untouched) → served real traffic and real data → legacy stopped deterministically → Docker restarted and reverified. That drill was never part of the normal deployment flow, and the legacy runtime and its supervising tooling have since been fully removed from this repository — this subsection is kept only as a historical record of the first cutover, not a currently available fallback.

### B. Normal Docker-to-Docker rollback
`deploy/rollback-development.sh` — invoked automatically by `deploy-development.sh` on any post-backup failure, or manually with a reason string:

```text
read previous-digest.env
  ↓
docker compose up -d (previous app/web digests)
  ↓
/health/live, /health/ready (up to 15 retries × 2s)
  ↓
report honestly
```

Honest status wording is a hard requirement and is enforced in the script's own log line:

```text
DEPLOYMENT FAILED — ROLLBACK SUCCEEDED
```

A failed deployment that gets rolled back is **never** reported as a successful deployment, even though the system ends up healthy. If the rollback itself fails, the script says `ROLLBACK FAILED` and stops — it never assumes health it hasn't verified.

## 7. Database rollback limitation

**Image rollback does not reverse a database schema migration.** Rolling back to a previous image digest restores old application code; if that code runs against a schema that a since-rolled-back migration already changed, the combination may itself fail health checks — and the script reports that failure honestly rather than pretending success.

Consequences:
- Backward-compatible migrations (additive, nullable-first, expand/contract) are strongly preferred.
- A destructive or backward-incompatible schema migration needs its own explicit rollout/rollback design before it ships — Docker image rollback is not a substitute for that design.
- `deploy-development.sh` takes and integrity-checks a fresh SQLite backup immediately before every `migrate --force`, specifically because image rollback cannot undo the migration itself.

## 8. Deployment evidence

Every deployment report should include:

- workflow run (link or run ID)
- source commit SHA
- image digests (app + web, `sha256:...`)
- GitHub Environment (`development`)
- migration result (`Nothing to migrate` / migration count — never silently omitted)
- health result (`/health/live`, `/health/ready`)
- smoke result (homepage 200)
- rollback result, where applicable

**No secret values, ever** — not `APP_KEY`, not `REVERB_APP_SECRET`, not SSH keys, not GHCR credentials. Evidence reports state *that* a secret exists/was rotated/matches, never the value.

## 9. Manual promotion gate — current intentional policy

Development deployment is deliberately gated behind `workflow_dispatch` with `run_deploy=true`. It does **not** run automatically on every merge to `main`. This is intentional, current policy — not a temporary placeholder to remove reflexively. The gate's inline comment in `ci-cd.yml` was corrected in Phase 6.4F-0B to say exactly this, replacing an earlier comment that misleadingly implied the gate should be removed once VPS provisioning was done. Automatic deploy-on-merge for development may be considered later; it is not approved as of this document.

## 10. Production

Production CD does not exist yet: no production GitHub Environment, no production deployment path, no PostgreSQL/Redis/S3 migration for development, no production auto-CD. See `docs/rekayasa-operasional/production-readiness.md` for the full, itemized list of what's still blocked before any production discussion is in scope.
