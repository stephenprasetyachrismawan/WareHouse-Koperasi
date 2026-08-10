<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $google_id
 * @property string $status
 * @property bool $is_super_admin
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'google_id',
        'password',
        'status',
        'is_super_admin',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
        ];
    }

    /**
     * @return HasMany<WarehouseMembership, $this>
     */
    public function warehouseMemberships(): HasMany
    {
        return $this->hasMany(WarehouseMembership::class);
    }

    /**
     * @return HasManyThrough<Company, WarehouseMembership, $this>
     */
    public function companies(): HasManyThrough
    {
        return $this->hasManyThrough(
            Company::class,
            WarehouseMembership::class,
            'user_id',
            'id',
            'id',
            'company_id'
        )->distinct();
    }

    /**
     * @return HasManyThrough<Warehouse, WarehouseMembership, $this>
     */
    public function warehouses(): HasManyThrough
    {
        return $this->hasManyThrough(
            Warehouse::class,
            WarehouseMembership::class,
            'user_id',
            'id',
            'id',
            'warehouse_id'
        );
    }

    public function activeMembership(): ?WarehouseMembership
    {
        /** @var WarehouseMembership|null $membership */
        $membership = $this->warehouseMemberships()
            ->where('status', 'active')
            ->first();

        return $membership;
    }

    public function activeCompany(): ?Company
    {
        /** @var Company|null $company */
        $company = $this->activeMembership()?->company;

        return $company;
    }

    public function activeWarehouse(): ?Warehouse
    {
        /** @var Warehouse|null $warehouse */
        $warehouse = $this->activeMembership()?->warehouse;

        return $warehouse;
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    public function isAppAdmin(?Company $company = null): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $activeCompany = $this->activeCompany();
        $companyId = $company ? $company->id : ($activeCompany ? $activeCompany->id : null);

        if (! $companyId) {
            return false;
        }

        return $this->warehouseMemberships()
            ->where('company_id', $companyId)
            ->where('role', 'app_admin')
            ->where('status', 'active')
            ->exists();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }
}
