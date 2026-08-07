<?php

namespace App\Models;

use Database\Factories\WarehouseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $company_id
 * @property string $name
 * @property string $code
 * @property string|null $address
 * @property string $timezone
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Warehouse extends Model
{
    /** @use HasFactory<WarehouseFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'name',
        'code',
        'address',
        'timezone',
        'status',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Warehouse $warehouse) {
            if (empty($warehouse->uuid)) {
                $warehouse->uuid = (string) Str::uuid();
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
     * @return HasMany<WarehouseMembership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(WarehouseMembership::class);
    }

    /**
     * @return HasManyThrough<User, WarehouseMembership, $this>
     */
    public function users(): HasManyThrough
    {
        return $this->hasManyThrough(
            User::class,
            WarehouseMembership::class,
            'warehouse_id',
            'id',
            'id',
            'user_id'
        );
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * @return HasMany<Item, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    /**
     * @return HasMany<WarehouseLocation, $this>
     */
    public function locations(): HasMany
    {
        return $this->hasMany(WarehouseLocation::class);
    }

    /**
     * @return HasMany<Supplier, $this>
     */
    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    /**
     * @return HasMany<StockBalance, $this>
     */
    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    /**
     * @return HasMany<StockTransaction, $this>
     */
    public function stockTransactions(): HasMany
    {
        return $this->hasMany(StockTransaction::class);
    }
}
