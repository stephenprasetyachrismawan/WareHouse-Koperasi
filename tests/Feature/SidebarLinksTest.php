<?php

namespace Tests\Feature;

use App\Actions\Fortify\CreateNewUser;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarLinksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_repository_link_points_to_this_project_not_the_starter_kit_template(): void
    {
        $createNewUserAction = new CreateNewUser;
        $user = $createNewUserAction->create([
            'name' => 'Sidebar Link User',
            'email' => 'sidebar-link-user@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'company_name' => 'Sidebar Link Company',
        ]);

        $this->actingAs($user);

        $html = $this->get(route('dashboard'))->getContent();

        $this->assertStringNotContainsString('laravel/livewire-starter-kit', $html);
        $this->assertStringContainsString('https://github.com/stephenprasetyachrismawan/WareHouse-Koperasi', $html);
    }
}
