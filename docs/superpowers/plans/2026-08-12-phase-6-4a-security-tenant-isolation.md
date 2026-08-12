# Phase 6.4A Security and Tenant Isolation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Harden the existing Laravel application so security-sensitive HTTP, broadcast, file, export, and queued boundaries have executable tenant-isolation evidence.

**Architecture:** Add small middleware for request correlation and environment-aware security headers; make private notification channels explicitly warehouse-scoped; and give private report/evidence files an explicit private disk while preserving existing Policy and Action boundaries. Add focused feature tests at HTTP, broadcast, job, and file seams without introducing a new authorization bypass or business workflow.

**Tech Stack:** Laravel 13, PHPUnit/Pest feature tests, Livewire, Reverb/Pusher protocol, Laravel Filesystem, SQLite test database, existing factories and Policies.

## Global Constraints

- `Warehouse` is the tenant and every tenant-owned operation includes `warehouse_id` in its authorization and persistence boundary.
- UI hiding is never authorization; tests call routes, Livewire actions, jobs, channel auth, and file controllers directly.
- No generic status mutation, `$request->all()`, unscoped tenant lookup, public evidence disk, or universal `super_admin` bypass.
- Machine Learning remains disabled and unimplemented.
- Tests use synthetic factories only and external providers are mocked only at their system boundary.
- Every task follows RED → GREEN → REFACTOR and ends with a focused test command.
- This plan covers only 6.4A. 6.4B starts from merged `main`, not this branch.

---

### Task 1: Add correlation IDs and environment-aware security headers

**Files:**
- Create: `app/Http/Middleware/AddSecurityHeaders.php`
- Create: `app/Http/Middleware/AssignRequestId.php`
- Create: `config/security.php`
- Modify: `bootstrap/app.php`
- Test: `tests/Feature/Security/SecurityHeadersTest.php`

**Interfaces:**
- `AssignRequestId::handle(Request $request, Closure $next): Response` accepts a trusted incoming `X-Request-Id` only when it matches a safe opaque format, otherwise generates a UUID; it returns the same ID in the response.
- `AddSecurityHeaders::handle(Request $request, Closure $next): Response` adds HSTS only for production HTTPS and adds CSP, `X-Content-Type-Options`, `Referrer-Policy`, and clickjacking protection without exposing environment data.
- `config/security.php` exposes `request_id_header`, `headers`, and environment-aware `hsts` settings; no secret values are stored there.

- [ ] **Step 1: Write the failing test**

Add tests that prove:

```php
public function test_public_response_contains_safe_security_headers_and_request_id(): void
{
    $response = $this->get('/');

    $response->assertOk()
        ->assertHeader('X-Request-Id')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Content-Security-Policy')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
}

public function test_valid_request_id_is_propagated_but_injection_is_replaced(): void
{
    $this->get('/', ['X-Request-Id' => 'request-2026-0001'])
        ->assertHeader('X-Request-Id', 'request-2026-0001');

    $this->get('/', ['X-Request-Id' => "bad\r\nX-Leak: yes"])
        ->assertHeader('X-Request-Id')
        ->assertHeaderMissing('X-Leak');
}

public function test_hsts_is_not_added_to_local_http(): void
{
    config(['app.env' => 'local']);

    $this->get('/')->assertHeaderMissing('Strict-Transport-Security');
}
```

The production HTTPS test must set `app.env=production`, `app.url=https://warehouse.test`, and assert `max-age` without changing the repository `.env`.

- [ ] **Step 2: Run RED**

Run:

```bash
./automation/warehouse-orchestrator/agent-tools/agent-tdd-red --filter SecurityHeadersTest
```

Expected: the new test fails because the middleware and headers are not registered.

- [ ] **Step 3: Implement the minimum code**

Register the middleware in `bootstrap/app.php` using the framework's web middleware stack. Use a strict request-ID allowlist such as `/\A[A-Za-z0-9._:-]{1,128}\z/`; never copy arbitrary header bytes into a response. Use a configured CSP that does not contain `script-src *`; keep HSTS conditional on production HTTPS.

- [ ] **Step 4: Run GREEN**

Run:

```bash
./automation/warehouse-orchestrator/agent-tools/agent-tdd-green --filter SecurityHeadersTest
```

Expected: all `SecurityHeadersTest` examples pass.

- [ ] **Step 5: Refactor and focused verification**

Run:

```bash
./automation/warehouse-orchestrator/agent-tools/agent-test-focused --filter SecurityHeadersTest
vendor/bin/pint --test
```

Keep header construction in the middleware/config boundary; do not add header logic to controllers or views.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Middleware config/security.php bootstrap/app.php tests/Feature/Security/SecurityHeadersTest.php
git commit -m "security: add request correlation and response headers"
```

### Task 2: Bind private notification broadcasts to recipient and warehouse

**Files:**
- Modify: `routes/channels.php`
- Modify: `app/Domain/Notifications/Events/InboxNotificationCreated.php`
- Modify: `resources/js/notifications.js`
- Test: `tests/Feature/Notifications/ChannelAuthorizationTest.php`
- Test: `tests/Feature/Notifications/PushTenantIsolationTest.php`

**Interfaces:**
- Channel name becomes `private-user.{userId}.warehouse.{warehouseId}.notifications` for tenant notifications and `private-user.{userId}.platform.notifications` for platform notifications.
- Channel authorization accepts the authenticated `User`, route-bound user ID, and warehouse ID; it returns true only for an active membership in that warehouse and false for inactive, cross-tenant, malformed, or anonymous callers.
- `InboxNotificationCreated::broadcastOn()` uses the persisted notification warehouse, never ambient session state.

- [ ] **Step 1: Write the failing tests**

Extend `ChannelAuthorizationTest` with:

```php
public function test_user_can_authorize_only_their_own_active_warehouse_channel(): void
{
    $warehouse = Warehouse::factory()->create();
    $user = User::factory()->create();
    WarehouseMembership::factory()->create([
        'user_id' => $user->id,
        'warehouse_id' => $warehouse->id,
        'status' => 'active',
    ]);

    $this->actingAs($user)->post('/broadcasting/auth', [
        'channel_name' => "private-user.{$user->id}.warehouse.{$warehouse->id}.notifications",
        'socket_id' => '1234.5678',
    ])->assertOk();
}

public function test_user_cannot_subscribe_to_a_warehouse_without_active_membership(): void
{
    $warehouse = Warehouse::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)->post('/broadcasting/auth', [
        'channel_name' => "private-user.{$user->id}.warehouse.{$warehouse->id}.notifications",
        'socket_id' => '1234.5678',
    ])->assertForbidden();
}
```

Add a broadcast event assertion that a notification for warehouse B produces a channel containing B and never A.

- [ ] **Step 2: Run RED**

```bash
./automation/warehouse-orchestrator/agent-tools/agent-tdd-red --filter 'ChannelAuthorizationTest|PushTenantIsolationTest'
```

Expected: the new warehouse-qualified channel tests fail against the current user-only channel.

- [ ] **Step 3: Implement the minimum code**

Update the channel callback, event channel, and Echo subscription together. Preserve platform notification behavior if the existing data model permits `warehouse_id = null`; do not broadcast a tenant notification to a user-only global channel.

- [ ] **Step 4: Run GREEN**

```bash
./automation/warehouse-orchestrator/agent-tools/agent-tdd-green --filter 'ChannelAuthorizationTest|PushTenantIsolationTest'
```

- [ ] **Step 5: Refactor and focused verification**

```bash
./automation/warehouse-orchestrator/agent-tools/agent-test-focused --filter 'ChannelAuthorizationTest|PushTenantIsolationTest'
```

Verify payload remains minimal and that the browser-side active warehouse filter is defense-in-depth, not the authorization boundary.

- [ ] **Step 6: Commit**

```bash
git add routes/channels.php app/Domain/Notifications/Events/InboxNotificationCreated.php resources/js/notifications.js tests/Feature/Notifications/ChannelAuthorizationTest.php tests/Feature/Notifications/PushTenantIsolationTest.php
git commit -m "security: scope notification broadcasts by warehouse"
```

### Task 3: Make private storage an explicit boundary for reports and evidence

**Files:**
- Modify: `config/filesystems.php`
- Modify: `app/Actions/Reports/CreateOperationalReportExportAction.php`
- Modify: `app/Http/Controllers/Reports/DownloadReportExportController.php`
- Modify: `app/Http/Controllers/Returns/ReturnEvidenceController.php`
- Modify: `app/Http/Controllers/Procurement/QualityInspectionEvidenceController.php`
- Modify: `tests/Feature/Reports/OperationalReportsTest.php`
- Modify: `tests/Feature/Procurement/GoodsReceiptSecurityConcurrencyTest.php`
- Modify: `tests/Feature/Returns/ReturnApprovalTenantIsolationTest.php`

**Interfaces:**
- `Storage::disk('private')` is the only application disk used for report CSVs and QC/return evidence; its root is `storage/app/private` and it is not publicly served.
- Authorized download controllers keep Policy checks, expiry checks, tenant checks, and neutral missing-file responses.
- File paths remain tenant-prefixed and generated server-side; clients do not choose arbitrary disk/path values.

- [ ] **Step 1: Write the failing tests**

Change the existing export test to use `Storage::fake('private')`, assert the generated file is on `private`, and assert `Storage::disk('public')->exists($path)` is false. Add path-swap and inactive-membership download denial tests for a report export and representative evidence controller.

- [ ] **Step 2: Run RED**

```bash
./automation/warehouse-orchestrator/agent-tools/agent-tdd-red --filter 'OperationalReportsTest|GoodsReceiptSecurityConcurrencyTest|ReturnEvidenceSecurityTest'
```

Expected: export generation still writes to `local`, so the changed private-disk assertion fails.

- [ ] **Step 3: Implement the minimum code**

Add a `private` disk with `serve=false`, move report generation and all evidence reads/writes that currently use the private local root to it, and keep the `local` disk only for unrelated framework storage if any. Do not add a public storage link for this disk.

- [ ] **Step 4: Run GREEN**

```bash
./automation/warehouse-orchestrator/agent-tools/agent-tdd-green --filter 'OperationalReportsTest|GoodsReceiptSecurityConcurrencyTest|ReturnEvidenceSecurityTest'
```

- [ ] **Step 5: Refactor and focused verification**

```bash
./automation/warehouse-orchestrator/agent-tools/agent-test-focused --filter 'OperationalReportsTest|GoodsReceiptSecurityConcurrencyTest|ReturnEvidenceSecurityTest'
vendor/bin/pint --test
```

Review every changed `Storage` call for a policy check before access and for a warehouse-prefixed path.

- [ ] **Step 6: Commit**

```bash
git add config/filesystems.php app/Actions/Reports/CreateOperationalReportExportAction.php app/Http/Controllers/Reports/DownloadReportExportController.php app/Http/Controllers/Returns/ReturnEvidenceController.php app/Http/Controllers/Procurement/QualityInspectionEvidenceController.php tests/Feature/Reports/OperationalReportsTest.php tests/Feature/Procurement/GoodsReceiptSecurityConcurrencyTest.php tests/Feature/Returns/ReturnEvidenceSecurityTest.php
git commit -m "security: isolate reports and evidence on private storage"
```

### Task 4: Add the 6.4A security regression matrix and assessment evidence

**Files:**
- Create: `tests/Feature/Security/TenantIsolationRegressionTest.php`
- Create: `tests/Feature/Security/PrivilegeEscalationRegressionTest.php`
- Create: `tests/Feature/Security/FileAndExportSecurityTest.php`
- Create: `tests/Feature/Security/FileAndExportSecurityTest.php`
- Create: `docs/security/phase-6-4a-threat-model.md`
- Create: `docs/security/phase-6-4a-assessment.md`
- Modify: `.github/workflows/tests.yml`

**Interfaces:**
- The regression tests use existing HTTP/Livewire/Policy/Job/channel/file seams and only synthetic factories.
- The assessment records actual search results, commands, fixed findings, dependency/secret-scan outcomes, RLS decision, and unresolved blockers; it does not assert that code search alone proves safety.
- CI runs focused security tests in addition to the existing test/build gates.

- [ ] **Step 1: Write the failing tests**

Cover at minimum:

```text
Warehouse A actor -> Warehouse A report/item/stock/pickup/procurement/return allowed where role permits
Warehouse A actor -> Warehouse B UUID/path/download denied
inactive membership -> denied
app_admin -> cannot grant super_admin or platform capability
super_admin -> cannot download tenant report through ordinary tenant policy
queue job with IDs from Warehouse B -> never uses Warehouse A ambient state
mass-assignment payload -> warehouse_id/status/actor fields remain server controlled
```

Use separate test methods with literal expected outcomes; do not create a generic loop that hides which domain is covered.

- [ ] **Step 2: Run RED**

```bash
./automation/warehouse-orchestrator/agent-tools/agent-tdd-red --filter 'TenantIsolationRegressionTest|PrivilegeEscalationRegressionTest|FileAndExportSecurityTest'
```

Expected: any new assertions expose the unqualified broadcast/private-disk or missing regression coverage before implementation is complete.

- [ ] **Step 3: Implement only required supporting fixes**

Add no new business workflow. Fix only authorization, tenant scoping, input allowlisting, job context, or test/CI wiring required by a failing regression test. Document code-search findings that are not changed as accepted risks or blockers.

- [ ] **Step 4: Run GREEN and broader focused verification**

```bash
./automation/warehouse-orchestrator/agent-tdd-green --filter 'TenantIsolationRegressionTest|PrivilegeEscalationRegressionTest|FileAndExportSecurityTest'
./automation/warehouse-orchestrator/agent-test-focused --filter 'Security|TenantIsolation|Policy|OperationalReports|ChannelAuthorization|PushTenantIsolation'
```

- [ ] **Step 5: Record assessment evidence**

Run and record outputs for:

```bash
composer audit
npm audit --audit-level=high
git grep -n 'request->all()' -- app routes tests || true
git grep -n '\$guarded = \[\]' -- app || true
git grep -n 'isSuperAdmin' -- app/Policies app/Providers app/Livewire || true
```

Run a repository secret scan using an available standard tool; if unavailable, document the exact command limitation and do not claim a clean scan.

- [ ] **Step 6: Commit**

```bash
git add tests/Feature/Security docs/security .github/workflows/tests.yml
git commit -m "security: add phase 6.4 tenant regression matrix"
```

### Task 5: Review and verify the complete 6.4A slice

**Files:**
- Modify: `docs/security/phase-6-4a-assessment.md`
- Modify: `docs/superpowers/plans/2026-08-12-phase-6-4a-security-tenant-isolation.md`

- [ ] **Step 1: Standards review**

Review the complete diff from `main` against the Five-Axis checklist: correctness, simplicity, architecture, security, and performance. Resolve every Critical or Required finding before the PR.

- [ ] **Step 2: Specification review**

Check every 6.4A requirement in `docs/superpowers/specs/2026-08-12-phase-6-4-production-hardening-design.md` against a test, code change, or documented environment limitation. Do not turn an unverified item into a PASS.

- [ ] **Step 3: Run repository gates**

```bash
php artisan test
vendor/bin/pint --test
vendor/bin/phpstan analyse
npm run build
composer audit
npm audit --audit-level=high
```

Record existing failures separately from new failures and keep the branch honest.

- [ ] **Step 4: Push and open PR**

```bash
git push -u origin hardening/security-tenant-isolation
gh pr create --base main --head hardening/security-tenant-isolation --title "security: harden tenant isolation and production security controls" --body-file /tmp/phase-6-4a-pr.md
```

The PR body must include requirement IDs, affected roles/workflows, schema impact (`none` unless tests prove otherwise), security impact, commands, review evidence, rollout/rollback, and unresolved risks.

- [ ] **Step 5: Merge only after review**

After review and CI evidence are green:

```bash
gh pr merge <number> --merge --delete-branch
git switch main
git pull --ff-only origin main
```

Then verify `composer run dev`/the repository's equivalent process group, web, Vite, queue, and Reverb before creating the 6.4B branch.

## Execution record

Implemented on `hardening/security-tenant-isolation` through commits `4a3820f`, `c564cfd`, `a3403d8`, and `144a063`, with the final file/export regression test added after the initial review. Focused security tests, 585 feature tests, Pint, Composer audit, npm audit, and the frontend build passed. PHPStan remains a pre-existing baseline failure with 409 errors outside the changed files and is recorded as an unresolved repository gate, not suppressed.
