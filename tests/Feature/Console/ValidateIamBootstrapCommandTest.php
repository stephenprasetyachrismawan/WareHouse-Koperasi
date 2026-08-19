<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ValidateIamBootstrapCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_passes_when_core_iam_is_bootstrapped(): void
    {
        $this->artisan('ops:validate-iam')
            ->assertSuccessful()
            ->expectsOutputToContain('ROLE[app_admin]=PASS')
            ->expectsOutputToContain('IAM bootstrap is present.');
    }

    public function test_fails_when_a_required_role_is_missing(): void
    {
        Role::where('name', 'app_admin')->where('guard_name', 'web')->delete();

        $this->artisan('ops:validate-iam')
            ->assertFailed()
            ->expectsOutputToContain('ROLE[app_admin]=FAIL');
    }
}
