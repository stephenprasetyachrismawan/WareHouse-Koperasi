# Phase 6.3 Role Dashboards & Operational Reporting

## Scope

Turn existing transactional domain facts into tenant-safe read models, role-aware dashboards, filtered operational reports, and policy-protected CSV exports. Machine learning, financial analytics, arbitrary report builders, and cross-tenant business analytics remain out of scope.

## Architecture

- `/dashboard` remains the stable entry point and resolves the active membership or platform context server-side.
- Each dashboard role uses a focused Query Object and a small Blade/Livewire read model.
- All tenant queries receive an explicit `warehouse_id`; Koperasi queries additionally scope ownership to the authenticated user/membership.
- Existing domain Actions, Policies, Inbox queries, allocation queries, and status enums remain authoritative.
- Dashboard drill-down links reuse existing policy-protected routes; query-string filters are never authorization.
- Reports use typed filter DTOs, report row DTOs, deterministic ordering, warehouse-local date boundaries converted to UTC, and server-side pagination.
- CSV exports use a validated filter payload, private tenant-prefixed storage, audit metadata only, and policy-gated downloads.
- Realtime refresh reuses Phase 6.2 private channels/events and reloads authorised data; no sensitive KPI payload is broadcast.

## Role boundaries

- Kepala Gudang: warehouse-wide operational attention and approvals.
- Staff Admin: pickup/QC/return/stock work and FR-38 in-progress purchase quantities.
- Purchasing: approved procurement, grouping, PO, and receipt work.
- Koperasi: own pickup/request/return/replacement/inbox data only.
- App Admin: warehouse administration; operational widgets require explicit permissions.
- Super Admin: platform-scoped health/tenant administration only, never default tenant business detail.

## Verification strategy

Each slice follows red-green-refactor with focused tenant/permission tests, then broader regression, Pint, static analysis, asset build, browser verification, and `composer run dev`. No completion claim is made without fresh command output.
