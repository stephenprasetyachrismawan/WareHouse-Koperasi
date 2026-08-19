<?php

namespace App\Livewire\Procurement;

use App\Actions\Procurement\ApprovePurchaseCancellationAction;
use App\Actions\Procurement\ApprovePurchaseRequestAction;
use App\Actions\Procurement\RejectPurchaseCancellationAction;
use App\Actions\Procurement\RejectPurchaseRequestAction;
use App\Models\CancellationRequest;
use App\Models\PurchaseRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ApprovalInbox extends Component
{
    use WithPagination;

    public string $tab = 'purchase_requests'; // purchase_requests, cancellations

    public bool $actionModal = false;

    public string $actionType = ''; // approve_pr, reject_pr, approve_cancel, reject_cancel

    public int|string|null $selectedId = null;

    public string $notes = '';

    public function switchTab(string $tab): void
    {
        $this->tab = $tab;
        $this->resetPage();
    }

    public function openActionModal(string $type, int|string $id): void
    {
        $this->actionType = $type;
        $this->selectedId = $id;
        $this->notes = '';
        $this->actionModal = true;
    }

    public function closeActionModal(): void
    {
        $this->actionModal = false;
    }

    public function submitAction(
        ApprovePurchaseRequestAction $approvePr,
        RejectPurchaseRequestAction $rejectPr,
        ApprovePurchaseCancellationAction $approveCancel,
        RejectPurchaseCancellationAction $rejectCancel
    ): void {
        $this->authorize('approve', PurchaseRequest::class);

        $user = Auth::user();

        if ($this->actionType === 'reject_pr' || $this->actionType === 'reject_cancel') {
            $this->validate(['notes' => 'required|string|min:3']);
        }

        if (in_array($this->actionType, ['approve_pr', 'reject_pr'])) {
            $pr = PurchaseRequest::findOrFail($this->selectedId);
            if ($this->actionType === 'approve_pr') {
                $approvePr->execute($user, $pr);
                session()->flash('success', 'Purchase Request approved.');
            } else {
                $rejectPr->execute($user, $pr, $this->notes);
                session()->flash('success', 'Purchase Request rejected.');
            }
        } elseif (in_array($this->actionType, ['approve_cancel', 'reject_cancel'])) {
            $cr = CancellationRequest::findOrFail($this->selectedId);
            if ($this->actionType === 'approve_cancel') {
                $approveCancel->execute($user, $cr, $this->notes);
                session()->flash('success', 'Cancellation approved.');
            } else {
                $rejectCancel->execute($user, $cr, $this->notes);
                session()->flash('success', 'Cancellation rejected.');
            }
        }

        $this->actionModal = false;
    }

    public function render(): View
    {
        $this->authorize('approve', PurchaseRequest::class);

        $warehouseId = Auth::user()->activeWarehouse()?->id;

        $purchaseRequests = collect();
        $cancellations = collect();

        if ($this->tab === 'purchase_requests') {
            $purchaseRequests = PurchaseRequest::forWarehouse($warehouseId)
                ->where('status', 'WAITING_APPROVAL')
                ->with(['creator', 'items.item.stockBalance'])
                ->paginate(10);
        } else {
            $cancellations = CancellationRequest::whereHas('purchaseRequest', function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            })
                ->where('status', 'PENDING')
                ->with(['purchaseRequest', 'requester'])
                ->paginate(10);
        }

        return view('livewire.procurement.approval-inbox', [
            'purchaseRequests' => $purchaseRequests,
            'cancellations' => $cancellations,
        ]);
    }
}
