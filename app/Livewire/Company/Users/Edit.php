<?php

namespace App\Livewire\Company\Users;

use App\Actions\UserManagement\UpdateCompanyUserAction;
use App\Models\User;
use App\Models\WarehouseMembership;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Edit extends Component
{
    use AuthorizesRequests;

    public User $user;

    public string $name = '';

    public string $email = '';

    public string $role = 'staff_admin';

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;

        /** @var User $actor */
        $actor = Auth::user();

        $membership = WarehouseMembership::where('user_id', $user->id)
            ->where('company_id', $actor->activeCompany()?->id)
            ->first();

        if ($membership) {
            $this->role = $membership->role;
        }
    }

    public function save(UpdateCompanyUserAction $action): mixed
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user->id)],
            'role' => ['required', 'string', Rule::in(UpdateCompanyUserAction::ALLOWED_ROLES)],
        ]);

        /** @var User $actor */
        $actor = Auth::user();

        try {
            $action->execute($actor, $this->user, [
                'name' => $this->name,
                'email' => $this->email,
                'role' => $this->role,
            ]);
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->getMessageBag());

            return null;
        }

        session()->flash('status', __('User details updated successfully.'));

        return $this->redirect(route('company.users.index'), navigate: true);
    }

    public function render(): View
    {
        $this->authorize('update', $this->user);

        return view('livewire.company.users.edit', [
            'roles' => [
                'app_admin' => 'Administrator (App Admin)',
                'kepala_gudang' => 'Kepala Gudang',
                'staff_admin' => 'Staff Admin Gudang',
                'purchasing' => 'Purchasing',
                'koperasi' => 'Koperasi',
            ],
        ]);
    }
}
