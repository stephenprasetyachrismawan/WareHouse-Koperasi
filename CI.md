# CI.md — Continuous Integration Contract

```text
STATUS: ACTIVE
```

This is the authoritative contract for continuous integration in this repository. It reflects what GitHub Actions actually runs today, not an aspirational future state. If this document and the workflow files under `.github/workflows/` ever disagree, the workflow files are the ground truth for behavior — but the disagreement itself is a bug in this document and should be fixed in the same PR that's touched last.

## 1. What CI answers

CI answers exactly one question:

> **Is this change safe to merge?**

It does not answer "is this deployed" (see [CD.md](CD.md)) and it does not answer "is this production-ready" (see `docs/PRODUCTION-READINESS.md`).

## 2. The merge lifecycle

```text
dedicated branch
  ↓
RED test where the change is behavioral (see .agent/WORKFLOW.md)
  ↓
implementation
  ↓
local verification (Pint, PHPStan, tests, npm run build)
  ↓
push
  ↓
Pull Request
  ↓
GitHub Actions
  ↓
ALL REQUIRED CHECKS GREEN
  ↓
merge
```

**LOCAL PASS DOES NOT AUTHORIZE MERGE.** A green run on a developer's machine or VPS proves nothing about what GitHub's runners see — different PHP/Node versions, different service availability, different filesystem/timing behavior, and (historically, in this repository) different bugs that only reproduce on GitHub's infrastructure (see the SSH-key trailing-newline bug in [CD.md §5](CD.md)). GitHub Actions is the authoritative merge gate. Every completion report in this repository states the actual workflow run ID and job results, not just "tests passed locally."

## 3. Current real jobs

One workflow file is active in `.github/workflows/`: `ci-cd.yml`. (An earlier, overlapping `tests.yml` existed alongside it briefly during the Docker/CI-CD buildout; it was a strict subset of `quality` below — same setup, same `composer test`, same Gitleaks scan, just unpinned action refs and no PostgreSQL/Redis/container coverage — and was consolidated away in Phase 6.4F-0B with zero coverage loss.)

| Job | Runs on | Responsibility |
| --- | --- | --- |
| `quality` | PR + push to main | Security regression suite, dependency audits, Pint, PHPStan level 7, full test suite, Gitleaks — SQLite fast lane |
| `integration (PostgreSQL + Redis)` | PR + push to main | Migrates and runs the full suite against ephemeral PostgreSQL 16; separately exercises a real Redis cache/queue round-trip |
| `image build (no publish)` | PR + push to main | Builds both Dockerfile targets (`runtime`, `web`) to prove they build; never pushes |
| `image publish (GHCR)` | push to `main` only | Rebuilds (cheaply, via BuildKit cache) and pushes both images to GHCR with immutable digests |
| `deploy-development` | `workflow_dispatch` with `run_deploy=true` only | SSHes into the development VPS and runs the already-published images; not part of PR CI |

Every third-party action in `ci-cd.yml` is pinned to a full commit SHA (with a version comment alongside it) — this is current, standing policy, not a one-time cleanup. A PR that reintroduces a floating tag (`@v4`, `@main`, etc.) for a third-party action should be treated as a regression.

Deployment is deliberately **not** part of pull-request CI. `deploy-development` only runs when a human explicitly triggers `workflow_dispatch` with `run_deploy=true` — see [CD.md](CD.md) for why.

## 4. Quality contract

The `quality` job runs, in order:

```bash
composer install --no-interaction --prefer-dist --no-progress
npm ci && npm run build
php artisan test tests/Feature/Security   # security regression suite, run in isolation first
composer audit
npm audit --audit-level=high
composer test                              # Pint --test, PHPStan level 7, full test suite
# Gitleaks secret scan (gitleaks/gitleaks-action)
```

This must keep passing with:

- **Pint**: formatting, zero diffs.
- **PHPStan / Larastan level 7: zero errors.** This was closed 411 → 0 in Phase 6.4F-1 (PR #54) through real bug fixes, not suppression. It stays at zero.
- **Laravel test suite**: full pass, not a subset, on every PR.
- **Security regression suite**: `tests/Feature/Security` passes standalone before the rest of the suite runs.
- **`npm run build`**: Vite build succeeds.
- **`composer audit` / `npm audit --audit-level=high`**: no advisories.
- **Gitleaks**: no secrets detected.

**Not permitted, ever, merely to produce a green badge:**
- a PHPStan baseline file
- `ignoreErrors` entries added to silence real findings
- lowering the PHPStan level below 7
- `continue-on-error: true` on any quality step
- removing or skipping security regression tests
- disabling Gitleaks

If a quality gate is genuinely wrong (a real false positive), fix it narrowly and document why in the same PR — don't route around it at the CI-configuration level.

## 5. Integration contract (PostgreSQL + Redis)

The `integration` job runs the full test suite against ephemeral **PostgreSQL 16** and separately exercises a real **Redis 7** cache/queue round-trip (`tests/Feature/Infrastructure/RedisCompatibilityTest.php`), even though the current **development VPS runtime uses SQLite, database-backed queue, database-backed cache, and database-backed sessions** (see [CD.md §4](CD.md)).

This is intentional and not a mismatch to "fix":

- [`ARCHITECTURE.md` §4](ARCHITECTURE.md) defines PostgreSQL + Redis as the target production architecture.
- CI proves the codebase stays compatible with that target continuously, so migrating the VPS runtime later is a configuration change, not a scramble to fix accumulated PostgreSQL/Redis-incompatible code.
- The development VPS does not need PostgreSQL/Redis containers to get this assurance — CI already proves it on every PR, against disposable, ephemeral services.

The Redis compatibility test is deliberately isolated from the rest of the suite (see the comment in `RedisCompatibilityTest.php` and in `ci-cd.yml`): most of the suite relies on `phpunit.xml`'s synchronous queue/cache/session defaults to assert job side effects immediately, an assumption a real async Redis queue doesn't share. Forcing the whole suite onto Redis would break that unrelated assumption without proving anything extra.

## 6. Container contract

**On a pull request:**
- The Docker image (both `runtime` and `web` targets) **must build**. It must **not** deploy, and it must **not** require GHCR write privileges — `image-build` runs with `permissions: contents: read` only and never authenticates to GHCR.

**On trusted `main`** (push, not PR):
- `image-publish` may build and push immutable deployment artifacts to GHCR, using `permissions: packages: write` scoped to that job alone.

**Image identity is always traceable:**

```text
git commit (main)
  → GitHub Actions run
  → GHCR image (ghcr.io/stephenprasetyachrismawan/warehouse-koperasi[-web])
  → OCI digest (sha256:...)
```

Deployment truth is always a digest — `sha256:<digest>` — never a mutable tag like `latest` or `development`. The `development` tag exists for human browsing convenience only; `deploy-development.sh` refuses any image reference that doesn't contain `@sha256:`.

## 7. Failure rule

When a required GitHub Actions check goes red:

```text
inspect the failed job (gh run view --job --log-failed)
  ↓
inspect logs — do not guess
  ↓
identify the actual root cause
  ↓
fix the source
  ↓
push
  ↓
wait for CI again
```

**Never, to make a check pass:**
- disable the failing job or step
- weaken the failing assertion
- lower the PHPStan level or add an ignore
- add `continue-on-error: true`
- merge while a required check is still pending or red

This exact rule was followed live during the first VPS Docker cutover: the `deploy-development` job failed with `Load key ...: error in libcrypto`. The fix was to find the real root cause (GitHub strips the trailing newline from stored secrets; OpenSSH's key parser requires one) and correct it in the workflow source (`printf '%s'` → `printf '%s\n'`, PR #58) — not to retry blindly or bypass the SSH step.

## 8. Completion evidence

Every future coding task should report, at minimum:

- branch name
- commit SHA
- PR number
- workflow run (link or run ID)
- `quality` result
- `integration` result
- PHPStan result (must be zero errors)
- test result (pass count)
- build result (`npm run build`)
- security result (security regression suite + Gitleaks)
- remaining risks, if any

When a Docker artifact is produced, also report:

- image tag(s)
- image digest(s) (`sha256:...`)

Do not report a task as complete on the strength of local command output alone — see §2.
