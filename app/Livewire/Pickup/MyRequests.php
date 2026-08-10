<?php

namespace App\Livewire\Pickup;

use App\Actions\Pickup\CancelPickupRequestAction;
use App\Models\PickupRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class MyRequests extends Component
{
    use WithPagination;

    public function cancelRequest($id, CancelPickupRequestAction $cancelAction)
    {
        $request = PickupRequest::where('user_id', Auth::id())->findOrFail($id);

        try {
            $cancelAction->execute(Auth::user(), $request, 'Dibatalkan oleh user');
            session()->flash('status', 'Request berhasil dibatalkan.');
        } catch (\Exception $e) {
            $this->addError('general', 'Gagal membatalkan: '.$e->getMessage());
        }
    }

    public function render()
    {
        $requests = PickupRequest::where('user_id', Auth::id())
            ->latest()
            ->paginate(10);

        return view('livewire.pickup.my-requests', [
            'requests' => $requests,
        ]);
    }
}
