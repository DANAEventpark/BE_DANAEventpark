<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_submit_a_review_successfully()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAsJwt($user)
            ->postJson("/api/events/{$event->id}/reviews", [
                'rating' => 5,
                'comment' => 'Sự kiện vô cùng ấn tượng và bổ ích!',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Bình luận thành công.')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'event_id',
                    'user_id',
                    'rating',
                    'comment',
                    'user' => ['id', 'name', 'avatar'],
                ],
            ]);

        $this->assertDatabaseHas('reviews', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'rating' => 5,
            'comment' => 'Sự kiện vô cùng ấn tượng và bổ ích!',
        ]);
    }

    public function test_it_fails_submitting_review_without_jwt_token()
    {
        $event = Event::factory()->create();

        $response = $this->postJson("/api/events/{$event->id}/reviews", [
            'rating' => 4,
            'comment' => 'Rất hay',
        ]);

        $response->assertStatus(401);
    }

    public function test_it_fails_submitting_review_with_missing_rating()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAsJwt($user)
            ->postJson("/api/events/{$event->id}/reviews", [
                'comment' => 'Thiếu đánh giá sao',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    public function test_it_fails_submitting_review_with_missing_comment()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAsJwt($user)
            ->postJson("/api/events/{$event->id}/reviews", [
                'rating' => 4,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['comment']);
    }

    public function test_it_fails_submitting_review_with_invalid_rating()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();

        // Rating > 5
        $response1 = $this->actingAsJwt($user)
            ->postJson("/api/events/{$event->id}/reviews", [
                'rating' => 6,
                'comment' => 'Rating quá lớn',
            ]);
        $response1->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);

        // Rating < 1
        $response2 = $this->actingAsJwt($user)
            ->postJson("/api/events/{$event->id}/reviews", [
                'rating' => 0,
                'comment' => 'Rating quá nhỏ',
            ]);
        $response2->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    public function test_it_returns_404_when_reviewing_non_existent_event()
    {
        $user = User::factory()->create();

        $response = $this->actingAsJwt($user)
            ->postJson('/api/events/999/reviews', [
                'rating' => 5,
                'comment' => 'Sự kiện không tồn tại',
            ]);

        $response->assertStatus(404);
    }
}
