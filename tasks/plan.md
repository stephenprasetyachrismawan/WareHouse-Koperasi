# Implementation Plan: Phase 6.3 Role Dashboards & Operational Reporting

## Overview

Continue the existing `feat/role-dashboards-reporting` worktree from the completed Slice A–D dashboard foundation. Finish Koperasi and administrative dashboards, then add constrained operational reports/CSV exports and the required realtime, security, performance, browser, and git verification.

## Architecture decisions

- Reuse existing tenant-aware domain Query Objects and status helpers; do not duplicate allocation, critical-stock, inbox, or lifecycle rules.
- Keep `/dashboard` as the stable entry point and enforce permissions server-side before composing widgets.
- Keep Koperasi reads owned-data-only and omit internal stock, supplier, PO, and QC detail.
- Use a small typed report/filter/export layer rather than a generic report builder.
- Store exports privately and audit export metadata without row contents or sensitive paths.
- Do not add ML, financial reports, broad cache, or a second realtime stack.

## Task list

### Existing baseline

- [x] Slice A: dashboard route, role resolution, metric/read-model foundation.
- [x] Slice B: Staff Admin dashboard and FR-38 query.
- [x] Slice C: Kepala Gudang dashboard.
- [x] Slice D: Purchasing dashboard.

### Slice E — Koperasi dashboard

- [x] Add own ready-pickup, latest request, latest return, replacement-ready, and unread-inbox reads.
- [x] Add safe CTAs and meaningful empty states.
- [x] Add current-warehouse, current-user, cross-user, cross-warehouse, and sensitive-field tests.

### Slice F — Administrative dashboards

- [x] Add App Admin tenant administration metrics with explicit permission gating.
- [x] Add Super Admin platform-only metrics without tenant business aggregation.
- [x] Test inactive membership, permission narrowing, tenant denial, and platform boundary.

### Slice G — Operational reports

- [x] Define typed report filters/rows and warehouse-local UTC date boundaries.
- [x] Implement stock, movement, purchase request, PO/receipt, pickup, return, and QC reports only where existing models support them.
- [x] Add paginated report UI, role matrix, deterministic ordering, and tenant/permission tests.

### Slice H — CSV exports

- [x] Add validated export authorization and filter-preserving CSV generation.
- [x] Store files privately with safe/random object names and human download names.
- [x] Add audit metadata, download policy, tenant isolation, and retry/double-submit guards.

### Slice I — Hardening and release

- [x] Reuse Phase 6.2 realtime invalidation and add safe dashboard refresh behavior.
- [x] Profile representative dashboards/reports, add only justified indexes, and detect obvious N+1 queries.
- [ ] Run authorization/tenant/security suites and real browser responsive checks (browser MCP unavailable in this environment).
- [ ] Run full gates, review, commit, push, create PR, and merge through the required workflow.

## Checkpoints

- After Slice E/F: focused dashboard tests, Pint, and full regression.
- After Slice G/H: report/export authorization and privacy tests, build, and static analysis.
- Before merge: full tests, Pint, static analysis, npm build, migration safety, browser checks, and `composer run dev`.

## Risks

| Risk | Mitigation |
|---|---|
| Existing ACL gives App Admin all permissions by default | Gate operational widgets by explicit membership permissions and document the pre-existing template risk. |
| Koperasi ownership differs between pickup and return models | Scope through authenticated user and warehouse, then assert both relationships in tests. |
| Export implementation leaks data through files or filters | Validate filters server-side, use private storage, audit metadata only, and policy-gate download. |
| Dashboard query count grows with widgets | Use aggregate queries, eager loading, pagination, and a representative query-count test. |
