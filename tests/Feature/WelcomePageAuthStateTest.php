<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WelcomePageAuthStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_masuk_and_daftar_links(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee(__('Masuk'));
        $response->assertSee(__('Daftar'));
        $response->assertDontSee(__('Log out'));
    }

    public function test_authenticated_user_sees_dashboard_link_and_logout_instead(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $response->assertSee(__('Buka Dashboard'));
        $response->assertSee(__('Log out'));
        $response->assertDontSee(__('Daftar'));
    }
}
