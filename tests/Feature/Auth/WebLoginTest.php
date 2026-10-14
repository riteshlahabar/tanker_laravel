<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Modules\Core\Enums\RoleEnum;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WebLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_login_page_is_reachable(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Sign in to account');
    }

    public function test_admin_can_sign_in_and_reach_the_dashboard(): void
    {
        $admin = $this->userWithRole(RoleEnum::Admin, ['email' => 'owner@example.com']);

        $this->post(route('login.attempt'), [
            'email' => 'owner@example.com',
            'password' => 'secret-password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_driver_is_refused_by_the_panel(): void
    {
        $this->userWithRole(RoleEnum::Driver, ['email' => 'driver@example.com']);

        $this->post(route('login.attempt'), [
            'email' => 'driver@example.com',
            'password' => 'secret-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_deactivated_admin_cannot_sign_in(): void
    {
        $this->userWithRole(RoleEnum::Admin, ['email' => 'off@example.com', 'status' => false]);

        $this->post(route('login.attempt'), [
            'email' => 'off@example.com',
            'password' => 'secret-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_wrong_password_is_refused(): void
    {
        $this->userWithRole(RoleEnum::Admin, ['email' => 'owner@example.com']);

        $this->post(route('login.attempt'), [
            'email' => 'owner@example.com',
            'password' => 'not-the-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function userWithRole(RoleEnum $role, array $attributes = []): User
    {
        $user = User::query()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'password' => 'secret-password',
            'status' => true,
            ...$attributes,
        ]);

        $user->assignRole($role->value);

        return $user;
    }
}
