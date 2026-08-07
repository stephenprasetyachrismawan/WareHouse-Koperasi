<?php

namespace App\Livewire\Company\Users;

use App\Actions\UserManagement\ToggleCompanyUserStatusAction;
use App\Models\User;
use App\Models\WarehouseMembership;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $userId): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        $target = User::findOrFail($userId);

        $action = new ToggleCompanyUserStatusAction;
        $action->execute($actor, $target);

        session()->flash('status', __('User status updated successfully.'));
    }

    public function render(): View
    {
        /** @var User $actor */
        $actor = Auth::user();

        $this->authorize('viewAny', User::class);

        $company = $actor->activeCompany();

        $memberships = WarehouseMembership::with(['user', 'warehouse'])
            ->where('company_id', $company?->id)
            ->when($this->search !== '', function ($q) {
                $q->whereHas('user', function ($uq) {
                    $uq->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                });
            })
            ->latest()
            ->paginate(10);

        return view('livewire.company.users.index', [
            'memberships' => $memberships,
            'company' => $company,
        ]);
    }
}
