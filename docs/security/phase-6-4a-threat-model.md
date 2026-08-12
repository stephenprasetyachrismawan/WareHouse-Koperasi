# Phase 6.4A Threat Model Revalidation

Status: reviewed against the implementation on `hardening/security-tenant-isolation`.

## Security boundaries

- `warehouse_id` is the tenant boundary for warehouse-owned records.
- Policies and explicit Actions are the authorization boundary; UI visibility is not trusted.
- Queue, notification, export, broadcast, cache, lock, and file access are treated as separate boundary crossings.
- Super Admin is a platform actor. Tenant business access requires a controlled, auditable support context.
- App Admin manages warehouse administration but is not automatically an operational actor.

## Adversary cases and executable evidence

| Threat | Evidence | Result |
| --- | --- | --- |
| Tenant IDOR / UUID swap on exports | `TenantIsolationRegressionTest`, `OperationalReportsTest` | PASS |
| Suspended membership retaining export access | `TenantIsolationRegressionTest` | PASS |
| App Admin gaining operational permission through missing ACL data | `PrivilegeEscalationRegressionTest`, `WarehouseAccessControlTest` | PASS after fail-closed fix |
| App Admin assigning `super_admin` | `UserManagementTest`, `PrivilegeEscalationRegressionTest` | PASS |
| Cross-tenant notification subscription | `ChannelAuthorizationTest` | PASS |
| Notification channel payload crossing tenant boundary | `ChannelAuthorizationTest`, `PushTenantIsolationTest` | PASS |
| Private report/evidence exposure | `OperationalReportsTest`, `ReturnApprovalTenantIsolationTest`, `GoodsReceiptSecurityConcurrencyTest` | PASS |
| Security headers and request correlation | `SecurityHeadersTest` | PASS |
| Mass assignment of sensitive workflow fields | domain-specific Form Request/Action tests and repository audit | PASS / no unguarded sensitive model found |

## Controls reviewed

Authentication, MFA/session policy, OAuth account linking, CSRF, output escaping, raw SQL bindings, server-side network destinations, uploads, exports, broadcast authorization, job ownership validation, cache/lock keys, generic status mutation, and audit access were inspected.

The review found no evidence that the current Phase 6.4A changes introduce an arbitrary SSRF destination, public evidence disk, generic workflow status mutation, or client-controlled actor/approver field. Network calls remain outside database transactions.

## RLS decision

PostgreSQL Row Level Security is **not enabled in Phase 6.4A**. The application currently relies on Laravel tenant context, scoped queries, Policies, explicit warehouse IDs in persisted records/jobs, and adversarial regression tests. Enabling RLS safely requires a production PostgreSQL assessment covering connection pooling, queue context reset, scheduled jobs, migrations, support impersonation, and separate runtime/migration database users. That assessment remains a Phase 6.4C production-environment gate, not a claim that RLS exists today.

## Remaining security work

Phase 6.4B must verify failure behavior, concurrency, observability, and dependency-provider degradation. Phase 6.4C must prove backup/restore and production deployment controls. This document is not a production-readiness sign-off.
