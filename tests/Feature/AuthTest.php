<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_register_as_an_attendee_successfully()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Nguyễn Văn A',
            'email' => 'vana@gmail.com',
            'password' => 'password123',
            'phone' => '0987654321',
            'role' => 'attendee',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email', 'phone', 'role_id', 'role' => ['id', 'name']],
                    'token',
                    'token_type',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'vana@gmail.com',
            'role_id' => 1,
        ]);
    }

    public function test_it_can_register_as_an_organizer_successfully()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Công ty Event',
            'email' => 'organizer@gmail.com',
            'password' => 'password123',
            'phone' => '0123456789',
            'role' => 'organizer',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.user.role.name', 'organizer');

        $this->assertDatabaseHas('users', [
            'email' => 'organizer@gmail.com',
            'role_id' => 2,
        ]);
    }

    public function test_it_fails_registration_when_email_already_exists()
    {
        User::factory()->create(['email' => 'existed@gmail.com']);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Nguyễn Văn B',
            'email' => 'existed@gmail.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_it_fails_registration_with_missing_mandatory_fields()
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => 'missing_name@gmail.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_it_fails_registration_when_password_too_short()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Nguyễn Văn C',
            'email' => 'shortpass@gmail.com',
            'password' => '123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_it_can_login_successfully_with_correct_credentials()
    {
        $user = User::factory()->create([
            'email' => 'login@gmail.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login@gmail.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email', 'role_id', 'role' => ['id', 'name']],
                    'token',
                    'token_type',
                ],
            ]);
    }

    public function test_it_fails_login_with_incorrect_password()
    {
        $user = User::factory()->create([
            'email' => 'login@gmail.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login@gmail.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Email hoặc mật khẩu không đúng');
    }

    public function test_it_fails_login_with_non_existent_email()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'notfound@gmail.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('status', 'error');
    }

    public function test_it_can_fetch_me_profile_with_valid_jwt()
    {
        $user = User::factory()->create();

        $response = $this->actingAsJwt($user)
            ->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.user.id', $user->id);
    }

    public function test_it_fails_fetching_me_profile_without_jwt()
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    public function test_it_can_logout_successfully()
    {
        $user = User::factory()->create();

        $response = $this->actingAsJwt($user)
            ->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Đăng xuất thành công');
    }
}
