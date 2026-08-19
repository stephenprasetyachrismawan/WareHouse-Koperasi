<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Stock;

use App\Actions\Inventory\RecordStockMovementAction;
use App\Domain\Inventory\ValueObjects\StockMovementInput;
use App\Enums\MovementType;
use App\Models\Item;
use App\Models\StockBalance;
use App\Models\StockTransaction;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

class Movement extends Component
{
    use AuthorizesRequests;

    public ?int $item_id = null;

    public string $movement_type = 'OPENING_BALANCE';

    public int $quantity = 1;

    public string $reason = '';

    public string $barcodeSearch = '';

    public ?Item $selectedItem = null;

    public function mount(): void
    {
        $this->authorize('recordIn', StockTransaction::class);
    }

    public function updatedItemId(): void
    {
        if ($this->item_id) {
            /** @var User $actor */
            $actor = Auth::user();
            $warehouseId = $actor->activeMembership()?->warehouse_id;

            $this->selectedItem = Item::with('stockBalance')
                ->where('warehouse_id', $warehouseId)
                ->find($this->item_id);
        } else {
            $this->selectedItem = null;
        }
    }

    public function searchBarcode(): void
    {
        if (empty($this->barcodeSearch)) {
            return;
        }

        /** @var User $actor */
        $actor = Auth::user();
        $warehouseId = $actor->activeMembership()?->warehouse_id;

        $item = Item::with('stockBalance')
            ->where('warehouse_id', $warehouseId)
            ->where(function ($q) {
                $q->where('code', $this->barcodeSearch)
                    ->orWhereHas('barcodes', fn ($bq) => $bq->where('barcode', $this->barcodeSearch));
            })
            ->first();

        if ($item) {
            $this->item_id = $item->id;
            $this->selectedItem = $item;
        } else {
            $this->addError('barcodeSearch', __('Barang dengan barcode/SKU tersebut tidak ditemukan.'));
        }
    }

    /**
     * @return array<string, string|array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'movement_type' => ['required', 'string', 'in:OPENING_BALANCE,MANUAL_ADJUSTMENT_IN,MANUAL_ADJUSTMENT_OUT'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function save(): mixed
    {
        $this->authorize('adjust', StockBalance::class);
        $validated = $this->validate();

        /** @var User $actor */
        $actor = Auth::user();
        $membership = $actor->activeMembership();

        if (! $membership) {
            abort(403);
        }

        $movementEnum = MovementType::from($validated['movement_type']);

        $idempotencyKey = 'manual-mvt-'.Str::uuid();

        $input = new StockMovementInput(
            warehouseId: $membership->warehouse_id,
            itemId: $validated['item_id'],
            movementType: $movementEnum,
            quantity: $validated['quantity'],
            performedBy: $actor->id,
            idempotencyKey: $idempotencyKey,
            reason: $validated['reason'],
        );

        $action = new RecordStockMovementAction;
        $transaction = $action->execute($input);

        session()->flash('status', __('Pergerakan stok berhasil dicatat. Saldo baru: ').number_format($transaction->balance_after));

        return redirect()->route('inventory.stock.overview');
    }

    public function render(): View
    {
        /** @var User $actor */
        $actor = Auth::user();
        $warehouseId = $actor->activeMembership()?->warehouse_id;

        $items = Item::where('warehouse_id', $warehouseId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'unit']);

        return view('livewire.inventory.stock.movement', [
            'items' => $items,
        ]);
    }
}
