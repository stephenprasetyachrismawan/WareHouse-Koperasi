<?php

namespace Database\Factories;

use App\Enums\ApprovalStatus;
use App\Models\Approval;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApprovalFactory extends Factory
{
    protected $model = Approval::class;

    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'approvable_type' => 'App\Models\PickupRequest',
            'approvable_id' => 1,
            'requested_by' => User::factory(),
            'approver_id' => null,
            'status' => ApprovalStatus::Approved,
            'reason' => null,
            'decided_at' => now(),
        ];
    }
}
