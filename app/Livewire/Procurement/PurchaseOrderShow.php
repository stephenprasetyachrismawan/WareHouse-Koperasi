<?php

namespace App\Livewire\Procurement;

use App\Actions\Procurement\SendPurchaseOrderAction;
use App\Domain\Procurement\Queries\PurchaseOrderReceivingProgressQuery;
use App\Domain\Procurement\Queries\PurchaseOrderTraceabilityQuery;
use App\Models\PurchaseOrder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PurchaseOrderShow extends Component
{
    public PurchaseOrder $purchaseOrder;

    public function mount(PurchaseOrder $purchaseOrder): void
    {
        $this->authorize('view', $purchaseOrder);

        $this->purchaseOrder = $purchaseOrder->load(['supplier', 'creator', 'sentBy', 'group', 'items.item', 'goodsReceipt']);
    }

    public function send(SendPurchaseOrderAction $action): void
    {
        $action->execute(Auth::user(), $this->purchaseOrder);

        session()->flash('success', 'Purchase Order telah dikirim ke supplier.');

        $this->purchaseOrder->refresh();
    }

    public function render(PurchaseOrderTraceabilityQuery $traceabilityQuery, PurchaseOrderReceivingProgressQuery $progressQuery): View
    {
        return view('livewire.procurement.purchase-order-show', [
            'traceability' => $traceabilityQuery->execute($this->purchaseOrder),
            'receivingProgress' => $progressQuery->execute($this->purchaseOrder),
        ]);
    }
}
