<?php

namespace App\Models;

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
 * @property string $role
 * @property string $status
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
    ];

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
        return $this->status === 'active';
    }
}
