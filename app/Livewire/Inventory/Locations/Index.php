<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Locations;

use App\Models\User;
use App\Models\WarehouseLocation;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    public bool $showCreateModal = false;

    public string $code = '';

    public string $name = '';

    public string $description = '';

    public function saveLocation(): void
    {
        $this->authorize('create', WarehouseLocation::class);

        $validated = $this->validate([
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        /** @var User $actor */
        $actor = Auth::user();
        $warehouseId = $actor->activeMembership()?->warehouse_id;

        WarehouseLocation::create([
            'uuid' => (string) Str::uuid(),
            'warehouse_id' => $warehouseId,
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ]);

        $this->reset(['code', 'name', 'description', 'showCreateModal']);
        session()->flash('status', __('Lokasi rak/area gudang berhasil ditambahkan.'));
    }

    public function render(): View
    {
        /** @var User $actor */
        $actor = Auth::user();
        $this->authorize('viewAny', WarehouseLocation::class);

        $warehouseId = $actor->activeMembership()?->warehouse_id;

        $locations = WarehouseLocation::where('warehouse_id', $warehouseId)
            ->when($this->search !== '', function ($q) {
                $q->where('code', 'like', '%'.$this->search.'%')
                    ->orWhere('name', 'like', '%'.$this->search.'%');
            })
            ->latest()
            ->paginate(10);

        return view('livewire.inventory.locations.index', [
            'locations' => $locations,
        ]);
    }
}
