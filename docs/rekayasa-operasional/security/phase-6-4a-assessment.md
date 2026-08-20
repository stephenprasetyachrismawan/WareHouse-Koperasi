# Phase 6.4A Security Assessment

## Findings fixed

### App Admin ACL fail-open fallback

`WarehouseMembership::hasPermission()` previously returned `true` for App Admin whenever a permission was absent from the Spatie registry. That made a missing or incomplete permission registry an operational privilege escalation path. The fallback now fails closed. The App Admin dashboard remains available through its explicit administration route, while operational dashboard/report cards require explicit membership permissions.

### Tenant-qualified notification channels

Private notification channels previously used only the recipient user ID. Channels now include the warehouse ID and validate active membership and warehouse status. A platform channel is separate for notifications that intentionally have no tenant.

### Private report and evidence storage

Operational exports, QC evidence, and return evidence now use the non-served `private` filesystem disk. Controllers still authorize the business record before reading the object. Public-disk and cross-tenant download assertions are part of the feature tests.

### Request correlation and security headers

Web requests receive a validated or newly generated `X-Request-Id`. Responses include the correlation ID and baseline security headers. HSTS is environment-aware and is emitted only for production HTTPS configuration.

## Verification commands

```bash
./automation/warehouse-orchestrator/agent-tools/agent-test-focused --filter 'TenantIsolationRegressionTest|PrivilegeEscalationRegressionTest|SecurityHeadersTest|ChannelAuthorizationTest|OperationalReportsTest|ReturnApprovalTenantIsolationTest'
vendor/bin/pint --test
npm run build
```

The focused suite, formatting check, and asset build passed on this branch. A full repository test, dependency audit, static analysis, and real-browser check remain required before the 6.4A merge decision.

## Dependency and secret audit status

`composer audit`, `npm audit`, and repository secret scanning must be run as part of the slice gate and recorded in the Phase 6.4A PR. A command that cannot execute in the current environment is recorded as NOT VERIFIED; it is not treated as a pass.

## Accepted limitations for this slice

- RLS is not enabled; compensating application controls are tested and documented above.
- Runtime database-user privilege separation, backup/restore, load testing, and provider outage drills are Phase 6.4C/B gates.
- No production readiness decision is made by this slice alone.
