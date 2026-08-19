<!--
Checkboxes below are reminders for the author, not proof. GitHub Actions
(see CI.md) is the authoritative merge gate regardless of what's checked here.
-->

## What / why

<!-- Requirement or ticket ID, and why this change is needed. -->

## Affected tenant / role / workflow

<!-- Which warehouse-scoped data, role, or workflow does this touch? -->

## Schema / migration impact

- [ ] No schema change
- [ ] Additive/backward-compatible migration
- [ ] Backward-incompatible migration (explain rollout/rollback plan below — see CD.md §7)

## Security impact

- [ ] No security-relevant change
- [ ] Touches authN/authZ, tenant resolution, stock, approvals, file access, impersonation, or ML integration (requires explicit security review — see AGENTS.md §9)

## Local verification

- [ ] `php artisan test`
- [ ] `vendor/bin/pint --test`
- [ ] Static analysis (PHPStan level 7 — zero errors)
- [ ] `npm run build`

## GitHub CI

- [ ] Pushed and waiting for required checks (`quality`, `integration (PostgreSQL + Redis)`, `image build (no publish)` where applicable) — see CI.md
- [ ] Do not merge until required checks are green

## Rollout / rollback

<!-- How does this ship? What undoes it if it's wrong? See CD.md §6-7 for the rollback model and its database-migration limitation. -->

## Deployment impact

- [ ] No deployment-relevant change
- [ ] Touches `.github/workflows/**`, `Dockerfile`, `deploy/**`, `compose*.yaml`, health endpoints, registry strategy, the GitHub Environment contract, deployment transport, or rollback flow — **CI.md and/or CD.md reviewed and updated in this PR** (see AGENTS.md §12)

## Unresolved risks

<!-- Anything left open, or state "none". -->
