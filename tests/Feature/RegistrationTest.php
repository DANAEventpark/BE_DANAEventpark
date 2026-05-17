<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_register_for_an_event_successfully()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['capacity' => 10]);

        $response = $this->actingAsJwt($user)
            ->postJson("/api/events/{$event->id}/register");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Đăng ký thành công.')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'event_id', 'user_id', 'status'],
            ]);

        $this->assertDatabaseHas('registrations', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'approved',
        ]);
    }

    public function test_it_fails_registration_without_jwt_token()
    {
        $event = Event::factory()->create();

        $response = $this->postJson("/api/events/{$event->id}/register");

        $response->assertStatus(401);
    }

    public function test_it_fails_registration_when_already_registered()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();

        // Create pre-existing registration
        Registration::factory()->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'approved',
        ]);

        $response = $this->actingAsJwt($user)
            ->postJson("/api/events/{$event->id}/register");

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bạn đã đăng ký sự kiện này.');
    }

    public function test_it_fails_registration_when_capacity_exceeded()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        
        $event = Event::factory()->create(['capacity' => 1]);

        // Pre-fill capacity
        Registration::factory()->create([
            'event_id' => $event->id,
            'user_id' => $otherUser->id,
            'status' => 'approved',
        ]);

        $response = $this->actingAsJwt($user)
            ->postJson("/api/events/{$event->id}/register");

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Sự kiện đã hết chỗ.');
    }

    public function test_it_returns_404_when_registering_for_non_existent_event()
    {
        $user = User::factory()->create();

        $response = $this->actingAsJwt($user)
            ->postJson('/api/events/999/register');

        $response->assertStatus(404);
    }
}
