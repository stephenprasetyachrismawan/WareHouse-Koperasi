<?php

namespace Tests\Feature\Seeders;

use App\Enums\CancellationRequestStatus;
use App\Enums\PickupRequestStatus;
use App\Enums\PurchaseRequestStatus;
use App\Enums\ReturnFaultAttribution;
use App\Models\CancellationRequest;
use App\Models\PickupRequest;
use App\Models\PurchaseRequest;
use App\Models\ReturnRequest;
use App\Models\Warehouse;
use Database\Seeders\DevelopmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DemoJatengSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_gudang_koperasi_jateng_covers_every_pickup_and_purchase_request_status(): void
    {
        $this->seed(DevelopmentSeeder::class);

        $warehouse = Warehouse::where('code', 'WH-JATENG')->firstOrFail();

        $pickupStatuses = PickupRequest::where('warehouse_id', $warehouse->id)
            ->distinct()
            ->pluck('status')
            ->map(fn (PickupRequestStatus $s) => $s->value)
            ->sort()
            ->values()
            ->all();

        foreach (PickupRequestStatus::cases() as $case) {
            $this->assertContains(
                $case->value,
                $pickupStatuses,
                "Expected WH-JATENG to have a pickup request in status {$case->value}."
            );
        }

        $purchaseRequestStatuses = PurchaseRequest::where('warehouse_id', $warehouse->id)
            ->distinct()
            ->pluck('status')
            ->map(fn (PurchaseRequestStatus $s) => $s->value)
            ->sort()
            ->values()
            ->all();

        foreach (PurchaseRequestStatus::cases() as $case) {
            $this->assertContains(
                $case->value,
                $purchaseRequestStatuses,
                "Expected WH-JATENG to have a purchase request in status {$case->value}."
            );
        }
    }

    public function test_gudang_koperasi_jateng_covers_the_cancellation_request_workflow(): void
    {
        $this->seed(DevelopmentSeeder::class);

        $warehouse = Warehouse::where('code', 'WH-JATENG')->firstOrFail();

        $statuses = CancellationRequest::where('warehouse_id', $warehouse->id)
            ->distinct()
            ->pluck('status')
            ->map(fn (CancellationRequestStatus $s) => $s->value)
            ->sort()
            ->values()
            ->all();

        foreach (CancellationRequestStatus::cases() as $case) {
            $this->assertContains(
                $case->value,
                $statuses,
                "Expected WH-JATENG to have a cancellation request in status {$case->value}."
            );
        }

        // The rejected cancellation must leave its purchase request untouched.
        $rejected = CancellationRequest::where('warehouse_id', $warehouse->id)
            ->where('status', CancellationRequestStatus::Rejected->value)
            ->firstOrFail();
        $this->assertNotSame(
            PurchaseRequestStatus::Cancelled,
            $rejected->purchaseRequest->status,
            'Rejecting a cancellation request must not cancel the purchase request.'
        );

        // The approved cancellation must actually cancel its purchase request.
        $approved = CancellationRequest::where('warehouse_id', $warehouse->id)
            ->where('status', CancellationRequestStatus::Approved->value)
            ->firstOrFail();
        $this->assertSame(PurchaseRequestStatus::Cancelled, $approved->purchaseRequest->status);
    }

    public function test_gudang_koperasi_jateng_demonstrates_both_return_fault_attributions(): void
    {
        $this->seed(DevelopmentSeeder::class);

        $warehouse = Warehouse::where('code', 'WH-JATENG')->firstOrFail();

        $attributions = ReturnRequest::where('warehouse_id', $warehouse->id)
            ->whereNotNull('fault_attribution')
            ->distinct()
            ->pluck('fault_attribution')
            ->map(fn (ReturnFaultAttribution $a) => $a->value)
            ->sort()
            ->values()
            ->all();

        $this->assertContains(ReturnFaultAttribution::Warehouse->value, $attributions);
        $this->assertContains(ReturnFaultAttribution::Supplier->value, $attributions);
    }

    public function test_seeding_gudang_koperasi_jateng_leaves_the_main_company_as_the_active_permission_team(): void
    {
        $this->seed(DevelopmentSeeder::class);

        $mainCompanyId = Warehouse::where('code', 'WH-PUSAT')->value('company_id');

        $this->assertSame(
            $mainCompanyId,
            app(PermissionRegistrar::class)->getPermissionsTeamId(),
            'Seeding WH-JATENG (a different Company than WH-PUSAT/WH-BARAT) must not leave a '
            .'different company as the active spatie/permission team once seeding finishes.'
        );
    }

    public function test_running_the_full_demo_seed_twice_does_not_duplicate_jateng_data(): void
    {
        $this->seed(DevelopmentSeeder::class);

        $warehouse = Warehouse::where('code', 'WH-JATENG')->firstOrFail();

        $countsBefore = [
            'pickups' => PickupRequest::where('warehouse_id', $warehouse->id)->count(),
            'purchase_requests' => PurchaseRequest::where('warehouse_id', $warehouse->id)->count(),
            'cancellation_requests' => CancellationRequest::where('warehouse_id', $warehouse->id)->count(),
            'returns' => ReturnRequest::where('warehouse_id', $warehouse->id)->count(),
        ];

        $this->assertGreaterThan(0, $countsBefore['pickups']);

        $this->seed(DevelopmentSeeder::class);

        $this->assertSame($countsBefore, [
            'pickups' => PickupRequest::where('warehouse_id', $warehouse->id)->count(),
            'purchase_requests' => PurchaseRequest::where('warehouse_id', $warehouse->id)->count(),
            'cancellation_requests' => CancellationRequest::where('warehouse_id', $warehouse->id)->count(),
            'returns' => ReturnRequest::where('warehouse_id', $warehouse->id)->count(),
        ]);
    }
}
