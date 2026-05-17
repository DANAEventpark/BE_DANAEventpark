<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Registration;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_fetch_event_details_successfully()
    {
        $event = Event::factory()->create();

        $response = $this->getJson("/api/events/{$event->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'title',
                    'description',
                    'start_time',
                    'end_time',
                    'location',
                    'organizer_id',
                    'status',
                    'capacity',
                    'category_id',
                    'image',
                    'category',
                    'organizer' => ['id', 'name', 'email', 'avatar'],
                    'registrations',
                    'reviews',
                ],
            ]);
    }

    public function test_it_includes_only_approved_registrations_with_user_details()
    {
        $event = Event::factory()->create();
        
        $approvedUser = User::factory()->create();
        $pendingUser = User::factory()->create();

        Registration::factory()->create([
            'event_id' => $event->id,
            'user_id' => $approvedUser->id,
            'status' => 'approved',
        ]);
        
        Registration::factory()->create([
            'event_id' => $event->id,
            'user_id' => $pendingUser->id,
            'status' => 'pending',
        ]);

        $response = $this->getJson("/api/events/{$event->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.registrations')
            ->assertJsonPath('data.registrations.0.user.id', $approvedUser->id)
            ->assertJsonStructure([
                'data' => [
                    'registrations' => [
                        '*' => [
                            'id',
                            'event_id',
                            'user_id',
                            'status',
                            'user' => ['id', 'name', 'avatar'],
                        ]
                    ]
                ]
            ]);
    }

    public function test_it_includes_reviews_with_reviewer_details()
    {
        $event = Event::factory()->create();
        $reviewer = User::factory()->create();

        Review::factory()->create([
            'event_id' => $event->id,
            'user_id' => $reviewer->id,
            'rating' => 5,
            'comment' => 'Tuyệt vời',
        ]);

        $response = $this->getJson("/api/events/{$event->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.reviews')
            ->assertJsonPath('data.reviews.0.comment', 'Tuyệt vời')
            ->assertJsonPath('data.reviews.0.user.id', $reviewer->id)
            ->assertJsonStructure([
                'data' => [
                    'reviews' => [
                        '*' => [
                            'id',
                            'event_id',
                            'user_id',
                            'rating',
                            'comment',
                            'user' => ['id', 'name', 'avatar'],
                        ]
                    ]
                ]
            ]);
    }

    public function test_it_returns_404_when_event_does_not_exist()
    {
        $response = $this->getJson('/api/events/999');

        $response->assertStatus(404);
    }
}
