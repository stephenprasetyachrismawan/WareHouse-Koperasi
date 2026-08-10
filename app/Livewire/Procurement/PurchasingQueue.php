<?php

namespace App\Livewire\Procurement;

use App\Models\PurchaseRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class PurchasingQueue extends Component
{
    use WithPagination;

    public function render()
    {
        $this->authorize('viewAny', PurchaseRequest::class);

        $warehouseId = Auth::user()->activeWarehouse()?->id;

        $requests = PurchaseRequest::forWarehouse($warehouseId)
            ->approved()
            ->with(['creator', 'items.item'])
            ->latest()
            ->paginate(10);

        return view('livewire.procurement.purchasing-queue', [
            'requests' => $requests,
        ]);
    }
}
