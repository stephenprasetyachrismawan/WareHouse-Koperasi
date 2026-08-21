<?php

namespace Database\Seeders;

use App\Actions\Procurement\CompleteQualityInspectionAction;
use App\Actions\Procurement\CreatePurchaseOrderAction;
use App\Actions\Procurement\CreatePurchaseRequestGroupAction;
use App\Actions\Procurement\RecordGoodsReceiptAction;
use App\Actions\Procurement\SendPurchaseOrderAction;
use App\Actions\Returns\CompleteReplacementPickupAction;
use App\Actions\Returns\PrepareReplacementPickupAction;
use App\Domain\Procurement\ValueObjects\AllocationInput;
use App\Domain\Procurement\ValueObjects\CompleteQualityInspectionInput;
use App\Domain\Procurement\ValueObjects\CreateGroupInput;
use App\Domain\Procurement\ValueObjects\CreatePurchaseOrderInput;
use App\Domain\Procurement\ValueObjects\RecordGoodsReceiptInput;
use App\Enums\PurchaseRequestStatus;
use App\Enums\QualityInspectionCondition;
use App\Enums\QualityInspectionResult;
use App\Enums\ReturnFaultAttribution;
use App\Enums\ReturnReasonCode;
use App\Enums\ReturnStatus;
use App\Models\Item;
use App\Models\PickupRequest;
use App\Models\PurchaseRequest;
use App\Models\ReturnRequest;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * Progresses the WH-JATENG REPLACEMENT_PENDING demo returns through the
 * stock-available path (Scenario C: PUP-UREA has stock -> ready for
 * repickup; Scenario B: BNH-PADI carried all the way to completed), plus
 * two fresh synthetic zero-stock scenarios demonstrating the procurement
 * fallback (one pending approval, one carried to ready).
 */
class DemoJatengReturnReplacementSeeder extends Seeder
{
    public function run(): void
    {
        Gate::before(fn () => true);

        $warehouse = Warehouse::where('code', 'WH-JATENG')->first();
        $staffJateng = User::where('email', 'staff.jateng@koperasi.id')->first();
        $purchasingJateng = User::where('email', 'purchasing.jateng@koperasi.id')->first();

        if (! $warehouse || ! $staffJateng || ! $purchasingJateng) {
            return;
        }

        // Scenario C (PUP-UREA) already has replacement stock -> ready for repickup.
        $readyReturn = ReturnRequest::where('reason_notes', 'Demo Seeder Return - Scenario C (Waiting approval) (JTG)')->first();
        if ($readyReturn && $readyReturn->status === ReturnStatus::ReplacementPending) {
            app(PrepareReplacementPickupAction::class)->execute($readyReturn);
        }

        // Scenario B (BNH-PADI) already has replacement stock -> carry all the way to completed.
        $completedReturn = ReturnRequest::where('reason_notes', 'Demo Seeder Return - Scenario B (Admin verified) (JTG)')->first();
        if ($completedReturn && $completedReturn->status === ReturnStatus::ReplacementPending) {
            $prepared = app(PrepareReplacementPickupAction::class)->execute($completedReturn);
            if ($prepared->status === ReturnStatus::ReadyForRepickup) {
                app(CompleteReplacementPickupAction::class)->execute($staffJateng, $prepared);
            }
        }

        $this->seedNoStockScenario(
            $warehouse,
            $staffJateng,
            $purchasingJateng,
            itemCode: 'JTG-RPL-A',
            marker: 'Demo Seeder Return - Replacement Scenario D (No stock, PR pending approval) (JTG)',
            completeProcurement: false,
        );

        $this->seedNoStockScenario(
            $warehouse,
            $staffJateng,
            $purchasingJateng,
            itemCode: 'JTG-RPL-B',
            marker: 'Demo Seeder Return - Replacement Scenario E (Procurement completed, ready) (JTG)',
            completeProcurement: true,
        );
    }

    private function seedNoStockScenario(
        Warehouse $warehouse,
        User $staff,
        User $purchasing,
        string $itemCode,
        string $marker,
        bool $completeProcurement,
    ): void {
        if (ReturnRequest::where('reason_notes', $marker)->exists()) {
            return;
        }

        $item = Item::firstOrCreate(
            ['warehouse_id' => $warehouse->id, 'code' => $itemCode],
            [
                'uuid' => (string) Str::uuid(),
                'name' => "Barang Demo Penggantian {$itemCode}",
                'unit' => 'pcs',
                'minimum_stock' => 5,
                'description' => 'Item sintetis untuk demo fallback pengadaan penggantian Gudang Jateng.',
                'is_active' => true,
            ]
        );

        $anyCompletedPickup = PickupRequest::where('warehouse_id', $warehouse->id)
            ->where('status', 'COMPLETED')
            ->first();
        if (! $anyCompletedPickup) {
            return;
        }

        $membership = WarehouseMembership::where('warehouse_id', $warehouse->id)
            ->where('user_id', $anyCompletedPickup->user_id)
            ->where('status', 'active')
            ->firstOrFail();

        $returnRequest = ReturnRequest::create([
            'uuid' => (string) Str::uuid(),
            'warehouse_id' => $warehouse->id,
            'cooperative_membership_id' => $membership->id,
            'pickup_request_id' => $anyCompletedPickup->id,
            'return_number' => 'RET-JTG-'.now()->format('Ymd').'-'.strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)),
            'status' => ReturnStatus::ReplacementPending,
            'reason_code' => ReturnReasonCode::Damaged,
            'reason_notes' => $marker,
            'submitted_by' => $anyCompletedPickup->user_id,
            'submitted_at' => now(),
            'approved_by' => $staff->id,
            'approved_at' => now(),
            'fault_attribution' => ReturnFaultAttribution::Supplier,
            'disposed_at' => now(),
            'version' => 1,
        ]);
        $returnRequest->items()->create([
            'pickup_request_item_id' => $anyCompletedPickup->items()->first()->id,
            'item_id' => $item->id,
            'return_quantity' => 3,
            'barcode_verified' => true,
        ]);

        $prepared = app(PrepareReplacementPickupAction::class)->execute($returnRequest);

        if (! $completeProcurement) {
            return;
        }

        $pr = PurchaseRequest::where('return_request_id', $prepared->id)->first();
        if (! $pr) {
            return;
        }

        $supplier = Supplier::forWarehouse($warehouse->id)->active()->orderBy('id')->first();
        if (! $supplier) {
            return;
        }

        $prItem = $pr->items->first();
        $pr->update(['status' => PurchaseRequestStatus::Approved->value]);

        $group = app(CreatePurchaseRequestGroupAction::class)->execute($purchasing, new CreateGroupInput(
            warehouseId: $warehouse->id,
            notes: $marker,
            allocations: [new AllocationInput($prItem->id, $prItem->requested_quantity)],
        ));

        $po = app(CreatePurchaseOrderAction::class)->execute($purchasing, new CreatePurchaseOrderInput(
            warehouseId: $warehouse->id,
            groupId: $group->id,
            supplierId: $supplier->id,
            notes: $marker,
            items: [['item_id' => $item->id, 'unit_cost' => 9500]],
        ));

        $po = app(SendPurchaseOrderAction::class)->execute($purchasing, $po)->load('items');

        $receipt = app(RecordGoodsReceiptAction::class)->execute($purchasing, new RecordGoodsReceiptInput(
            warehouseId: $warehouse->id,
            purchaseOrderId: $po->id,
            receivedQuantities: $po->items->pluck('ordered_quantity', 'id')->all(),
        ));

        app(CompleteQualityInspectionAction::class)->execute($purchasing, $receipt->items->first(), new CompleteQualityInspectionInput(
            warehouseId: $warehouse->id,
            result: QualityInspectionResult::Pass,
            condition: QualityInspectionCondition::Good,
            notes: 'QC lolos, penggantian retur siap.',
        ));
    }
}
