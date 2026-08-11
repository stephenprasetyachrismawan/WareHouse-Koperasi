<?php

namespace App\Models;

use App\Enums\PurchaseRequestSource;
use App\Enums\PurchaseRequestStatus;
use App\Enums\PurchaseRequestUrgency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PurchaseRequest extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'warehouse_id',
        'request_number',
        'source',
        'urgency',
        'status',
        'created_by',
        'notes',
        'pickup_request_id',
        'return_request_id',
        'is_duplicate_override',
        'duplicate_override_reason',
        'duplicate_overridden_by',
        'duplicate_overridden_at',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'source' => PurchaseRequestSource::class,
        'urgency' => PurchaseRequestUrgency::class,
        'status' => PurchaseRequestStatus::class,
        'is_duplicate_override' => 'boolean',
        'duplicate_overridden_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function uniqueIds()
    {
        return ['uuid'];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequestItem::class);
    }

    public function pickupRequest(): BelongsTo
    {
        return $this->belongsTo(PickupRequest::class);
    }

    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class);
    }

    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    public function cancellationRequests(): HasMany
    {
        return $this->hasMany(CancellationRequest::class);
    }

    public function scopeForWarehouse(Builder $query, int $warehouseId): void
    {
        $query->where('warehouse_id', $warehouseId);
    }

    public function scopeInProgress(Builder $query): void
    {
        $query->whereNotIn('status', [
            PurchaseRequestStatus::Completed,
            PurchaseRequestStatus::Rejected,
            PurchaseRequestStatus::Cancelled,
        ]);
    }

    public function scopeApproved(Builder $query): void
    {
        $query->where('status', PurchaseRequestStatus::Approved);
    }

    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }

    public function isInProgress(): bool
    {
        return $this->status->isInProgress();
    }
}
