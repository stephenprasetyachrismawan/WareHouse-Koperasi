<?php

namespace App\Livewire\Procurement;

use App\Actions\Procurement\RecordGoodsReceiptAction;
use App\Domain\Procurement\ValueObjects\RecordGoodsReceiptInput;
use App\Enums\PurchaseOrderStatus;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class RecordGoodsReceipt extends Component
{
    public PurchaseOrder $purchaseOrder;

    /** @var array<int, int> [purchase_order_item_id => received_quantity] */
    public array $receivedQuantities = [];

    public string $notes = '';

    public function mount(PurchaseOrder $purchaseOrder): void
    {
        $this->authorize('create', GoodsReceipt::class);

        if ($purchaseOrder->status !== PurchaseOrderStatus::SentToSupplier) {
            abort(403, 'Only purchase orders sent to the supplier can be received.');
        }

        $this->purchaseOrder = $purchaseOrder->load(['supplier', 'items.item']);

        foreach ($this->purchaseOrder->items as $item) {
            $this->receivedQuantities[$item->id] = $item->ordered_quantity;
        }
    }

    public function save(RecordGoodsReceiptAction $action): mixed
    {
        $this->authorize('create', GoodsReceipt::class);

        $this->validate([
            'receivedQuantities' => 'required|array',
            'receivedQuantities.*' => 'required|integer|min:0',
        ]);

        $warehouseId = Auth::user()->activeWarehouse()?->id;

        $receipt = $action->execute(Auth::user(), new RecordGoodsReceiptInput(
            warehouseId: $warehouseId,
            purchaseOrderId: $this->purchaseOrder->id,
            receivedQuantities: array_map('intval', $this->receivedQuantities),
            notes: $this->notes ?: null,
        ));

        return redirect()
            ->route('procurement.receipts.show', $receipt->uuid)
            ->with('success', 'Penerimaan barang berhasil dicatat.');
    }

    public function render(): View
    {
        return view('livewire.procurement.record-goods-receipt');
    }
}
