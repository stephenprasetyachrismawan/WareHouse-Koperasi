<?php

namespace App\Livewire\Procurement;

use App\Actions\Procurement\DirectCancelPurchaseRequestAction;
use App\Actions\Procurement\RequestPurchaseCancellationAction;
use App\Actions\Procurement\SubmitPurchaseForApprovalAction;
use App\Models\PurchaseRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Show extends Component
{
    public PurchaseRequest $purchaseRequest;

    public bool $showCancelModal = false;

    public string $cancellation_reason = '';

    public bool $directCancel = false; // true if direct cancel, false if request cancel

    public function mount(PurchaseRequest $purchaseRequest): void
    {
        $this->authorize('view', $purchaseRequest);

        $this->purchaseRequest = $purchaseRequest->load(['items.item', 'approvals.requester', 'approvals.approver', 'creator', 'cancellationRequests']);
    }

    public function submitForApproval(SubmitPurchaseForApprovalAction $submitAction): void
    {
        $submitAction->execute(Auth::user(), $this->purchaseRequest);
        session()->flash('success', 'Purchase Request submitted for approval.');
        $this->purchaseRequest->refresh();
    }

    public function openCancelModal(bool $direct = false): void
    {
        $this->directCancel = $direct;
        $this->showCancelModal = true;
        $this->cancellation_reason = '';
    }

    public function closeCancelModal(): void
    {
        $this->showCancelModal = false;
    }

    public function submitCancel(RequestPurchaseCancellationAction $requestAction, DirectCancelPurchaseRequestAction $directAction): void
    {
        $this->validate([
            'cancellation_reason' => 'required|string|min:3',
        ]);

        if ($this->directCancel) {
            $directAction->execute(Auth::user(), $this->purchaseRequest, $this->cancellation_reason);
            session()->flash('success', 'Purchase Request has been cancelled directly.');
        } else {
            $requestAction->execute(Auth::user(), $this->purchaseRequest, $this->cancellation_reason);
            session()->flash('success', 'Cancellation request submitted.');
        }

        $this->showCancelModal = false;
        $this->purchaseRequest->refresh();
    }

    public function render(): View
    {
        return view('livewire.procurement.show');
    }
}
