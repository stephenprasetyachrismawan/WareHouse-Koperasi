# Warehouse Koperasi Project Guidelines

These rules supplement Laravel Boost guidelines.

- Read `PRD.md`, `BATASAN.md`, `SECURITY-RULES.md`, `ARCHITECTURE.md`, `UI-RULES.md`, `CI.md`, and `CD.md` before implementation.
- `CI.md` governs the merge gate (what GitHub Actions must pass before merge); `CD.md` governs how the development environment is actually deployed. Do not assume a different CI/CD process — read the current documents.
- Treat `Warehouse` as the tenant. All operational data, queries, jobs, cache keys, broadcasts, files, exports, and notifications must be tenant-aware.
- Use Laravel Policies/Gates for model-level authorisation. Do not rely on hidden UI or role middleware alone.
- Keep controllers and Livewire page actions thin. Use Form Requests, explicit Actions/Services, and Query Objects.
- Do not provide a universal `super_admin` bypass. Platform access and tenant support access are explicit, time-boxed, MFA-protected, and audited.
- Use append-only stock transactions, atomic balance updates, idempotency, and optimistic concurrency.
- Status transitions occur only through named actions and validated state machines.
- Approval decisions are immutable. Rejections require reasons. Direct prediction purchase requests use `AUTO_APPROVED` audit records.
- Store QC and return evidence privately. Authorise every upload, view, and download.
- Dispatch external side effects after commit using outbox/jobs.
- Public registration is disabled. Authentication uses Google Sign-In plus mandatory MFA and invitation/membership checks.
- Write allow/deny and cross-tenant tests for every sensitive feature.
- The Python ML service is the final phase, feature-flagged off by default, and never receives direct database access.
- Use Laravel factories and tenant-safe states for test/demo data. Never use production data in local environments.
