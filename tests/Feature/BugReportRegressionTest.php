<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BugReportRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizer_cannot_create_event_in_the_past()
    {
        $organizer = User::factory()->create(['role_id' => 2]);

        $response = $this->actingAsJwt($organizer)
            ->postJson('/api/organizer/events', [
                'title' => 'Past Event',
                'start_time' => now()->subDay()->format('Y-m-d H:i:s'),
                'end_time' => now()->addDay()->format('Y-m-d H:i:s'),
                'location' => 'Da Nang',
                'capacity' => 50,
                'status' => 'published',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_time']);
    }

    public function test_organizer_cannot_create_event_with_excessive_capacity()
    {
        $organizer = User::factory()->create(['role_id' => 2]);

        $response = $this->actingAsJwt($organizer)
            ->postJson('/api/organizer/events', [
                'title' => 'Huge Event',
                'start_time' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'end_time' => now()->addDays(3)->format('Y-m-d H:i:s'),
                'location' => 'Da Nang',
                'capacity' => 1000000,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['capacity']);
    }

    public function test_organizer_event_lists_include_full_edit_fields()
    {
        $organizer = User::factory()->create(['role_id' => 2]);
        $category = Category::factory()->create();

        Event::factory()->create([
            'organizer_id' => $organizer->id,
            'category_id' => $category->id,
            'description' => 'Full description',
            'location' => 'Hai Chau, Da Nang',
        ]);

        $response = $this->actingAsJwt($organizer)->getJson('/api/organizer/events');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'title', 'description', 'location', 'category_id', 'start_time', 'end_time', 'registration_deadline', 'capacity', 'status']
                ]
            ]);
    }

    public function test_system_stats_matches_upcoming_published_events()
    {
        Event::factory()->create([
            'status' => 'published',
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(3),
        ]);
        Event::factory()->create([
            'status' => 'published',
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDay(),
        ]);

        $stats = $this->getJson('/api/system-stats');
        $events = $this->getJson('/api/events');

        $stats->assertStatus(200)
            ->assertJsonPath('data.total_events', 1);
        $events->assertStatus(200)
            ->assertJsonPath('total', 1);
    }

    public function test_attendee_can_register_as_organization_with_quantity()
    {
        $attendee = User::factory()->create(['role_id' => 1]);
        $event = Event::factory()->create(['capacity' => 20, 'status' => 'published']);

        $missingOrg = $this->actingAsJwt($attendee)
            ->postJson("/api/events/{$event->id}/register", [
                'registration_type' => 'organization',
                'quantity' => 5,
            ]);
        $missingOrg->assertStatus(422)
            ->assertJsonValidationErrors(['organization_name']);

        $ok = $this->actingAsJwt($attendee)
            ->postJson("/api/events/{$event->id}/register", [
                'registration_type' => 'organization',
                'organization_name' => 'CLB IT',
                'quantity' => 5,
            ]);
        $ok->assertStatus(200)
            ->assertJsonPath('data.organization_name', 'CLB IT')
            ->assertJsonPath('data.quantity', 5);
    }

    public function test_cannot_register_for_cancelled_event()
    {
        $attendee = User::factory()->create(['role_id' => 1]);
        $event = Event::factory()->create(['status' => 'cancelled']);

        $response = $this->actingAsJwt($attendee)
            ->postJson("/api/events/{$event->id}/register", []);

        $response->assertStatus(400);
    }

    public function test_profile_update_rejects_non_numeric_phone()
    {
        $user = User::factory()->create(['role_id' => 1]);

        $response = $this->actingAsJwt($user)
            ->putJson('/api/auth/profile/info', [
                'name' => 'Ho Thi Vai',
                'phone' => 'haibaia',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_unverified_login_returns_translated_message_not_raw_key()
    {
        User::factory()->unverified()->create([
            'email' => 'unverified@example.com',
            'password' => bcrypt('password123'),
            'role_id' => 2,
        ]);

        $response = $this->withHeader('Accept-Language', 'vi')
            ->postJson('/api/auth/login', [
                'email' => 'unverified@example.com',
                'password' => 'password123',
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('error_code', 'EMAIL_UNVERIFIED');
        $this->assertNotEquals('messages.email_unverified', $response->json('message'));
    }

    public function test_unauthenticated_api_request_with_html_accept_returns_401_json()
    {
        $response = $this->withHeader('Accept', 'text/html')
            ->get('/api/auth/me');

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_attendee_cannot_create_event_via_organizer_api()
    {
        $attendee = User::factory()->create(['role_id' => 1]);

        $response = $this->actingAsJwt($attendee)
            ->postJson('/api/organizer/events', [
                'title' => 'Unauthorized Event',
                'start_time' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'end_time' => now()->addDays(3)->format('Y-m-d H:i:s'),
                'location' => 'Da Nang',
                'capacity' => 50,
            ]);

        $response->assertStatus(403);
    }

    public function test_non_existent_event_returns_clean_404_without_model_leak()
    {
        $response = $this->getJson('/api/events/99999');

        $response->assertStatus(404);
        $this->assertStringNotContainsString('App\\Models\\Event', $response->getContent());
    }
}
