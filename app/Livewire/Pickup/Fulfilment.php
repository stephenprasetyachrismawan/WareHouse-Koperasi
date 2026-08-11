<?php

namespace App\Livewire\Pickup;

use App\Actions\Pickup\FulfillPickupAction;
use App\Actions\Pickup\MarkPickupReadyAction;
use App\Actions\Returns\CompleteReplacementPickupAction;
use App\Enums\PickupRequestSource;
use App\Models\PickupRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Fulfilment extends Component
{
    public $searchRequestNumber = '';

    public ?PickupRequest $currentRequest = null;

    public function search()
    {
        $this->currentRequest = PickupRequest::where('request_number', trim($this->searchRequestNumber))
            ->whereIn('status', ['APPROVED', 'READY_FOR_PICKUP'])
            ->with(['user', 'items.item'])
            ->first();

        if (! $this->currentRequest) {
            $this->addError('searchRequestNumber', 'Request tidak ditemukan atau status tidak sesuai.');
        }
    }

    public function markReady(MarkPickupReadyAction $action)
    {
        if (! $this->currentRequest) {
            return;
        }

        $this->authorize('prepare', $this->currentRequest);

        try {
            $action->execute(Auth::user(), $this->currentRequest);
            $this->currentRequest->refresh();
            session()->flash('status', 'Status diubah menjadi Siap Diambil.');
        } catch (\Exception $e) {
            $this->addError('general', 'Gagal memproses: '.$e->getMessage());
        }
    }

    public function fulfill(FulfillPickupAction $action, CompleteReplacementPickupAction $completeReplacementAction)
    {
        if (! $this->currentRequest) {
            return;
        }

        $this->authorize('prepare', $this->currentRequest);

        try {
            if ($this->currentRequest->source === PickupRequestSource::ReturnReplacement) {
                $returnRequest = $this->currentRequest->originatingReturn()->firstOrFail();
                $completeReplacementAction->execute(Auth::user(), $returnRequest);
            } else {
                $action->execute(Auth::user(), $this->currentRequest);
            }

            $this->currentRequest->refresh();
            session()->flash('status', 'Request berhasil diselesaikan (Fulfilled).');
        } catch (\Exception $e) {
            $this->addError('general', 'Gagal menyelesaikan: '.$e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.pickup.fulfilment');
    }
}
