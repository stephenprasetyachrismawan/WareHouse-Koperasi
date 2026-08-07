<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Stock;

use App\Models\Item;
use App\Models\StockBalance;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Overview extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    public string $filter = 'all'; // all, critical, negative

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        /** @var User $actor */
        $actor = Auth::user();
        $this->authorize('viewAny', StockBalance::class);

        $membership = $actor->activeMembership();
        $warehouseId = $membership?->warehouse_id;

        $items = Item::with(['stockBalance', 'barcodes'])
            ->where('warehouse_id', $warehouseId)
            ->where('is_active', true)
            ->when($this->search !== '', function ($q) {
                $q->where(function ($sub) {
                    $sub->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('code', 'like', '%'.$this->search.'%')
                        ->orWhereHas('barcodes', fn ($bq) => $bq->where('barcode', 'like', '%'.$this->search.'%'));
                });
            })
            ->when($this->filter === 'critical', function ($q) {
                $q->where(function ($sub) {
                    $sub->whereHas('stockBalance', function ($sbq) {
                        $sbq->whereColumn('quantity', '<', 'items.minimum_stock');
                    })->orWhere(function ($noBalance) {
                        $noBalance->whereDoesntHave('stockBalance')
                            ->where('minimum_stock', '>', 0);
                    });
                });
            })
            ->when($this->filter === 'negative', function ($q) {
                $q->whereHas('stockBalance', fn ($sbq) => $sbq->where('quantity', '<', 0));
            })
            ->orderBy('name')
            ->paginate(15);

        // Stats queries
        $totalItems = Item::where('warehouse_id', $warehouseId)->where('is_active', true)->count();

        $criticalCount = Item::where('warehouse_id', $warehouseId)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereHas('stockBalance', function ($sbq) {
                    $sbq->whereColumn('quantity', '<', 'items.minimum_stock');
                });
            })->count();

        $negativeCount = StockBalance::where('warehouse_id', $warehouseId)
            ->where('quantity', '<', 0)
            ->count();

        return view('livewire.inventory.stock.overview', [
            'items' => $items,
            'warehouse' => $membership?->warehouse,
            'totalItems' => $totalItems,
            'criticalCount' => $criticalCount,
            'negativeCount' => $negativeCount,
        ]);
    }
}
