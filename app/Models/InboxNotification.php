<?php

namespace App\Models;

use App\Enums\NotificationType;
use Database\Factories\InboxNotificationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * The persistent, tenant-aware inbox — the authoritative source of truth
 * for operational notifications. Realtime/push (Phase 6.2) are delivery
 * conveniences layered on top of this record, never the other way around.
 */
class InboxNotification extends Model
{
    /** @use HasFactory<InboxNotificationFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'uuid',
        'recipient_id',
        'warehouse_id',
        'type',
        'title',
        'message',
        'subject_type',
        'subject_id',
        'action_route',
        'correlation_key',
        'metadata',
        'read_at',
    ];

    protected $casts = [
        'type' => NotificationType::class,
        'metadata' => 'array',
        'read_at' => 'datetime',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'subject_type', 'subject_id');
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function scopeForRecipient(Builder $query, int $userId): Builder
    {
        return $query->where('recipient_id', $userId);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }
}
