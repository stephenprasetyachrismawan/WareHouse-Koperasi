<?php

namespace Database\Factories;

use App\Enums\PurchaseRequestSource;
use App\Enums\PurchaseRequestStatus;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PurchaseRequestFactory extends Factory
{
    protected $model = PurchaseRequest::class;

    public function definition(): array
    {
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(8));

        return [
            'warehouse_id' => Warehouse::factory(),
            'request_number' => "PR-{$date}-{$random}",
            'source' => PurchaseRequestSource::ManualStaff->value,
            'status' => PurchaseRequestStatus::Draft->value,
            'created_by' => User::factory(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseRequestStatus::Draft->value,
        ]);
    }

    public function waitingApproval(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseRequestStatus::WaitingApproval->value,
            'submitted_at' => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseRequestStatus::Approved->value,
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseRequestStatus::Rejected->value,
            'rejected_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseRequestStatus::Cancelled->value,
            'cancelled_at' => now(),
        ]);
    }

    public function criticalStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => PurchaseRequestSource::CriticalStock->value,
        ]);
    }

    public function cooperativeBackorder(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => PurchaseRequestSource::CooperativeBackorder->value,
        ]);
    }
}
