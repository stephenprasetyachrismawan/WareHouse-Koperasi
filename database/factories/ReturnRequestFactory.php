<?php

namespace Database\Factories;

use App\Actions\Returns\DetermineReturnFaultAction;
use App\Enums\ReturnFaultAttribution;
use App\Enums\ReturnReasonCode;
use App\Enums\ReturnStatus;
use App\Models\PickupRequest;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReturnRequest>
 */
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

    public function approved(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReturnStatus::Approved,
            'submitted_at' => now()->subHours(3),
            'verified_by' => User::factory(),
            'verified_at' => now()->subHours(2),
            'verification_notes' => 'Barang sesuai laporan Koperasi.',
            'waiting_approval_at' => now()->subHour(),
            'approved_by' => User::factory(),
            'approved_at' => now(),
            'fault_attribution' => ReturnFaultAttribution::Supplier,
            'fault_rule_version' => DetermineReturnFaultAction::RULE_VERSION,
        ]);
    }

    public function rejected(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReturnStatus::Rejected,
            'submitted_at' => now()->subHours(3),
            'verified_by' => User::factory(),
            'verified_at' => now()->subHours(2),
            'verification_notes' => 'Barang sesuai laporan Koperasi.',
            'waiting_approval_at' => now()->subHour(),
            'rejected_by' => User::factory(),
            'rejected_at' => now(),
            'decision_notes' => 'Bukti foto tidak meyakinkan.',
        ]);
    }

    public function replacementPending(): self
    {
        return $this->approved()->state(fn (array $attributes) => [
            'status' => ReturnStatus::ReplacementPending,
            'disposed_at' => now(),
        ]);
    }

    public function readyForRepickup(): self
    {
        return $this->replacementPending()->state(fn (array $attributes) => [
            'status' => ReturnStatus::ReadyForRepickup,
        ]);
    }

    public function completed(): self
    {
        return $this->replacementPending()->state(fn (array $attributes) => [
            'status' => ReturnStatus::Completed,
        ]);
    }

    public function warehouseAttributed(): self
    {
        return $this->state(fn (array $attributes) => [
            'fault_attribution' => ReturnFaultAttribution::Warehouse,
            'fault_rule_version' => DetermineReturnFaultAction::RULE_VERSION,
        ]);
    }

    public function supplierAttributed(): self
    {
        return $this->state(fn (array $attributes) => [
            'fault_attribution' => ReturnFaultAttribution::Supplier,
            'fault_rule_version' => DetermineReturnFaultAction::RULE_VERSION,
        ]);
    }
}
