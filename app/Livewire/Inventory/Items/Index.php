<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Items;

use App\Actions\Inventory\ArchiveItemAction;
use App\Models\Item;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    public string $statusFilter = 'active';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function archiveItem(int $itemId): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        $membership = $actor->activeMembership();

        if (! $membership) {
            abort(403);
        }

        $item = Item::where('warehouse_id', $membership->warehouse_id)
            ->findOrFail($itemId);

        $this->authorize('archive', $item);

        $action = new ArchiveItemAction;
        $action->execute($actor, $item);

        session()->flash('status', __('Barang berhasil diarsipkan.'));
    }

    public function render(): View
    {
        /** @var User $actor */
        $actor = Auth::user();
        $this->authorize('viewAny', Item::class);

        $membership = $actor->activeMembership();
        $warehouseId = $membership?->warehouse_id;

        $items = Item::with(['barcodes', 'stockBalance'])
            ->where('warehouse_id', $warehouseId)
            ->when($this->statusFilter === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->statusFilter === 'archived', fn ($q) => $q->where('is_active', false))
            ->when($this->search !== '', function ($q) {
                $q->where(function ($sub) {
                    $sub->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('code', 'like', '%'.$this->search.'%')
                        ->orWhereHas('barcodes', fn ($bq) => $bq->where('barcode', 'like', '%'.$this->search.'%'));
                });
            })
            ->latest()
            ->paginate(12);

        return view('livewire.inventory.items.index', [
            'items' => $items,
            'warehouse' => $membership?->warehouse,
        ]);
    }
}
