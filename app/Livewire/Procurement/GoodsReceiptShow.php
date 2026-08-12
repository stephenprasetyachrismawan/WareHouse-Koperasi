<?php

namespace App\Livewire\Procurement;

use App\Actions\Procurement\CompleteQualityInspectionAction;
use App\Domain\Procurement\ValueObjects\CompleteQualityInspectionInput;
use App\Enums\QualityInspectionCondition;
use App\Enums\QualityInspectionResult;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\QualityInspection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class GoodsReceiptShow extends Component
{
    use WithFileUploads;

    public GoodsReceipt $goodsReceipt;

    public bool $showQcModal = false;

    public ?int $inspectingItemId = null;

    public string $result = 'PASS';

    public string $condition = 'GOOD';

    public string $qcNotes = '';

    public $evidence = null;

    public function mount(GoodsReceipt $goodsReceipt): void
    {
        $this->authorize('view', $goodsReceipt);

        $this->goodsReceipt = $goodsReceipt->load([
            'purchaseOrder.supplier',
            'receiver',
            'items.item',
            'items.inspection.inspector',
        ]);
    }

    public function openQcModal(int $goodsReceiptItemId): void
    {
        $this->inspectingItemId = $goodsReceiptItemId;
        $this->result = QualityInspectionResult::Pass->value;
        $this->condition = QualityInspectionCondition::Good->value;
        $this->qcNotes = '';
        $this->evidence = null;
        $this->showQcModal = true;
    }

    public function closeQcModal(): void
    {
        $this->showQcModal = false;
        $this->inspectingItemId = null;
    }

    public function submitQc(CompleteQualityInspectionAction $action): void
    {
        $goodsReceiptItem = GoodsReceiptItem::findOrFail($this->inspectingItemId);

        $this->authorize('create', [QualityInspection::class, $goodsReceiptItem]);

        $this->validate([
            'result' => 'required|in:PASS,FAIL',
            'condition' => 'required|string',
            'qcNotes' => $this->result === 'FAIL' ? 'required|string|min:3' : 'nullable|string',
            'evidence' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        $evidencePath = null;
        $evidenceMime = null;

        if ($this->evidence) {
            $evidencePath = $this->evidence->store("qc-evidence/{$goodsReceiptItem->warehouse_id}/{$goodsReceiptItem->id}", 'private');
            $evidenceMime = $this->evidence->getMimeType();
        }

        $warehouseId = Auth::user()->activeWarehouse()?->id;

        $action->execute(Auth::user(), $goodsReceiptItem, new CompleteQualityInspectionInput(
            warehouseId: $warehouseId,
            result: QualityInspectionResult::from($this->result),
            condition: QualityInspectionCondition::from($this->condition),
            notes: $this->qcNotes ?: null,
            evidencePath: $evidencePath,
            evidenceMime: $evidenceMime,
        ));

        session()->flash('success', $this->result === 'PASS' ? 'QC lolos, stok telah diperbarui.' : 'QC gagal dicatat. Stok-in diblokir.');

        $this->showQcModal = false;
        $this->inspectingItemId = null;
        $this->goodsReceipt->refresh()->load(['items.inspection.inspector', 'purchaseOrder']);
    }

    public function render()
    {
        return view('livewire.procurement.goods-receipt-show');
    }
}
