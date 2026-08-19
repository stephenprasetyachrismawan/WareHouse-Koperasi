<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * `RoleAndPermissionSeeder` (core IAM) always runs and is safe for every
     * environment. `DevelopmentSeeder` (demo companies/users/business data)
     * guards itself and only seeds in local/testing or with an explicit
     * `ALLOW_DEMO_SEEDING` opt-in — see `DevelopmentSeeder`.
     */
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            DevelopmentSeeder::class,
        ]);
    }
}
