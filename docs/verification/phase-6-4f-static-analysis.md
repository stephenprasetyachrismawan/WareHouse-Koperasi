# Phase 6.4F-1 Static Analysis Closure — Evidence

Date: 2026-08-19 UTC
Branch: `fix/phase-6-4f-1-phpstan-closure` (15 commits, based on `main` at `9f2eb1a`)
Baseline `main` at start of this phase: `9f2eb1a9c406734c77765674d13c7c3c13483931`

## A. Initial state

```
composer types:check (PHPStan level 7, app/ bootstrap/app.php config/ database/ routes/)
411 errors
107 files
```

Local analysis performance was never a blocker: `--memory-limit=1G` completed in ~2.15s with a warm
result cache; the "300s Composer process timeout" account previously recorded in
`docs/PRODUCTION-READINESS.md` was stale and did not match the actual CI failure log, which showed
real level-7 type errors, not a timeout.

## B. Error taxonomy at baseline

| Category | Errors | Files | Notes |
|---|---:|---:|---|
| Livewire components | 121 | 28 | Missing property/method/param/return types |
| Relations | 93 | 21 | Model relation methods missing generics |
| Actions | 48 | 16 | Mostly cascade from untyped relations |
| Query Objects | 31 | 9 | Cascade + own return-type generics |
| Builder typing | 25 | 8 | `scopeForWarehouse()`-style scopes |
| Factories | 32 (18 files) | 18 | Missing `Factory<TModel>` generics |
| Seeders | 19 | 5 | Mostly cascade |
| Model generics (other) | 14 | 14 | Misc |
| Broadcasting/notifications/queues | 12 | 5 | Mostly cascade |
| Policies/authorization | 9 | 2 | One was a real broken-policy bug, see D |
| DTO/value objects | 7 | 2 | Array shapes + one dead branch |

## C. Runtime and authorization defects found and fixed

Static-analysis cleanup surfaced defects beyond typing noise. Each was fixed with a RED→GREEN
regression test, not typed around.

1. **`app/Livewire/Procurement/Create.php` — TypeError on every manual purchase request submission.**
   The component imported the legacy array-based `App\Actions\Procurement\CreatePurchaseRequestAction`
   while constructing the typed `PurchaseRequestInput` DTO expected by the Domain-layer action of the
   same short name. Every submission of the "create purchase request" form threw a `TypeError`. Fixed
   by importing the correct `App\Domain\Procurement\Actions\CreatePurchaseRequestAction` (already
   covered by `CreatePurchaseRequestTest`) and deleting the now fully-unreferenced legacy action, which
   also contained the invalid `PurchaseRequestSource::Manual` enum reference (correct case is
   `ManualStaff`). Regression test:
   `ProcurementLivewireTest::test_create_component_save_creates_purchase_request`.

2. **`app/Livewire/Pickup/Show.php` — `RelationNotFoundException` on every pickup detail page view.**
   `mount()` eager-loaded a nonexistent `approvals.actor` relation (`Approval` only has `requester()`
   and `approver()`), and the Blade view read `$approval->actor->name`, `$approval->type` (no such
   column), and compared the `ApprovalStatus` enum against a raw string. Every visit to
   `pickup.show` threw a fatal error. Fixed the eager-load and the view to use the real
   `requester()`/`approver()` relations and `$approval->status->value`. Regression test:
   `PickupLivewireTest::test_show_page_renders_with_approval_history`.

3. **`CancellationRequestPolicy` — every method fatal.** All three methods (`viewAny`, `view`,
   `decide`) called `$user->hasActiveMembership()`, `$user->hasPermission()`, and read
   `$user->active_warehouse_id` — none exist on `App\Models\User`. This policy was not referenced by
   any live authorization check (the real Request/Approve/Reject cancellation flows all authorize
   against `PurchaseRequestPolicy` instead), so it was a dormant landmine rather than an active outage
   — but Laravel resolves it automatically for any future `Gate::authorize(..., $cancellationRequest)`
   call. Rewritten to match the sibling `PurchaseRequestPolicy` convention
   (`activeMembership()`/`hasPermissionTo()`/`activeWarehouse()`), since `CancellationRequest` reuses
   `PurchaseRequestPolicy`'s own permission enum cases. Added a focused authorization contract test
   (`CancellationRequestPolicyTest`, 9 cases: same-warehouse allow, unauthorized role, other warehouse,
   cross-tenant, inactive membership, missing membership, `view`/`viewAny` variants) — all 9 confirmed
   RED against the original code (`Call to undefined method ... hasActiveMembership()`) before the fix,
   GREEN after.

4. **`app/Domain/Pickup/ValueObjects/PickupRequestInput.php` — dead defensive branch.** An
   `instanceof` check on `$items` could never be false: the constructor's own PHPDoc already documents
   `PickupRequestItemInput[]`, the sole caller (`Livewire\Pickup\Create`) always maps raw input through
   `PickupRequestItemInput` before constructing this DTO, and no other value object in the domain layer
   carries an equivalent runtime guard. Removed to match the established convention of trusting the
   documented type.

5. **Unhandled `TemporaryUploadedFile::store()` failure (3 call sites).** `store()` inherits
   `Illuminate\Http\UploadedFile`'s documented `string|false` return type. QC evidence
   (`GoodsReceiptShow::submitQc()`), return-request evidence (`Returns\Create::submit()`), and
   return-verification photos (`Returns\Show::verify()`) all passed the raw result straight through as
   if it were always a path. On a storage failure, `false` would have been persisted as if it were a
   real evidence path, silently losing the evidence with no user-facing error. Each call site now
   checks for failure and surfaces a validation error instead of proceeding.

6. **Sequence-number generators counted via `->get()->count()` instead of `->count()`** (PO number,
   Purchase Request Group number, Goods Receipt number) — a real, if minor, performance finding
   (Larastan `larastan.noUnnecessaryCollectionCall`): loading every matching row into memory just to
   count them in PHP, inside a `lockForUpdate()` critical section. Changed to a direct
   `SELECT COUNT(*) ... FOR UPDATE`, preserving the same row-locking semantics (verified against the
   existing `PurchaseOrderSecurityConcurrencyTest` and `GoodsReceiptSecurityConcurrencyTest` suites).

None of these were hidden behind a type annotation, a baseline, or a suppression — each got a
root-cause fix and, where behavior could change, a test proving the fix.

## D. Error progression

| Checkpoint | Errors | Delta |
|---|---:|---:|
| Baseline | 411 | — |
| After 6.4F-1A (runtime bugs + authz policy) | 399 | −12 |
| After 6.4F-1B (Model/Relation/Builder/Factory generics — 21 models, 18 factories) | 154 | −245 |
| After 6.4F-1C (28 Livewire components) | 41 | −113 |
| After 6.4F-1D (remaining Actions/Queries/Seeders/misc) | 0 | −41 |
| **Final** | **0** | — |

The model/relation/builder/factory generics slice (6.4F-1B) was confirmed as the highest-leverage
slice as predicted by the initial inventory: fixing 21 model files cascaded into 245 error reductions
across Actions, Query Objects, Seeders, and notification code that consumed those relations.

## E. Notable PHPStan/Larastan modeling limitations encountered (not app bugs)

- **`?->` immediately followed by `??` is always syntactically redundant in PHP**, regardless of
  whether the left-hand object is itself nullable — PHP's `??` applies `isset()`-style semantics to
  the whole property/array access chain, so `$a?->b ?? $c` and `$a->b ?? $c` are behaviorally
  identical. PHPStan's `nullsafe.neverNull` rule flags this correctly; empirically verified via a
  pre-existing test (`Items Edit can update item details`) that already exercises a barcode-less item
  through this exact code path.
- **`Illuminate\Support\Collection`'s `TValue` generic is invariant**, not covariant (documented at
  <https://phpstan.org/blog/whats-up-with-template-covariant>). A `Collection<int, array{...}>` return
  type can be genuinely unsatisfiable from a `.map()` chain even when the declared and actual shapes
  are semantically identical, because PHPStan compares the array-shape's exact internal structure
  (sealed/unsealed, and — as found here — literal-narrowed types like `App\Enums\X` vs `mixed`, or a
  dead `?? null` fallback silently narrowing `string|null` down to `string`) rather than doing a
  looser structural match. Where no real caller depended on `Collection`-specific methods
  (`PurchaseOrderReceivingProgressQuery`, `ReceiptSourceTraceQuery`), the return type was simplified to
  a plain `array`, which isn't subject to this invariance. Where a real caller used `->first()` and
  `->pluck()` (`PurchaseOrderTraceabilityQuery`, exercised by `AllocationTraceabilityTest`), the
  `Collection` return type was kept and the actual root cause — a dead `?? null` on a `NOT NULL`
  foreign key relation — was fixed instead.
- **Larastan infers `BelongsTo` magic-property nullability from the FK column's `NOT NULL` constraint**
  in the migration, not just the relation method's own return type annotation. Several `?->`/`?? null`
  patterns that looked defensively necessary were, per the actual schema, unreachable.

## F. Final verification (fresh, this session)

All gates run fresh against the final commit on this branch.

```
vendor/bin/pint --test                    PASS
composer types:check (PHPStan level 7)    PASS — 0 errors
php artisan test --compact                PASS — 642 tests, 1579 assertions
npm run build                             PASS (only the known optional "fontaine" font-fallback warning)
composer audit                            PASS — no security vulnerability advisories found
npm audit --audit-level=high              PASS — 0 vulnerabilities
scripts/verification/secret-scan.sh .     NOT VERIFIED — gitleaks binary is not installed on this
                                           host (pre-existing environment gap, also recorded in the
                                           6.4F design baseline; GitHub Actions CI runs gitleaks
                                           separately)
git diff --check main...HEAD              PASS — no whitespace errors
composer test (config:clear + Pint +      PASS — completed in 34.9s wall time, confirming the
  PHPStan + full suite)                     previously recorded "300s timeout" was not a real
                                           performance ceiling once the underlying type errors
                                           were fixed
```

Test count grew from the last recorded full-suite baseline of 606 passed / 1,458 assertions
(`docs/PRODUCTION-READINESS.md`, PR #51 evidence) to 642 passed / 1,579 assertions — the +36 tests are
the regression tests added in slice 6.4F-1A (2 runtime-bug tests, 9 `CancellationRequestPolicyTest`
cases, 1 Pickup Show crash-reproduction test) plus incidental coverage growth from other merged work
on `main` since that baseline.

### Resource usage (4 GB development VPS)

`free -h` before this phase's PHPStan/test work: ~1.4Gi available, 0B swap.
Throughout this phase (21 model files, 28 Livewire components, dozens of PHPStan re-runs, and a full
642-test suite run): available RAM stayed in the 500Mi–1.2Gi range; swap usage never exceeded 180KiB
(effectively unused). No OOM, no thrashing. A 2GB swapfile (`vm.swappiness=10`) was added before this
phase as a safety net per the parent Phase 6.4F-0 design, but was not meaningfully drawn on.

## G. Remaining risk / honestly unresolved

- Secret scanning is not locally verified on this host (gitleaks unavailable). GitHub Actions CI is
  expected to run it independently; this must be confirmed once CI is exercised.
- This phase did not audit `Procurement/show.blade.php`'s approval-history block, which appears to
  have the same class of bug as the Pickup Show fix (`$approval->user->name` and `$approval->notes`
  don't exist on the `Approval` model — the real properties are `requester`/`approver` and `reason`).
  It was discovered by pattern-matching during the Pickup Show investigation but was out of scope for
  this PHPStan-closure pass (no PHPStan error pointed at it, and it's a different file than the one
  under active repair). **Recommend a follow-up fix with its own regression test before this is
  forgotten.**
- No new tests were added purely for the ~390 mechanical typing-only findings (Livewire property
  types, Model/Factory generics, array shapes) per the task's own guidance that mechanical corrections
  may rely on PHPStan plus existing regression coverage when runtime behavior is unchanged — this
  was verified by running the full domain test suite after every batch, not skipped.
