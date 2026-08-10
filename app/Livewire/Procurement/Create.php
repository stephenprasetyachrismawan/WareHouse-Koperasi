<?php

namespace App\Livewire\Procurement;

use App\Actions\Procurement\CreatePurchaseRequestAction;
use App\Actions\Procurement\SubmitPurchaseForApprovalAction;
use App\Domain\Procurement\ValueObjects\PurchaseRequestInput;
use App\Domain\Procurement\ValueObjects\PurchaseRequestItemInput;
use App\Enums\PurchaseRequestSource;
use App\Enums\PurchaseRequestUrgency;
use App\Models\Item;
use App\Models\PurchaseRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Create extends Component
{
    public $urgency = 'NORMAL';

    public $notes = '';

    public $items = []; // [item_id, quantity]

    public $availableItems = [];

    public $duplicateWarning = false;

    public $duplicateInfo = [];

    public $is_duplicate_override = false;

    public $duplicate_override_reason = '';

    public function mount()
    {
        $this->authorize('create', PurchaseRequest::class);

        $warehouseId = Auth::user()->activeWarehouse()?->id;
        $this->availableItems = Item::where('warehouse_id', $warehouseId)->get();
        $this->items = [['item_id' => '', 'quantity' => 1]];
    }

    public function addItem()
    {
        $this->items[] = ['item_id' => '', 'quantity' => 1];
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->checkDuplicates();
    }

    public function updatedItems()
    {
        $this->checkDuplicates();
    }

    public function checkDuplicates()
    {
        $this->duplicateWarning = false;
        $this->duplicateInfo = [];

        $warehouseId = Auth::user()->activeWarehouse()?->id;

        $itemIds = collect($this->items)
            ->pluck('item_id')
            ->filter(fn ($id) => ! empty($id))
            ->unique()
            ->toArray();

        if (empty($itemIds) || ! $warehouseId) {
            return;
        }

        $duplicates = PurchaseRequest::forWarehouse($warehouseId)
            ->inProgress()
            ->whereHas('items', function ($query) use ($itemIds) {
                $query->whereIn('item_id', $itemIds);
            })
            ->with(['items' => function ($query) use ($itemIds) {
                $query->whereIn('item_id', $itemIds);
            }, 'items.item'])
            ->get();

        if ($duplicates->isNotEmpty()) {
            $this->duplicateWarning = true;
            foreach ($duplicates as $duplicate) {
                foreach ($duplicate->items as $item) {
                    if (in_array($item->item_id, $itemIds)) {
                        $this->duplicateInfo[] = [
                            'request_number' => $duplicate->request_number,
                            'item_name' => $item->item->name ?? 'Unknown Item',
                            'quantity' => $item->requested_quantity,
                        ];
                    }
                }
            }
        }
    }

    public function save(CreatePurchaseRequestAction $createAction, SubmitPurchaseForApprovalAction $submitAction)
    {
        $this->authorize('create', PurchaseRequest::class);

        $this->validate([
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'urgency' => 'required|string',
            'is_duplicate_override' => $this->duplicateWarning ? 'accepted' : 'nullable',
            'duplicate_override_reason' => $this->duplicateWarning && $this->is_duplicate_override ? 'required|string|min:3' : 'nullable',
        ]);

        $warehouseId = Auth::user()->activeWarehouse()?->id;

        $itemInputs = array_map(
            fn ($item) => new PurchaseRequestItemInput((int) $item['item_id'], (int) $item['quantity']),
            $this->items
        );

        $input = new PurchaseRequestInput(
            warehouseId: $warehouseId,
            userId: Auth::id(),
            source: PurchaseRequestSource::ManualStaff,
            urgency: PurchaseRequestUrgency::from($this->urgency),
            notes: $this->notes,
            items: $itemInputs,
            overrideDuplicate: (bool) $this->is_duplicate_override,
            overrideReason: $this->duplicate_override_reason
        );

        $request = $createAction->execute(Auth::user(), $input);
        $submitAction->execute(Auth::user(), $request);

        return redirect()->route('procurement.index')->with('success', 'Purchase Request created successfully.');
    }

    public function render()
    {
        return view('livewire.procurement.create');
    }
}
