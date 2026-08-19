<?php

namespace App\Livewire\Procurement;

use App\Actions\Procurement\SubmitPurchaseForApprovalAction;
use App\Domain\Procurement\Actions\CreatePurchaseRequestAction;
use App\Domain\Procurement\ValueObjects\PurchaseRequestInput;
use App\Domain\Procurement\ValueObjects\PurchaseRequestItemInput;
use App\Enums\PurchaseRequestSource;
use App\Enums\PurchaseRequestUrgency;
use App\Models\Item;
use App\Models\PurchaseRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;

class Create extends Component
{
    public string $urgency = 'NORMAL';

    public string $notes = '';

    /** @var array<int, array{item_id: int|string, quantity: int}> */
    public array $items = []; // [item_id, quantity]

    /** @var Collection<int, Item> */
    public Collection $availableItems;

    public bool $duplicateWarning = false;

    /** @var array<int, array{request_number: string, item_name: string, quantity: int}> */
    public array $duplicateInfo = [];

    public bool $is_duplicate_override = false;

    public string $duplicate_override_reason = '';

    public function mount(): void
    {
        $this->authorize('create', PurchaseRequest::class);

        $warehouseId = Auth::user()->activeWarehouse()?->id;
        $this->availableItems = Item::where('warehouse_id', $warehouseId)->get();
        $this->items = [['item_id' => '', 'quantity' => 1]];
    }

    public function addItem(): void
    {
        $this->items[] = ['item_id' => '', 'quantity' => 1];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->checkDuplicates();
    }

    public function updatedItems(): void
    {
        $this->checkDuplicates();
    }

    public function checkDuplicates(): void
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

    public function save(CreatePurchaseRequestAction $createAction, SubmitPurchaseForApprovalAction $submitAction): RedirectResponse|Redirector
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
            userId: Auth::user()->id,
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

    public function render(): View
    {
        return view('livewire.procurement.create');
    }
}
