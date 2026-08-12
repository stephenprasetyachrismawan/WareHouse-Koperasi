<?php

namespace App\Jobs;

use App\Domain\Notifications\Push\PushNotificationSender;
use App\Domain\Notifications\Push\PushPayload;
use App\Domain\Notifications\Support\PushEligibilityPolicy;
use App\Enums\DeliveryStatus;
use App\Models\DeviceToken;
use App\Models\InboxNotification;
use App\Models\NotificationDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Throwable;

/**
 * Takes only IDs, never a serialized model graph — everything is reloaded
 * fresh here, so the job always acts on the current, authoritative state
 * (recipient role, membership, consent, preferences) rather than whatever
 * was true when it was dispatched. There is no session to inherit: tenant
 * context (the warehouse) always comes from the reloaded InboxNotification
 * itself, never anything ambient to the worker process.
 */
class DeliverPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /**
     * @var array<string, array{title: string, body: string}>
     */
    private const PUSH_TEXT = [
        'APPROVAL_REQUIRED' => [
            'title' => 'Persetujuan Diperlukan',
            'body' => 'Ada permintaan yang memerlukan keputusan Anda.',
        ],
        'CANCELLATION_REQUIRED' => [
            'title' => 'Permintaan Pembatalan',
            'body' => 'Ada permintaan pembatalan yang menunggu keputusan Anda.',
        ],
    ];

    public function __construct(
        public readonly int $inboxNotificationId,
        public readonly int $deviceTokenId,
    ) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30, 90, 300];
    }

    public function handle(PushEligibilityPolicy $eligibility, PushNotificationSender $sender): void
    {
        $notification = InboxNotification::find($this->inboxNotificationId);
        $deviceToken = DeviceToken::find($this->deviceTokenId);

        if (! $notification || ! $deviceToken || ! $deviceToken->isActive()) {
            return;
        }

        if (! $eligibility->isEligible($notification)) {
            return;
        }

        $delivery = NotificationDelivery::firstOrCreate(
            [
                'inbox_notification_id' => $notification->id,
                'device_token_id' => $deviceToken->id,
                'channel' => 'push',
            ],
            ['status' => DeliveryStatus::Pending, 'attempts' => 0]
        );

        // Already resolved (delivered, or permanently given up) — never
        // send twice and never retry a permanent failure.
        if ($delivery->status !== DeliveryStatus::Pending && $delivery->status !== DeliveryStatus::FailedRetryable) {
            return;
        }

        $delivery->increment('attempts');

        $result = $sender->send($deviceToken, $this->buildPayload($notification));

        if ($result->status === DeliveryStatus::Sent) {
            $delivery->update([
                'status' => DeliveryStatus::Sent,
                'provider_message_id' => $result->providerMessageId,
                'sent_at' => now(),
            ]);
            $deviceToken->update(['last_used_at' => now()]);

            return;
        }

        if ($result->status === DeliveryStatus::FailedPermanent) {
            $delivery->update([
                'status' => DeliveryStatus::FailedPermanent,
                'last_error_code' => $result->errorCode,
                'failed_at' => now(),
            ]);
            // A permanent failure means the token itself is invalid — revoke
            // only this device, never the user's other registered devices.
            $deviceToken->update(['revoked_at' => now()]);

            return;
        }

        $delivery->update([
            'status' => DeliveryStatus::FailedRetryable,
            'last_error_code' => $result->errorCode,
            'failed_at' => now(),
        ]);

        // Let Laravel's queue retry/backoff mechanism handle the retry;
        // once $tries is exhausted, failed() below gives up permanently.
        throw new RuntimeException("Retryable push delivery failure: {$result->errorCode}");
    }

    public function failed(?Throwable $exception): void
    {
        NotificationDelivery::where([
            'inbox_notification_id' => $this->inboxNotificationId,
            'device_token_id' => $this->deviceTokenId,
            'channel' => 'push',
        ])->update([
            'status' => DeliveryStatus::FailedPermanent,
            'failed_at' => now(),
        ]);
    }

    private function buildPayload(InboxNotification $notification): PushPayload
    {
        $text = self::PUSH_TEXT[$notification->type->value] ?? [
            'title' => 'Notifikasi Baru',
            'body' => 'Anda memiliki notifikasi baru.',
        ];

        return new PushPayload(
            notificationUuid: $notification->uuid,
            title: $text['title'],
            body: $text['body'],
        );
    }
}
