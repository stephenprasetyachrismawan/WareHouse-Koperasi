<?php

namespace App\Domain\Notifications\Support;

use App\Enums\Permission;
use App\Models\DeviceToken;
use App\Models\InboxNotification;
use App\Models\NotificationPreference;
use App\Models\User;

/**
 * Push is a narrow, opt-in convenience layered on top of the persistent
 * Inbox — v1 only ever pushes APPROVAL_REQUIRED / CANCELLATION_REQUIRED to
 * the Kepala Gudang who can actually act on them. Every other notification
 * type stays Inbox + realtime only. This is re-evaluated at delivery time
 * (not just at notification-creation time), since a queued job may run
 * after the recipient's role, membership, or consent has changed.
 */
class PushEligibilityPolicy
{
    /**
     * @var array<string, Permission>
     */
    private const REQUIRED_PERMISSION = [
        'APPROVAL_REQUIRED' => Permission::PurchaseRequestApprove,
        'CANCELLATION_REQUIRED' => Permission::PurchaseRequestCancel,
    ];

    public function isEligible(InboxNotification $notification): bool
    {
        $requiredPermission = self::REQUIRED_PERMISSION[$notification->type->value] ?? null;

        if ($requiredPermission === null) {
            return false;
        }

        $recipient = $notification->recipient;

        if (! $recipient || ! $recipient->isActive()) {
            return false;
        }

        if (! $this->hasActiveConsentedDevice($recipient)) {
            return false;
        }

        if ($notification->warehouse_id !== null && ! $this->holdsRequiredPermission($recipient, $notification->warehouse_id, $requiredPermission)) {
            return false;
        }

        return $this->preferenceAllows($recipient, $notification);
    }

    private function hasActiveConsentedDevice(User $recipient): bool
    {
        return DeviceToken::forUser($recipient->id)->active()->whereNotNull('consented_at')->exists();
    }

    private function holdsRequiredPermission(User $recipient, int $warehouseId, Permission $permission): bool
    {
        $membership = $recipient->warehouseMemberships()
            ->where('warehouse_id', $warehouseId)
            ->where('status', 'active')
            ->first();

        return $membership !== null && $membership->hasPermission($permission);
    }

    private function preferenceAllows(User $recipient, InboxNotification $notification): bool
    {
        $preference = NotificationPreference::where('user_id', $recipient->id)
            ->where('channel', 'push')
            ->where(fn ($query) => $query->where('warehouse_id', $notification->warehouse_id)->orWhereNull('warehouse_id'))
            ->where(fn ($query) => $query->where('notification_type', $notification->type->value)->orWhereNull('notification_type'))
            ->orderByRaw('warehouse_id is null')
            ->orderByRaw('notification_type is null')
            ->first();

        return $preference?->enabled ?? true;
    }
}
