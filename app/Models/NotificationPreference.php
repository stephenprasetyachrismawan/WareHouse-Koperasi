<?php

namespace App\Models;

use App\Enums\NotificationType;
use Database\Factories\NotificationPreferenceFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An explicit user override of a delivery default. Absence of a row is not
 * "unconfigured" — it means the default applies (Inbox mandatory, realtime
 * automatic, push enabled only for eligible categories once a device is
 * consented). Rows are only ever written when a user actively changes
 * something, never pre-created per user/warehouse/type/channel combination.
 */
class NotificationPreference extends Model
{
    /** @use HasFactory<NotificationPreferenceFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'uuid',
        'user_id',
        'warehouse_id',
        'notification_type',
        'channel',
        'enabled',
    ];

    protected $casts = [
        'notification_type' => NotificationType::class,
        'enabled' => 'boolean',
    ];

    /**
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
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
}
