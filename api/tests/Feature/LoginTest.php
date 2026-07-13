<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression: "Invalid login details" with correct credentials was repeatedly
 * caused by empty/wiped users tables and identifier/password edge cases — not
 * flaky Auth::attempt. These tests lock the login contract on SQLite :memory:
 * (see phpunit.xml). Do not comment out DB_CONNECTION=sqlite there.
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function seedUser(string $email = 'superadmin@efsc-ya.com', string $password = 'S.Admin.123'): User
    {
        return User::factory()->create([
            'email' => $email,
            'password' => $password,
        ]);
    }

    public function test_login_with_full_email_and_password(): void
    {
        $this->seedUser();

        $response = $this->postJson('/api/login', [
            'email' => 'superadmin@efsc-ya.com',
            'password' => 'S.Admin.123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'user' => ['id', 'email']]);
    }

    public function test_login_with_local_part_appends_configured_domain(): void
    {
        $this->seedUser();

        $response = $this->postJson('/api/login', [
            'email' => 'superadmin',
            'password' => 'S.Admin.123',
        ]);

        $response->assertOk();
        $this->assertSame('superadmin@efsc-ya.com', $response->json('user.email'));
    }

    public function test_login_trims_password_and_identifier_whitespace(): void
    {
        $this->seedUser();

        $response = $this->postJson('/api/login', [
            'email' => '  superadmin@efsc-ya.com  ',
            'password' => '  S.Admin.123  ',
        ]);

        $response->assertOk();
    }

    public function test_wrong_password_returns_invalid_login_details(): void
    {
        $this->seedUser();

        $response = $this->postJson('/api/login', [
            'email' => 'superadmin@efsc-ya.com',
            'password' => 'Wrong.Password',
        ]);

        $response->assertUnauthorized()
            ->assertJson(['message' => 'Invalid login details']);
    }

    public function test_unknown_user_returns_invalid_login_details(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nobody@efsc-ya.com',
            'password' => 'S.Admin.123',
        ]);

        $response->assertUnauthorized()
            ->assertJson(['message' => 'Invalid login details']);
    }

    public function test_empty_users_table_returns_invalid_login_not_500(): void
    {
        $this->assertSame(0, User::query()->count());

        $response = $this->postJson('/api/login', [
            'email' => 'superadmin@efsc-ya.com',
            'password' => 'S.Admin.123',
        ]);

        $response->assertUnauthorized()
            ->assertJson(['message' => 'Invalid login details']);
    }
}
