<?php

namespace App\Models;

use App\Enums\PickupRequestSource;
use App\Enums\PickupRequestStatus;
use Database\Factories\PickupRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

/**
 * @property-read PickupRequestStatus $status
 * @property-read User|null $user
 */
class PickupRequest extends Model
{
    /** @use HasFactory<PickupRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'request_number',
        'user_id',
        'source',
        'status',
        'notes',
        'submitted_at',
        'approved_at',
        'ready_at',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'status' => PickupRequestStatus::class,
        'source' => PickupRequestSource::class,
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'ready_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (PickupRequest $pickupRequest) {
            if (empty($pickupRequest->uuid)) {
                $pickupRequest->uuid = (string) Str::uuid();
            }
        });
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

    /**
     * @return HasMany<PickupRequestItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PickupRequestItem::class);
    }

    /**
     * @return MorphMany<Approval, $this>
     */
    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    /**
     * @return HasOne<ReturnRequest, $this>
     */
    public function originatingReturn(): HasOne
    {
        return $this->hasOne(ReturnRequest::class, 'replacement_pickup_request_id');
    }

    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }

    /**
     * @param  Builder<PickupRequest>  $query
     * @return Builder<PickupRequest>
     */
    public function scopeForWarehouse(Builder $query, int $warehouseId): Builder
    {
        return $query->where('warehouse_id', $warehouseId);
    }
}
