<?php

namespace Database\Factories;

use App\Enums\NotificationType;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationPreference>
 */
class NotificationPreferenceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'warehouse_id' => Warehouse::factory(),
            'notification_type' => NotificationType::ApprovalRequired,
            'channel' => 'push',
            'enabled' => true,
        ];
    }

    public function disabled(): self
    {
        return $this->state(fn (array $attributes) => ['enabled' => false]);
    }
}
