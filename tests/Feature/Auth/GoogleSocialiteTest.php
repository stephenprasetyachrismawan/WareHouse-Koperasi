<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GoogleSocialiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function fakeGoogle(array $attributes = []): SocialiteUser
    {
        $user = new SocialiteUser;

        $user->map(array_merge([
            'id' => 'google-123',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'avatar' => null,
        ], $attributes));

        $user->user['email_verified'] = $attributes['email_verified'] ?? true;

        Socialite::fake('google', $user);

        return $user;
    }

    public function test_redirect_to_google_provider(): void
    {
        $this->fakeGoogle();

        $this->get(route('auth.google.redirect'))
            ->assertRedirect();
    }

    public function test_new_verified_user_is_provisioned_and_sent_to_company_setup(): void
    {
        $this->fakeGoogle();

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('auth.google.complete'));

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'google_id' => 'google-123',
            'status' => 'active',
        ]);

        $this->assertGuest();
    }

    public function test_unverified_google_email_is_rejected(): void
    {
        $this->fakeGoogle(['email_verified' => false]);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'));

        $this->assertDatabaseMissing('users', ['email' => 'john@example.com']);
        $this->assertGuest();
    }

    public function test_existing_user_is_logged_in_directly_by_google_id(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'google_id' => 'google-123',
            'status' => 'active',
        ]);

        $this->fakeGoogle();

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_existing_user_matching_by_email_is_logged_in_and_linked(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'google_id' => null,
            'status' => 'active',
        ]);

        $this->fakeGoogle();

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-123', $user->fresh()->google_id);
    }

    public function test_deactivated_user_cannot_sign_in_with_google(): void
    {
        User::factory()->create([
            'email' => 'john@example.com',
            'google_id' => 'google-123',
            'status' => 'inactive',
        ]);

        $this->fakeGoogle();

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_complete_company_creates_tenant_and_logs_in_user(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'google_id' => 'google-123',
            'status' => 'active',
        ]);

        session(['google_signin.user_id' => $user->id]);

        $this->post(route('auth.google.complete.store'), [
            'company_name' => 'Koperasi Mandiri Sejahtera',
            'company_code' => 'KOP-MANDIRI',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('companies', ['code' => 'KOP-MANDIRI']);
        $this->assertDatabaseHas('warehouses', ['company_id' => $user->activeCompany()->id]);
        $this->assertDatabaseHas('warehouse_memberships', [
            'user_id' => $user->id,
            'role' => 'app_admin',
            'status' => 'active',
        ]);
    }

    public function test_complete_form_requires_pending_google_session(): void
    {
        $this->get(route('auth.google.complete'))
            ->assertRedirect(route('login'));
    }
}
