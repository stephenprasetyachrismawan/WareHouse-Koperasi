<?php

namespace Database\Factories;

use App\Enums\DeliveryStatus;
use App\Models\DeviceToken;
use App\Models\InboxNotification;
use App\Models\NotificationDelivery;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationDelivery>
 */
class NotificationDeliveryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'inbox_notification_id' => InboxNotification::factory(),
            'device_token_id' => DeviceToken::factory(),
            'channel' => 'push',
            'status' => DeliveryStatus::Pending,
            'attempts' => 0,
        ];
    }

    public function sent(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeliveryStatus::Sent,
            'attempts' => 1,
            'provider_message_id' => 'fake-message-id-'.$this->faker->uuid(),
            'sent_at' => now(),
        ]);
    }

    public function failedPermanent(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeliveryStatus::FailedPermanent,
            'attempts' => 1,
            'last_error_code' => 'unregistered',
            'failed_at' => now(),
        ]);
    }
}
