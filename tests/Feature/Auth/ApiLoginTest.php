<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Modules\Auth\Services\SanctumTokenIssuer;
use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Core\Enums\RoleEnum;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ApiLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_driver_signs_in_with_mobile_and_receives_scoped_tokens(): void
    {
        $this->userWithRole(RoleEnum::Driver, ['mobile' => '9876543210']);

        $response = $this->postJson(route('api.auth.login'), [
            'username' => '9876543210',
            'password' => 'secret-password',
            'device_name' => 'pixel-8',
        ])->assertOk();

        $response->assertJsonPath('success', true)
            ->assertJsonPath('data.user.role', RoleEnum::Driver->value)
            ->assertJsonStructure(['data' => ['token' => ['access_token', 'refresh_token', 'expires_in', 'abilities']]]);

        $this->assertSame(
            PermissionEnum::forRole(RoleEnum::Driver),
            $response->json('data.token.abilities'),
        );
    }

    public function test_admin_signs_in_with_email(): void
    {
        $this->userWithRole(RoleEnum::Admin, ['email' => 'owner@example.com']);

        $this->postJson(route('api.auth.login'), [
            'username' => 'owner@example.com',
            'password' => 'secret-password',
        ])->assertOk()->assertJsonPath('data.user.role', RoleEnum::Admin->value);
    }

    public function test_wrong_password_is_unauthorised(): void
    {
        $this->userWithRole(RoleEnum::Customer, ['mobile' => '9000000000']);

        $this->postJson(route('api.auth.login'), [
            'username' => '9000000000',
            'password' => 'wrong',
        ])->assertUnauthorized()->assertJsonPath('success', false);
    }

    public function test_deactivated_account_is_refused(): void
    {
        $this->userWithRole(RoleEnum::Driver, ['mobile' => '9111111111', 'status' => false]);

        $this->postJson(route('api.auth.login'), [
            'username' => '9111111111',
            'password' => 'secret-password',
        ])->assertUnauthorized();
    }

    public function test_login_requires_username_and_password(): void
    {
        $this->postJson(route('api.auth.login'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['username', 'password']);
    }

    public function test_me_requires_a_token(): void
    {
        $this->getJson(route('api.auth.me'))->assertUnauthorized();
    }

    public function test_me_returns_the_signed_in_user(): void
    {
        $user = $this->userWithRole(RoleEnum::Customer, ['mobile' => '9222222222']);
        $token = $this->accessTokenFor($user);

        $this->withToken($token)->getJson(route('api.auth.me'))
            ->assertOk()
            ->assertJsonPath('data.mobile', '9222222222')
            ->assertJsonPath('data.can_self_order', true);
    }

    public function test_access_token_cannot_be_used_to_refresh(): void
    {
        $user = $this->userWithRole(RoleEnum::Driver, ['mobile' => '9333333333']);
        $token = $this->accessTokenFor($user);

        $this->withToken($token)->postJson(route('api.auth.refresh'))->assertForbidden();
    }

    public function test_refresh_token_issues_a_new_pair(): void
    {
        $user = $this->userWithRole(RoleEnum::Driver, ['mobile' => '9444444444']);

        $refresh = $this->postJson(route('api.auth.login'), [
            'username' => '9444444444',
            'password' => 'secret-password',
        ])->json('data.token.refresh_token');

        $this->withToken($refresh)->postJson(route('api.auth.refresh'))
            ->assertOk()
            ->assertJsonStructure(['data' => ['token' => ['access_token', 'refresh_token']]]);
    }

    public function test_logout_revokes_the_device_tokens(): void
    {
        $user = $this->userWithRole(RoleEnum::Driver, ['mobile' => '9555555555']);

        $token = $this->postJson(route('api.auth.login'), [
            'username' => '9555555555',
            'password' => 'secret-password',
            'device_name' => 'pixel-8',
        ])->json('data.token.access_token');

        $this->withToken($token)->postJson(route('api.auth.logout'))->assertOk();

        $this->assertSame(0, $user->tokens()->count());
    }

    private function accessTokenFor(User $user): string
    {
        return app(SanctumTokenIssuer::class)->issue($user, 'test-device')['access_token'];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function userWithRole(RoleEnum $role, array $attributes = []): User
    {
        $user = User::query()->create([
            'first_name' => 'Test',
            'password' => 'secret-password',
            'status' => true,
            ...$attributes,
        ]);

        $user->assignRole($role->value);

        return $user;
    }
}
