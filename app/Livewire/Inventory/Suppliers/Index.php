<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Suppliers;

use App\Models\Supplier;
use App\Models\User;
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

    public string $name = '';

    public string $contact_name = '';

    public string $email = '';

    public string $phone = '';

    public string $address = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function saveSupplier(): void
    {
        $this->authorize('create', Supplier::class);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        /** @var User $actor */
        $actor = Auth::user();
        $warehouseId = $actor->activeMembership()?->warehouse_id;

        Supplier::create([
            'uuid' => (string) Str::uuid(),
            'warehouse_id' => $warehouseId,
            'name' => $validated['name'],
            'contact_name' => $validated['contact_name'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'is_active' => true,
        ]);

        $this->reset(['name', 'contact_name', 'email', 'phone', 'address', 'showCreateModal']);
        session()->flash('status', __('Supplier berhasil ditambahkan.'));
    }

    public function render(): View
    {
        /** @var User $actor */
        $actor = Auth::user();
        $this->authorize('viewAny', Supplier::class);

        $warehouseId = $actor->activeMembership()?->warehouse_id;

        $suppliers = Supplier::where('warehouse_id', $warehouseId)
            ->when($this->search !== '', function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('contact_name', 'like', '%'.$this->search.'%');
            })
            ->latest()
            ->paginate(10);

        return view('livewire.inventory.suppliers.index', [
            'suppliers' => $suppliers,
        ]);
    }
}
