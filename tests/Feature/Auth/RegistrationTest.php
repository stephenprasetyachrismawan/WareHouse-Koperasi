<?php

namespace Tests\Feature\Auth;

use App\Enums\MembershipStatus;
use App\Enums\Permission;
use App\Enums\WarehouseRole;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post(route('register.store'), [
            'company_name' => 'Koperasi Sejahtera',
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_registration_creates_a_company_and_makes_the_user_its_app_admin(): void
    {
        $this->post(route('register.store'), [
            'company_name' => 'Koperasi Sejahtera',
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $warehouse = Warehouse::firstWhere('name', 'Koperasi Sejahtera');
        $user = User::firstWhere('email', 'test@example.com');

        $this->assertNotNull($warehouse);
        $this->assertNotNull($user);

        $membership = $user->membershipFor($warehouse);

        $this->assertNotNull($membership);
        $this->assertSame(WarehouseRole::AppAdmin, $membership->role);
        $this->assertSame(MembershipStatus::Active, $membership->status);
        $this->assertTrue($user->hasPermission($warehouse, Permission::UsersManage));
    }

    public function test_company_name_is_required(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('company_name');
        $this->assertGuest();
    }
}
