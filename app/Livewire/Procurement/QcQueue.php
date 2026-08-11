<?php

namespace App\Livewire\Procurement;

use App\Domain\Procurement\Queries\PendingQualityInspectionsQuery;
use App\Models\GoodsReceipt;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class QcQueue extends Component
{
    use WithPagination;

    public function render(PendingQualityInspectionsQuery $pendingQuery)
    {
        $this->authorize('viewAny', GoodsReceipt::class);

        $warehouseId = Auth::user()->activeWarehouse()?->id;

        return view('livewire.procurement.qc-queue', [
            'pendingItems' => $pendingQuery->execute($warehouseId),
        ]);
    }
}
