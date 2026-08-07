<?php

namespace App\Livewire\Company\Users;

use App\Actions\UserManagement\CreateCompanyUserAction;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Create extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $role = 'staff_admin';

    public function mount(): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        Gate::forUser($actor)->authorize('create', User::class);
    }

    public function save(CreateCompanyUserAction $action): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string'],
        ]);

        /** @var User $actor */
        $actor = Auth::user();

        $action->execute($actor, [
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'role' => $this->role,
        ]);

        session()->flash('status', 'Pengguna baru berhasil ditambahkan.');

        $this->redirect(route('company.users.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.company.users.create', [
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
