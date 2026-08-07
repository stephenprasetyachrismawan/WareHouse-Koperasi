<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Permission\Models\Role;

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

    public function role(mixed $role): static
    {
        $roleStr = is_object($role) && isset($role->value) ? (string) $role->value : (string) $role;

        return $this->state(fn (array $attributes) => [
            'role' => $roleStr,
        ])->afterCreating(function (WarehouseMembership $membership) use ($roleStr) {
            $warehouse = Warehouse::find($membership->warehouse_id);
            $companyId = $warehouse ? $warehouse->company_id : $membership->company_id;

            if ($membership->company_id !== $companyId) {
                $membership->company_id = $companyId;
                $membership->saveQuietly();
            }

            if ($membership->user) {
                setPermissionsTeamId($companyId);
                Role::findOrCreate($roleStr);
                $membership->user->assignRole($roleStr);
            }
        });
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }
}
