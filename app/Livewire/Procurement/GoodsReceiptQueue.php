<?php

namespace App\Livewire\Procurement;

use App\Domain\Procurement\Queries\ReceivablePurchaseOrdersQuery;
use App\Models\GoodsReceipt;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class GoodsReceiptQueue extends Component
{
    use WithPagination;

    public function render(ReceivablePurchaseOrdersQuery $receivableQuery): View
    {
        $this->authorize('viewAny', GoodsReceipt::class);

        $warehouseId = Auth::user()->activeWarehouse()?->id;

        $receivable = $receivableQuery->execute($warehouseId);

        $received = GoodsReceipt::forWarehouse($warehouseId)
            ->with(['purchaseOrder.supplier'])
            ->latest('received_at')
            ->paginate(10, ['*'], 'receivedPage');

        return view('livewire.procurement.goods-receipt-queue', [
            'receivable' => $receivable,
            'received' => $received,
        ]);
    }
}
