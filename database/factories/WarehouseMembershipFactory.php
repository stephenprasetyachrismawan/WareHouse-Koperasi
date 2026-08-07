<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WarehouseMembership>
 */
class WarehouseMembershipFactory extends Factory
{
    protected $model = WarehouseMembership::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'warehouse_id' => Warehouse::factory(),
            'user_id' => User::factory(),
            'role' => 'staff_admin',
            'status' => 'active',
        ];
    }
}
