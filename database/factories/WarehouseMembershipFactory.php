<?php

namespace Database\Factories;

use App\Enums\MembershipStatus;
use App\Enums\WarehouseRole;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WarehouseMembership>
 */
class WarehouseMembershipFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'warehouse_id' => Warehouse::factory(),
            'role' => WarehouseRole::StaffAdmin,
            'status' => MembershipStatus::Active,
            'permissions' => null,
        ];
    }

    public function role(WarehouseRole $role): static
    {
        return $this->state(fn () => ['role' => $role]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => MembershipStatus::Suspended]);
    }
}
