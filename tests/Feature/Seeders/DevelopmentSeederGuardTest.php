<?php

namespace Tests\Feature\Seeders;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\DevelopmentSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class DevelopmentSeederGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_is_skipped_outside_local_testing_without_explicit_opt_in(): void
    {
        $this->app['env'] = 'production';
        Config::set('app.allow_demo_seeding', false);

        $this->artisan('db:seed', ['--class' => DevelopmentSeeder::class, '--force' => true]);

        $this->assertSame(0, Company::count());
        $this->assertSame(0, User::query()->where('email', 'superadmin@koperasi.id')->count());
    }

    public function test_demo_data_seeds_when_explicitly_allowed_outside_local_testing(): void
    {
        $this->app['env'] = 'production';
        Config::set('app.allow_demo_seeding', true);

        $this->artisan('db:seed', ['--class' => DevelopmentSeeder::class, '--force' => true]);

        $this->assertGreaterThan(0, Company::count());
        $this->assertDatabaseHas('users', ['email' => 'superadmin@koperasi.id']);
    }

    public function test_seeded_demo_users_remain_unique_after_running_development_seeder_twice(): void
    {
        $this->seed(DevelopmentSeeder::class);
        $this->seed(DevelopmentSeeder::class);

        $expectedEmails = [
            'superadmin@koperasi.id',
            'admin.pusat@koperasi.id',
            'kepala.gudang@koperasi.id',
            'staff.admin@koperasi.id',
            'purchasing@koperasi.id',
            'koperasi.unit1@koperasi.id',
            'koperasi.unit2@koperasi.id',
        ];

        foreach ($expectedEmails as $email) {
            $this->assertSame(
                1,
                User::query()->where('email', $email)->count(),
                "Expected exactly one user with email [{$email}] after seeding twice."
            );
        }
    }

    public function test_seeded_demo_role_mapping_matches_expected_roles(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(DevelopmentSeeder::class);

        $expectedRoles = [
            'superadmin@koperasi.id' => 'super_admin',
            'admin.pusat@koperasi.id' => 'app_admin',
            'kepala.gudang@koperasi.id' => 'kepala_gudang',
            'staff.admin@koperasi.id' => 'staff_admin',
            'purchasing@koperasi.id' => 'purchasing',
            'koperasi.unit1@koperasi.id' => 'koperasi',
            'koperasi.unit2@koperasi.id' => 'koperasi',
        ];

        foreach ($expectedRoles as $email => $role) {
            $user = User::where('email', $email)->firstOrFail();
            $this->assertTrue(
                $user->hasRole($role),
                "Expected user [{$email}] to have role [{$role}]."
            );
        }
    }
}
