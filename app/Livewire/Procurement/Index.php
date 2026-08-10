<?php

namespace App\Livewire\Procurement;

use App\Models\PurchaseRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $status = '';

    public $source = '';

    public $urgency = '';

    public function render()
    {
        $this->authorize('viewAny', PurchaseRequest::class);

        $warehouseId = Auth::user()->activeWarehouse()?->id;

        $query = PurchaseRequest::query()
            ->where('warehouse_id', $warehouseId)
            ->with(['creator', 'items.item'])
            ->latest();

        if ($this->status) {
            $query->where('status', $this->status);
        }
        if ($this->source) {
            $query->where('source', $this->source);
        }
        if ($this->urgency) {
            $query->where('urgency', $this->urgency);
        }

        return view('livewire.procurement.index', [
            'requests' => $query->paginate(10),
        ]);
    }
}
