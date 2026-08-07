<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Stock;

use App\Models\Item;
use App\Models\StockTransaction;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Ledger extends Component
{
    use AuthorizesRequests, WithPagination;

    public ?int $item_id = null;

    public string $movement_type = '';

    public string $search = '';

    public function mount(?int $item_id = null): void
    {
        $this->authorize('viewAny', StockTransaction::class);
        $this->item_id = $item_id;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingItemId(): void
    {
        $this->resetPage();
    }

    public function updatingMovementType(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        /** @var User $actor */
        $actor = Auth::user();
        $membership = $actor->activeMembership();
        $warehouseId = $membership?->warehouse_id;

        $transactions = StockTransaction::with(['item', 'performer'])
            ->where('warehouse_id', $warehouseId)
            ->when($this->item_id, fn ($q) => $q->where('item_id', $this->item_id))
            ->when($this->movement_type !== '', fn ($q) => $q->where('movement_type', $this->movement_type))
            ->when($this->search !== '', function ($q) {
                $q->whereHas('item', function ($iq) {
                    $iq->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('code', 'like', '%'.$this->search.'%');
                })->orWhere('idempotency_key', 'like', '%'.$this->search.'%')
                    ->orWhere('reason', 'like', '%'.$this->search.'%');
            })
            ->latest('occurred_at')
            ->paginate(15);

        $items = Item::where('warehouse_id', $warehouseId)->orderBy('name')->get(['id', 'name', 'code']);

        return view('livewire.inventory.stock.ledger', [
            'transactions' => $transactions,
            'items' => $items,
            'warehouse' => $membership?->warehouse,
        ]);
    }
}
