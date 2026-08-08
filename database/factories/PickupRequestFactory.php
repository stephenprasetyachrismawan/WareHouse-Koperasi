<?php

namespace Database\Factories;

use App\Enums\PickupRequestStatus;
use App\Models\PickupRequest;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PickupRequestFactory extends Factory
{
    protected $model = PickupRequest::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'warehouse_id' => Warehouse::factory(),
            'request_number' => 'PR-'.$this->faker->unique()->numerify('#####'),
            'user_id' => User::factory(),
            'status' => PickupRequestStatus::Draft,
            'notes' => $this->faker->sentence(),
        ];
    }

    public function submitted(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => PickupRequestStatus::Submitted,
            'submitted_at' => now(),
        ]);
    }

    public function approved(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => PickupRequestStatus::Approved,
            'submitted_at' => now()->subDays(2),
            'approved_at' => now(),
        ]);
    }

    public function rejected(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => PickupRequestStatus::Rejected,
            'submitted_at' => now()->subDays(2),
            'cancellation_reason' => 'Rejected by approver',
        ]);
    }

    public function readyForPickup(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => PickupRequestStatus::ReadyForPickup,
            'submitted_at' => now()->subDays(3),
            'approved_at' => now()->subDays(2),
            'ready_at' => now(),
        ]);
    }

    public function completed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => PickupRequestStatus::Completed,
            'submitted_at' => now()->subDays(4),
            'approved_at' => now()->subDays(3),
            'ready_at' => now()->subDays(1),
            'completed_at' => now(),
        ]);
    }

    public function cancelled(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => PickupRequestStatus::Cancelled,
            'submitted_at' => now()->subDays(2),
            'cancelled_at' => now(),
            'cancellation_reason' => 'Cancelled by user',
        ]);
    }
}
