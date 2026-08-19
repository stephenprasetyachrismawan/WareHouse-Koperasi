<?php

namespace App\Models;

use Database\Factories\DeviceTokenFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A registered push-delivery target for one user's device/browser. The raw
 * provider token is encrypted at rest and never exposed; token_fingerprint
 * (a deterministic hash) is what identifies "the same device again" since
 * Laravel's encryption is randomized and cannot be looked up directly.
 */
class DeviceToken extends Model
{
    /** @use HasFactory<DeviceTokenFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'uuid',
        'user_id',
        'provider',
        'platform',
        'encrypted_token',
        'token_fingerprint',
        'device_name',
        'browser',
        'user_agent_summary',
        'consented_at',
        'last_seen_at',
        'last_used_at',
        'revoked_at',
    ];

    protected $hidden = [
        'encrypted_token',
    ];

    protected $casts = [
        'encrypted_token' => 'encrypted',
        'consented_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
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

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }

    /**
     * @param  Builder<DeviceToken>  $query
     * @return Builder<DeviceToken>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at');
    }

    /**
     * @param  Builder<DeviceToken>  $query
     * @return Builder<DeviceToken>
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
