<?php

namespace Tests\Feature;

use App\Actions\Fortify\CreateNewUser;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentationPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('documentation'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_documentation_page(): void
    {
        $createNewUserAction = new CreateNewUser;
        $user = $createNewUserAction->create([
            'name' => 'Documentation User',
            'email' => 'documentation-user@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'company_name' => 'Documentation Company',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('documentation'));

        $response->assertOk();
    }

    public function test_documentation_page_renders_without_unclosed_blade_directives(): void
    {
        $createNewUserAction = new CreateNewUser;
        $user = $createNewUserAction->create([
            'name' => 'Documentation Structure User',
            'email' => 'documentation-structure@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'company_name' => 'Documentation Structure Company',
        ]);

        $this->actingAs($user);

        $html = $this->get(route('documentation'))->getContent();

        $this->assertStringNotContainsString('@endphp', $html);

        $openDivCount = preg_match_all('/<div\b/', $html);
        $closeDivCount = preg_match_all('/<\/div>/', $html);

        $this->assertSame($openDivCount, $closeDivCount, 'Documentation page has unbalanced <div> tags.');
    }
}
