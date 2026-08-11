<?php

namespace App\Livewire\Returns;

use App\Actions\Returns\ApproveReturnAction;
use App\Actions\Returns\CompleteReplacementPickupAction;
use App\Actions\Returns\PrepareReplacementPickupAction;
use App\Actions\Returns\RejectReturnAction;
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

    private const WITH_RELATIONS = [
        'items.item',
        'items.pickupRequestItem',
        'evidence',
        'pickupRequest',
        'cooperativeMembership.user',
        'verifier',
        'approver',
        'rejecter',
        'disposals',
        'replacementPickup.items',
        'replacementPurchaseRequests',
    ];

    public ReturnRequest $returnRequest;

    public string $scannedBarcode = '';

    public int $verifiedQuantity = 1;

    public $staffPhoto = null;

    public string $verificationNotes = '';

    public string $rejectReason = '';

    public bool $showRejectForm = false;

    public function mount(ReturnRequest $returnRequest): void
    {
        $this->authorize('view', $returnRequest);

        $this->returnRequest = $returnRequest->load(self::WITH_RELATIONS);
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

            $this->returnRequest = $updated->load(self::WITH_RELATIONS);
            session()->flash('success', 'Barang berhasil diverifikasi.');
        } catch (\RuntimeException $e) {
            $this->addError('verify', $e->getMessage());
        }
    }

    public function submitForApproval(SubmitReturnForApprovalAction $action): void
    {
        try {
            $updated = $action->execute(Auth::user(), $this->returnRequest, $this->returnRequest->version);
            $this->returnRequest = $updated->load(self::WITH_RELATIONS);
            session()->flash('success', 'Retur diteruskan untuk keputusan Kepala Gudang.');
        } catch (\RuntimeException $e) {
            $this->addError('submitForApproval', $e->getMessage());
        }
    }

    public function approve(ApproveReturnAction $action): void
    {
        try {
            $updated = $action->execute(Auth::user(), $this->returnRequest);
            $this->returnRequest = $updated->load(self::WITH_RELATIONS);
            session()->flash('success', 'Retur disetujui. Barang lama telah dicatat sebagai disposed.');
        } catch (\RuntimeException $e) {
            $this->addError('decision', $e->getMessage());
        }
    }

    public function showReject(): void
    {
        $this->showRejectForm = true;
    }

    public function reject(RejectReturnAction $action): void
    {
        $this->validate([
            'rejectReason' => 'required|string|min:3',
        ]);

        try {
            $updated = $action->execute(Auth::user(), $this->returnRequest, $this->rejectReason);
            $this->returnRequest = $updated->load(self::WITH_RELATIONS);
            $this->showRejectForm = false;
            session()->flash('success', 'Retur ditolak.');
        } catch (\RuntimeException $e) {
            $this->addError('decision', $e->getMessage());
        }
    }

    public function checkAvailability(PrepareReplacementPickupAction $action): void
    {
        $this->authorize('verify', $this->returnRequest);

        $updated = $action->execute($this->returnRequest);
        $this->returnRequest = $updated->load(self::WITH_RELATIONS);

        session()->flash('success', $this->returnRequest->status === ReturnStatus::ReadyForRepickup
            ? 'Stok penggantian tersedia. Siap diambil.'
            : 'Stok penggantian belum cukup. Permintaan pembelian penggantian telah disiapkan.');
    }

    public function completeRepickup(CompleteReplacementPickupAction $action): void
    {
        try {
            $updated = $action->execute(Auth::user(), $this->returnRequest);
            $this->returnRequest = $updated->load(self::WITH_RELATIONS);
            session()->flash('success', 'Penggantian retur selesai diserahkan.');
        } catch (\RuntimeException $e) {
            $this->addError('decision', $e->getMessage());
        }
    }

    public function render()
    {
        $canVerify = Gate::forUser(Auth::user())->allows('verify', $this->returnRequest)
            && $this->returnRequest->status === ReturnStatus::Submitted;

        $canSubmitForApproval = Gate::forUser(Auth::user())->allows('submitForApproval', $this->returnRequest)
            && $this->returnRequest->status === ReturnStatus::AdminVerified;

        $canApprove = Gate::forUser(Auth::user())->allows('approve', $this->returnRequest)
            && $this->returnRequest->status === ReturnStatus::WaitingApproval;

        $canSeeAttribution = Gate::forUser(Auth::user())->allows('verify', $this->returnRequest)
            || Gate::forUser(Auth::user())->allows('approve', $this->returnRequest);

        $canManageReplacement = Gate::forUser(Auth::user())->allows('verify', $this->returnRequest);

        $canCheckAvailability = $canManageReplacement
            && $this->returnRequest->status === ReturnStatus::ReplacementPending;

        $canCompleteRepickup = $canManageReplacement
            && $this->returnRequest->status === ReturnStatus::ReadyForRepickup;

        return view('livewire.returns.show', [
            'canVerify' => $canVerify,
            'canSubmitForApproval' => $canSubmitForApproval,
            'canApprove' => $canApprove,
            'canSeeAttribution' => $canSeeAttribution,
            'canManageReplacement' => $canManageReplacement,
            'canCheckAvailability' => $canCheckAvailability,
            'canCompleteRepickup' => $canCompleteRepickup,
        ]);
    }
}
