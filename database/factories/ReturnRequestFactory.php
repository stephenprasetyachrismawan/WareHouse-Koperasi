<?php

namespace Database\Factories;

use App\Enums\ReturnReasonCode;
use App\Enums\ReturnStatus;
use App\Models\PickupRequest;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReturnRequestFactory extends Factory
{
    protected $model = ReturnRequest::class;

    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'cooperative_membership_id' => WarehouseMembership::factory(),
            'pickup_request_id' => PickupRequest::factory(),
            'return_number' => 'RET-'.now()->format('Ymd').'-'.$this->faker->unique()->numerify('########'),
            'status' => ReturnStatus::Submitted,
            'reason_code' => ReturnReasonCode::Damaged,
            'reason_notes' => null,
            'submitted_by' => User::factory(),
            'submitted_at' => now(),
            'version' => 1,
        ];
    }

    public function submitted(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReturnStatus::Submitted,
            'submitted_at' => now(),
            'verified_by' => null,
            'verified_at' => null,
        ]);
    }

    public function adminVerified(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReturnStatus::AdminVerified,
            'submitted_at' => now()->subHour(),
            'verified_by' => User::factory(),
            'verified_at' => now(),
            'verification_notes' => 'Barang sesuai laporan Koperasi.',
        ]);
    }

    public function waitingApproval(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReturnStatus::WaitingApproval,
            'submitted_at' => now()->subHours(2),
            'verified_by' => User::factory(),
            'verified_at' => now()->subHour(),
            'verification_notes' => 'Barang sesuai laporan Koperasi.',
            'waiting_approval_at' => now(),
        ]);
    }
}
