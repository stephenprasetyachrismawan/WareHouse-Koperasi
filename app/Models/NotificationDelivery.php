<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use Database\Factories\NotificationDeliveryFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per (InboxNotification, DeviceToken, channel) — the idempotency
 * unit for push delivery. Never the source of truth for notification
 * content, only a tracking record of a delivery attempt against the
 * already-persisted InboxNotification.
 */
class NotificationDelivery extends Model
{
    /** @use HasFactory<NotificationDeliveryFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'uuid',
        'inbox_notification_id',
        'device_token_id',
        'channel',
        'status',
        'attempts',
        'provider_message_id',
        'last_error_code',
        'sent_at',
        'failed_at',
    ];

    protected $casts = [
        'status' => DeliveryStatus::class,
        'attempts' => 'integer',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function inboxNotification(): BelongsTo
    {
        return $this->belongsTo(InboxNotification::class);
    }

    public function deviceToken(): BelongsTo
    {
        return $this->belongsTo(DeviceToken::class);
    }
}
