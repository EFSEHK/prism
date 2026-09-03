<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    private function seedUser(string $email = 'parent@efsc-ya.com', string $password = 'Test.123'): User
    {
        return User::factory()->create([
            'email' => $email,
            'password' => $password,
        ]);
    }

    public function test_change_password_with_valid_credentials(): void
    {
        $this->seedUser();

        $response = $this->postJson('/api/change-password', [
            'email' => 'parent@efsc-ya.com',
            'current_password' => 'Test.123',
            'password' => 'Secure.Pass9',
            'password_confirmation' => 'Secure.Pass9',
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'Password updated successfully.']);

        $this->assertTrue(
            \Illuminate\Support\Facades\Hash::check('Secure.Pass9', User::first()->password)
        );
    }

    public function test_change_password_rejects_weak_password(): void
    {
        $this->seedUser();

        $response = $this->postJson('/api/change-password', [
            'email' => 'parent@efsc-ya.com',
            'current_password' => 'Test.123',
            'password' => 'weakpass',
            'password_confirmation' => 'weakpass',
        ]);

        $response->assertUnprocessable();
    }

    public function test_change_password_rejects_username_substring(): void
    {
        $this->seedUser();

        $response = $this->postJson('/api/change-password', [
            'email' => 'parent@efsc-ya.com',
            'current_password' => 'Test.123',
            'password' => 'XparentY!9',
            'password_confirmation' => 'XparentY!9',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_change_password_rejects_wrong_current_password(): void
    {
        $this->seedUser();

        $response = $this->postJson('/api/change-password', [
            'email' => 'parent@efsc-ya.com',
            'current_password' => 'Wrong.Password',
            'password' => 'Secure.Pass9',
            'password_confirmation' => 'Secure.Pass9',
        ]);

        $response->assertUnauthorized()
            ->assertJson(['message' => 'Current password is incorrect.']);
    }
}
