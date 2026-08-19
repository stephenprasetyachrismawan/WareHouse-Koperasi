<?php

namespace App\Livewire\Procurement;

use App\Actions\Procurement\CreatePurchaseOrderAction;
use App\Actions\Procurement\CreatePurchaseRequestGroupAction;
use App\Domain\Procurement\Queries\ApprovedAllocatablePurchaseRequestsQuery;
use App\Domain\Procurement\ValueObjects\AllocationInput;
use App\Domain\Procurement\ValueObjects\CreateGroupInput;
use App\Domain\Procurement\ValueObjects\CreatePurchaseOrderInput;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequestAllocation;
use App\Models\PurchaseRequestGroup;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class GroupingWorkspace extends Component
{
    public int $step = 1;

    /** @var array<int, int> [purchase_request_item_id => quantity] */
    public array $selected = [];

    public string $groupNotes = '';

    public ?int $groupId = null;

    public ?int $supplierId = null;

    public string $poNotes = '';

    /** @var array<int, float> [item_id => unit_cost] */
    public array $unitCosts = [];

    public function mount(): void
    {
        $this->authorize('create', PurchaseRequestGroup::class);
    }

    public function proceedToSupplierStep(CreatePurchaseRequestGroupAction $groupAction): void
    {
        $this->authorize('create', PurchaseRequestGroup::class);

        $allocations = collect($this->selected)
            ->filter(fn ($quantity) => (int) $quantity > 0)
            ->map(fn ($quantity, $purchaseRequestItemId) => new AllocationInput((int) $purchaseRequestItemId, (int) $quantity))
            ->values()
            ->all();

        if (empty($allocations)) {
            $this->addError('selected', 'Pilih minimal satu item beserta jumlah yang ingin dialokasikan.');

            return;
        }

        $warehouseId = Auth::user()->activeWarehouse()?->id;

        $input = new CreateGroupInput(
            warehouseId: $warehouseId,
            notes: $this->groupNotes ?: null,
            allocations: $allocations,
        );

        $group = $groupAction->execute(Auth::user(), $input);

        $this->groupId = $group->id;
        $this->step = 2;
    }

    public function backToSelection(): void
    {
        $this->step = 1;
        $this->groupId = null;
        $this->selected = [];
    }

    public function createPurchaseOrder(CreatePurchaseOrderAction $poAction): mixed
    {
        $this->authorize('create', PurchaseOrder::class);

        $this->validate([
            'supplierId' => 'required|exists:suppliers,id',
        ]);

        $warehouseId = Auth::user()->activeWarehouse()?->id;

        $items = $this->cartSummary()->map(fn ($row) => [
            'item_id' => $row['item_id'],
            'unit_cost' => (float) ($this->unitCosts[$row['item_id']] ?? 0),
        ])->values()->all();

        $input = new CreatePurchaseOrderInput(
            warehouseId: $warehouseId,
            groupId: $this->groupId,
            supplierId: (int) $this->supplierId,
            notes: $this->poNotes ?: null,
            items: $items,
        );

        $purchaseOrder = $poAction->execute(Auth::user(), $input);

        return redirect()
            ->route('procurement.purchase-orders.show', $purchaseOrder->uuid)
            ->with('success', 'Purchase Order berhasil dibuat.');
    }

    /**
     * @return Collection<int, array{item_id: int, item_name: string, item_unit: string, total_quantity: int}>
     */
    private function cartSummary(): Collection
    {
        if (! $this->groupId) {
            return collect();
        }

        return PurchaseRequestAllocation::where('purchase_request_group_id', $this->groupId)
            ->whereNull('purchase_order_item_id')
            ->with('purchaseRequestItem.item')
            ->get()
            ->groupBy('purchaseRequestItem.item_id')
            /**
             * @param  Collection<int, PurchaseRequestAllocation>  $allocations
             * @return array{item_id: int, item_name: string, item_unit: string, total_quantity: int}
             */
            ->map(function (Collection $allocations): array {
                /** @var Item $item */
                $item = $allocations->first()->purchaseRequestItem->item;

                return [
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'item_unit' => $item->unit,
                    'total_quantity' => (int) $allocations->sum('allocated_quantity'),
                ];
            })
            ->values();
    }

    public function render(ApprovedAllocatablePurchaseRequestsQuery $candidatesQuery): View
    {
        $warehouseId = Auth::user()->activeWarehouse()?->id;

        $candidates = $this->step === 1 ? $candidatesQuery->execute($warehouseId) : collect();
        $suppliers = $this->step === 2
            ? Supplier::forWarehouse($warehouseId)->active()->orderBy('name')->get()
            : collect();
        $cartItems = $this->step === 2 ? $this->cartSummary() : collect();

        return view('livewire.procurement.grouping-workspace', [
            'candidates' => $candidates,
            'suppliers' => $suppliers,
            'cartItems' => $cartItems,
        ]);
    }
}
