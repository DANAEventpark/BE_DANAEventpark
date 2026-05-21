<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Event;
use App\Models\Registration;
use App\Models\Review;

class OrganizerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizer_can_register_and_access_dashboard()
    {
        // 1. Create Roles
        Role::firstOrCreate(['name' => 'organizer']);
        Role::firstOrCreate(['name' => 'attendee']);

        // 2. Register Organizer
        $registerResponse = $this->postJson('/api/auth/register', [
            'name' => 'Test Organizer',
            'email' => 'organizer_test@example.com',
            'phone' => '0987654321',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'organizer'
        ]);

        if ($registerResponse->status() !== 201) {
            dd($registerResponse->json());
        }
        
        $token = $registerResponse->json('data.token');

        $organizer = User::where('email', 'organizer_test@example.com')->first();

        // 3. Create some events for this organizer
        $event1 = Event::factory()->create(['organizer_id' => $organizer->id, 'capacity' => 100, 'status' => 'Published']);
        $event2 = Event::factory()->create(['organizer_id' => $organizer->id, 'capacity' => 50, 'status' => 'Draft']);

        // Add registrations
        Registration::factory()->count(10)->create(['event_id' => $event1->id]);
        Registration::factory()->count(5)->create(['event_id' => $event2->id]);

        // Add reviews
        Review::factory()->create(['event_id' => $event1->id, 'rating' => 5]);
        Review::factory()->create(['event_id' => $event1->id, 'rating' => 4]);

        // 4. Test Dashboard Stats
        $statsResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/organizer/dashboard/stats');

        $statsResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_events' => 2,
                    'total_registrations' => 15,
                    'average_rating' => 4.5
                ]
            ]);

        // 5. Test Recent Events
        $eventsResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/organizer/dashboard/events');

        $eventsResponse->assertStatus(200);
        $this->assertCount(2, $eventsResponse->json('data'));
        $data = collect($eventsResponse->json('data'));
        $this->assertEquals(10, $data->where('id', $event1->id)->first()['registrations_count']);
        $this->assertEquals(5, $data->where('id', $event2->id)->first()['registrations_count']);
    }
}
