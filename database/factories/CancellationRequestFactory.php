<?php

namespace Database\Factories;

use App\Enums\CancellationRequestStatus;
use App\Models\CancellationRequest;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class CancellationRequestFactory extends Factory
{
    protected $model = CancellationRequest::class;

    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'purchase_request_id' => PurchaseRequest::factory(),
            'requested_by' => User::factory(),
            'reason' => $this->faker->sentence,
            'status' => CancellationRequestStatus::Pending->value,
        ];
    }
}
