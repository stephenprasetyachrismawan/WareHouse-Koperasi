<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any users in their active company.
     */
    public function viewAny(User $actor): bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        $company = $actor->activeCompany();

        if (! $company || ! $company->isActive()) {
            return false;
        }

        setPermissionsTeamId($company->id);

        return $actor->hasPermissionTo('users.view');
    }

    /**
     * Determine whether the user can view the target user.
     */
    public function view(User $actor, User $target): bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        $company = $actor->activeCompany();

        if (! $company || ! $company->isActive()) {
            return false;
        }

        // Must belong to the same company
        $sharedCompany = $actor->companies()
            ->where('companies.id', $company->id)
            ->whereHas('users', fn ($q) => $q->where('users.id', $target->id))
            ->exists();

        if (! $sharedCompany) {
            return false;
        }

        setPermissionsTeamId($company->id);

        return $actor->hasPermissionTo('users.view');
    }

    /**
     * Determine whether the user can create internal users.
     */
    public function create(User $actor): bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        $company = $actor->activeCompany();

        if (! $company || ! $company->isActive()) {
            return false;
        }

        setPermissionsTeamId($company->id);

        return $actor->hasPermissionTo('users.create');
    }

    /**
     * Determine whether the user can update the target user.
     */
    public function update(User $actor, User $target): bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        $company = $actor->activeCompany();

        if (! $company || ! $company->isActive()) {
            return false;
        }

        // Must belong to the same company
        $sharedCompany = $actor->companies()
            ->where('companies.id', $company->id)
            ->whereHas('users', fn ($q) => $q->where('users.id', $target->id))
            ->exists();

        if (! $sharedCompany) {
            return false;
        }

        setPermissionsTeamId($company->id);

        return $actor->hasPermissionTo('users.update');
    }

    /**
     * Determine whether the user can delete the target user.
     */
    public function delete(User $actor, User $target): bool
    {
        if ($actor->id === $target->id) {
            return false;
        }

        if ($actor->isSuperAdmin()) {
            return true;
        }

        $company = $actor->activeCompany();

        if (! $company || ! $company->isActive()) {
            return false;
        }

        // Must belong to the same company
        $sharedCompany = $actor->companies()
            ->where('companies.id', $company->id)
            ->whereHas('users', fn ($q) => $q->where('users.id', $target->id))
            ->exists();

        if (! $sharedCompany) {
            return false;
        }

        setPermissionsTeamId($company->id);

        return $actor->hasPermissionTo('users.delete');
    }

    /**
     * Determine whether the user can assign a role to the target user.
     */
    public function assignRole(User $actor, User $target, string $role): bool
    {
        if ($role === 'super_admin' && ! $actor->isSuperAdmin()) {
            return false;
        }

        return $this->update($actor, $target);
    }

    /**
     * Determine whether the user can toggle the status of the target user.
     */
    public function toggleStatus(User $actor, User $target): bool
    {
        if ($actor->id === $target->id) {
            return false;
        }

        return $this->update($actor, $target);
    }
}
