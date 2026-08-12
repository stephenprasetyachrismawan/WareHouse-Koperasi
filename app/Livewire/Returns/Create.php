<?php

namespace App\Livewire\Returns;

use App\Actions\Returns\CreateReturnAction;
use App\Domain\Returns\Queries\EligibleReturnItemsQuery;
use App\Domain\Returns\ValueObjects\CreateReturnInput;
use App\Enums\ReturnReasonCode;
use App\Models\PickupRequestItem;
use App\Models\ReturnRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class Create extends Component
{
    use WithFileUploads;

    public ?int $selectedPickupRequestItemId = null;

    public int $quantity = 1;

    public string $reasonCode = 'DAMAGED';

    public string $reasonNotes = '';

    public $photo = null;

    public bool $reviewing = false;

    public function mount(): void
    {
        $this->authorize('create', ReturnRequest::class);
    }

    public function selectItem(int $pickupRequestItemId): void
    {
        $this->selectedPickupRequestItemId = $pickupRequestItemId;
        $this->quantity = 1;
        $this->reviewing = false;
    }

    public function goToReview(): void
    {
        $this->validate([
            'selectedPickupRequestItemId' => 'required|integer',
            'quantity' => 'required|integer|min:1',
            'reasonCode' => 'required|in:DAMAGED,DEFECTIVE,WRONG_ITEM,OTHER',
            'reasonNotes' => $this->reasonCode === 'OTHER' ? 'required|string|min:3' : 'nullable|string',
            'photo' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        $this->reviewing = true;
    }

    public function backToForm(): void
    {
        $this->reviewing = false;
    }

    public function submit(CreateReturnAction $action): mixed
    {
        $this->authorize('create', ReturnRequest::class);

        $pickupRequestItem = PickupRequestItem::with('pickupRequest')->findOrFail($this->selectedPickupRequestItemId);

        $warehouseId = Auth::user()->activeWarehouse()?->id;

        $evidencePath = $this->photo->store("return-evidence/{$warehouseId}", 'private');
        $evidenceMime = $this->photo->getMimeType();

        $returnRequest = $action->execute(Auth::user(), new CreateReturnInput(
            warehouseId: $warehouseId,
            pickupRequestId: $pickupRequestItem->pickup_request_id,
            pickupRequestItemId: $pickupRequestItem->id,
            returnQuantity: (int) $this->quantity,
            reasonCode: ReturnReasonCode::from($this->reasonCode),
            reasonNotes: $this->reasonNotes ?: null,
            evidencePath: $evidencePath,
            evidenceMime: $evidenceMime,
        ));

        session()->flash('success', "Retur berhasil diajukan. Nomor retur: {$returnRequest->return_number}");

        return redirect()->route('returns.my-returns');
    }

    public function render(EligibleReturnItemsQuery $eligibleQuery)
    {
        $warehouseId = Auth::user()->activeWarehouse()?->id;

        $eligibleItems = $eligibleQuery->execute(Auth::user(), $warehouseId);

        $selectedItem = $this->selectedPickupRequestItemId
            ? $eligibleItems->firstWhere('id', $this->selectedPickupRequestItemId)
            : null;

        return view('livewire.returns.create', [
            'eligibleItems' => $eligibleItems,
            'selectedItem' => $selectedItem,
            'reasons' => ReturnReasonCode::cases(),
        ]);
    }
}
