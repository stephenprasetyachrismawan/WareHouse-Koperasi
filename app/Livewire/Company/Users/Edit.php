<?php

namespace App\Livewire\Company\Users;

use App\Actions\UserManagement\UpdateCompanyUserAction;
use App\Enums\WarehouseRole;
use App\Models\User;
use App\Models\WarehouseMembership;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Edit extends Component
{
    public User $user;

    public string $name = '';

    public string $email = '';

    public string $role = 'staff_admin';

    public function mount(User $user): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        Gate::forUser($actor)->authorize('update', $user);

        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;

        $company = $actor->activeCompany();

        if ($company) {
            $membership = WarehouseMembership::where('user_id', $user->id)
                ->where('company_id', $company->id)
                ->first();

            if ($membership) {
                $roleVal = $membership->role;
                $this->role = $roleVal instanceof WarehouseRole ? $roleVal->value : (string) $roleVal;
            }
        }
    }

    public function save(UpdateCompanyUserAction $action): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'role' => ['required', 'string'],
        ]);

        /** @var User $actor */
        $actor = Auth::user();

        $action->execute($actor, $this->user, [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
        ]);

        session()->flash('status', 'Pengguna berhasil diperbarui.');

        $this->redirect(route('company.users.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.company.users.edit', [
            'roles' => [
                'app_admin' => 'App Admin',
                'kepala_gudang' => 'Kepala Gudang',
                'staff_admin' => 'Staff Admin',
                'purchasing' => 'Purchasing',
                'koperasi' => 'Koperasi',
            ],
        ]);
    }
}
