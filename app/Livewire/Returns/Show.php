<?php

namespace App\Livewire\Returns;

use App\Actions\Returns\SubmitReturnForApprovalAction;
use App\Actions\Returns\VerifyReturnAction;
use App\Domain\Returns\ValueObjects\VerifyReturnInput;
use App\Enums\ReturnStatus;
use App\Models\ReturnRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithFileUploads;

class Show extends Component
{
    use WithFileUploads;

    public ReturnRequest $returnRequest;

    public string $scannedBarcode = '';

    public int $verifiedQuantity = 1;

    public $staffPhoto = null;

    public string $verificationNotes = '';

    public function mount(ReturnRequest $returnRequest): void
    {
        $this->authorize('view', $returnRequest);

        $this->returnRequest = $returnRequest->load(['items.item', 'items.pickupRequestItem', 'evidence', 'pickupRequest', 'cooperativeMembership.user', 'verifier']);
        $this->verifiedQuantity = $this->returnRequest->items->first()?->return_quantity ?? 1;
    }

    public function verify(VerifyReturnAction $action): void
    {
        $this->authorize('verify', $this->returnRequest);

        $this->validate([
            'scannedBarcode' => 'required|string',
            'verifiedQuantity' => 'required|integer|min:1',
            'staffPhoto' => 'required|image|mimes:jpg,jpeg,png|max:5120',
            'verificationNotes' => 'nullable|string',
        ]);

        $evidencePath = $this->staffPhoto->store("return-evidence/{$this->returnRequest->warehouse_id}", 'local');
        $evidenceMime = $this->staffPhoto->getMimeType();

        try {
            $updated = $action->execute(Auth::user(), $this->returnRequest, new VerifyReturnInput(
                warehouseId: $this->returnRequest->warehouse_id,
                scannedBarcode: $this->scannedBarcode,
                verifiedQuantity: (int) $this->verifiedQuantity,
                evidencePath: $evidencePath,
                evidenceMime: $evidenceMime,
                notes: $this->verificationNotes ?: null,
                expectedVersion: $this->returnRequest->version,
            ));

            $this->returnRequest = $updated->load(['items.item', 'items.pickupRequestItem', 'evidence', 'pickupRequest', 'cooperativeMembership.user', 'verifier']);
            session()->flash('success', 'Barang berhasil diverifikasi.');
        } catch (\RuntimeException $e) {
            $this->addError('verify', $e->getMessage());
        }
    }

    public function submitForApproval(SubmitReturnForApprovalAction $action): void
    {
        try {
            $updated = $action->execute(Auth::user(), $this->returnRequest, $this->returnRequest->version);
            $this->returnRequest = $updated->load(['items.item', 'items.pickupRequestItem', 'evidence', 'pickupRequest', 'cooperativeMembership.user', 'verifier']);
            session()->flash('success', 'Retur diteruskan untuk keputusan Kepala Gudang.');
        } catch (\RuntimeException $e) {
            $this->addError('submitForApproval', $e->getMessage());
        }
    }

    public function render()
    {
        $canVerify = Gate::forUser(Auth::user())->allows('verify', $this->returnRequest)
            && $this->returnRequest->status === ReturnStatus::Submitted;

        $canSubmitForApproval = Gate::forUser(Auth::user())->allows('submitForApproval', $this->returnRequest)
            && $this->returnRequest->status === ReturnStatus::AdminVerified;

        return view('livewire.returns.show', [
            'canVerify' => $canVerify,
            'canSubmitForApproval' => $canSubmitForApproval,
        ]);
    }
}
