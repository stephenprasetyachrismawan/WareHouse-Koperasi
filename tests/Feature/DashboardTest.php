<?php

namespace Tests\Feature;

use App\Actions\Fortify\CreateNewUser;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $createNewUserAction = new CreateNewUser;
        $user = $createNewUserAction->create([
            'name' => 'Dashboard User',
            'email' => 'user@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'company_name' => 'Dashboard Company',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }
}
