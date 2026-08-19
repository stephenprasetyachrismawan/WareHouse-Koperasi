<?php

namespace App\Livewire\Pickup;

use App\Actions\Pickup\ApprovePickupRequestAction;
use App\Actions\Pickup\RejectPickupRequestAction;
use App\Models\PickupRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ApprovalInbox extends Component
{
    use WithPagination;

    public string $rejectReason = '';

    public int|string|null $selectedRequestId = null;

    public bool $showRejectModal = false;

    public function approve(int|string $id, ApprovePickupRequestAction $action): void
    {
        $request = PickupRequest::findOrFail($id);
        $this->authorize('approve', $request);

        try {
            $action->execute(Auth::user(), $request);
            session()->flash('status', 'Request berhasil disetujui.');
        } catch (\Exception $e) {
            $this->addError('general', 'Gagal approve: '.$e->getMessage());
        }
    }

    public function openRejectModal(int|string $id): void
    {
        $this->selectedRequestId = $id;
        $this->rejectReason = '';
        $this->showRejectModal = true;
    }

    public function closeRejectModal(): void
    {
        $this->showRejectModal = false;
        $this->selectedRequestId = null;
    }

    public function confirmReject(RejectPickupRequestAction $action): void
    {
        $this->validate(['rejectReason' => 'required|string|min:3']);

        $request = PickupRequest::findOrFail($this->selectedRequestId);
        $this->authorize('approve', $request);

        try {
            $action->execute(Auth::user(), $request, $this->rejectReason);
            session()->flash('status', 'Request berhasil ditolak.');
            $this->closeRejectModal();
        } catch (\Exception $e) {
            $this->addError('general', 'Gagal menolak: '.$e->getMessage());
        }
    }

    public function render(): View
    {
        $requests = PickupRequest::whereIn('status', ['WAITING_APPROVAL', 'BACKORDERED'])
            ->with(['user', 'items.item'])
            ->latest()
            ->paginate(10);

        return view('livewire.pickup.approval-inbox', [
            'requests' => $requests,
        ]);
    }
}
