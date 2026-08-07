<?php

namespace App\Models;

use App\Enums\MembershipStatus;
use App\Enums\Permission;
use App\Enums\WarehouseRole;
use Database\Factories\WarehouseMembershipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $warehouse_id
 * @property WarehouseRole $role
 * @property MembershipStatus $status
 * @property list<string>|null $permissions
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'warehouse_id', 'role', 'status', 'permissions'])]
class WarehouseMembership extends Model
{
    /** @use HasFactory<WarehouseMembershipFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'role' => WarehouseRole::class,
            'status' => MembershipStatus::class,
            'permissions' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function isActive(): bool
    {
        return $this->status === MembershipStatus::Active;
    }

    /**
     * Membership-level ACL narrows the role's default permission template;
     * a null `permissions` column means "use the full role template".
     */
    public function hasPermission(Permission $permission): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        if (! in_array($permission, $this->role->defaultPermissions(), true)) {
            return false;
        }

        if ($this->permissions === null) {
            return true;
        }

        return in_array($permission->value, $this->permissions, true);
    }
}
