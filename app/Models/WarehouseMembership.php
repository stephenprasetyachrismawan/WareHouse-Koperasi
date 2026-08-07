<?php

namespace App\Models;

use App\Enums\MembershipStatus;
use App\Enums\Permission;
use App\Enums\WarehouseRole;
use Database\Factories\WarehouseMembershipFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $warehouse_id
 * @property int $user_id
 * @property string|WarehouseRole $role
 * @property string|MembershipStatus $status
 * @property array<string>|null $permissions
 * @property Company|null $company
 * @property Warehouse|null $warehouse
 * @property User|null $user
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class WarehouseMembership extends Model
{
    /** @use HasFactory<WarehouseMembershipFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'user_id',
        'role',
        'status',
        'permissions',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (WarehouseMembership $membership) {
            if ($membership->warehouse_id && ! $membership->company_id) {
                $warehouse = Warehouse::find($membership->warehouse_id);
                if ($warehouse) {
                    $membership->company_id = $warehouse->company_id;
                }
            }
        });
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        $statusVal = $this->status instanceof MembershipStatus ? $this->status->value : (string) $this->status;

        return strtolower($statusVal) === 'active';
    }

    public function hasPermission(mixed $permission): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        $permValue = $permission instanceof Permission ? $permission->value : (string) $permission;

        if (is_array($this->permissions)) {
            return in_array($permValue, $this->permissions, true);
        }

        if (! $this->user) {
            return false;
        }

        setPermissionsTeamId($this->company_id);

        if ($this->user->hasPermissionTo($permValue)) {
            return true;
        }

        $roleVal = $this->role instanceof WarehouseRole ? $this->role->value : (string) $this->role;

        return $this->user->hasRole($roleVal);
    }
}
