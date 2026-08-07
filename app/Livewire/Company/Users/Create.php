<?php

namespace App\Livewire\Company\Users;

use App\Actions\UserManagement\CreateCompanyUserAction;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Create extends Component
{
    use AuthorizesRequests;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $role = 'staff_admin';

    public function save(CreateCompanyUserAction $action): mixed
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', 'string', Rule::in(CreateCompanyUserAction::ALLOWED_ROLES)],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        /** @var User $actor */
        $actor = Auth::user();

        try {
            $action->execute($actor, [
                'name' => $this->name,
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'role' => $this->role,
            ]);
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->getMessageBag());

            return null;
        }

        session()->flash('status', __('Internal user created successfully.'));

        return $this->redirect(route('company.users.index'), navigate: true);
    }

    public function render(): View
    {
        $this->authorize('create', User::class);

        return view('livewire.company.users.create', [
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
