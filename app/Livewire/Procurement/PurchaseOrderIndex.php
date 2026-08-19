<?php

namespace App\Livewire\Procurement;

use App\Models\PurchaseOrder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class PurchaseOrderIndex extends Component
{
    use WithPagination;

    public function render(): View
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        $warehouseId = Auth::user()->activeWarehouse()?->id;

        $purchaseOrders = PurchaseOrder::forWarehouse($warehouseId)
            ->with(['supplier', 'creator'])
            ->latest()
            ->paginate(10);

        return view('livewire.procurement.purchase-order-index', [
            'purchaseOrders' => $purchaseOrders,
        ]);
    }
}
