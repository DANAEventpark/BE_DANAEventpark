<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\Registration;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventListTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_fetch_list_of_published_events()
    {
        Event::factory()->count(3)->create(['status' => 'published']);

        $response = $this->getJson('/api/events');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'current_page',
                'data' => [
                    '*' => [
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
                        'registration_deadline',
                        'confirmed_registrations_count',
                        'reviews_count',
                        'reviews_avg_rating',
                        'category',
                        'organizer',
                    ]
                ],
                'total',
                'per_page'
            ]);
    }

    public function test_it_excludes_draft_events_from_list()
    {
        $publishedEvent = Event::factory()->create(['status' => 'published']);
        $draftEvent = Event::factory()->create(['status' => 'draft']);

        $response = $this->getJson('/api/events');

        $response->assertStatus(200)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $publishedEvent->id);
    }

    public function test_it_can_search_events_by_title()
    {
        $matching = Event::factory()->create(['title' => 'Đại nhạc hội EDM sôi động', 'status' => 'published']);
        $nonMatching = Event::factory()->create(['title' => 'Triển lãm hội họa sơn dầu', 'status' => 'published']);

        $response = $this->getJson('/api/events?search=nhạc');

        $response->assertStatus(200)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $matching->id);
    }

    public function test_it_can_search_events_by_description()
    {
        $matching = Event::factory()->create(['description' => 'Sự kiện dành riêng cho các tín đồ công nghệ biển đảo', 'status' => 'published']);
        $nonMatching = Event::factory()->create(['description' => 'Học làm gốm thủ công nghệ thuật', 'status' => 'published']);

        $response = $this->getJson('/api/events?search=biển');

        $response->assertStatus(200)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $matching->id);
    }

    public function test_it_can_search_events_by_location()
    {
        $matching = Event::factory()->create(['location' => 'Công viên Châu Á Đà Nẵng', 'status' => 'published']);
        $nonMatching = Event::factory()->create(['location' => 'Sân vận động Mỹ Đình Hà Nội', 'status' => 'published']);

        $response = $this->getJson('/api/events?search=Đà Nẵng');

        $response->assertStatus(200)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $matching->id);
    }

    public function test_it_returns_empty_data_if_search_has_no_matches()
    {
        Event::factory()->create(['title' => 'Hội nghị Tech', 'status' => 'published']);

        $response = $this->getJson('/api/events?search=xyznotfound');

        $response->assertStatus(200)
            ->assertJsonPath('total', 0);
    }

    public function test_it_can_filter_events_by_category_id()
    {
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();

        $event1 = Event::factory()->create(['category_id' => $category1->id, 'status' => 'published']);
        $event2 = Event::factory()->create(['category_id' => $category2->id, 'status' => 'published']);

        $response = $this->getJson("/api/events?category_id={$category1->id}");

        $response->assertStatus(200)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $event1->id);
    }

    public function test_it_does_not_filter_when_category_id_is_all()
    {
        $category = Category::factory()->create();
        Event::factory()->create(['category_id' => $category->id, 'status' => 'published']);
        Event::factory()->create(['category_id' => null, 'status' => 'published']);

        $response = $this->getJson('/api/events?category_id=all');

        $response->assertStatus(200)
            ->assertJsonPath('total', 2);
    }

    public function test_it_can_filter_events_happening_today()
    {
        $todayEvent = Event::factory()->create([
            'start_time' => now()->hour(10),
            'status' => 'published'
        ]);
        $tomorrowEvent = Event::factory()->create([
            'start_time' => now()->addDay()->hour(10),
            'status' => 'published'
        ]);

        $response = $this->getJson('/api/events?time_filter=today');

        $response->assertStatus(200)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $todayEvent->id);
    }

    public function test_it_can_filter_events_happening_this_week()
    {
        $thisWeekEvent = Event::factory()->create([
            'start_time' => now()->addMinutes(30),
            'status' => 'published'
        ]);
        $nextMonthEvent = Event::factory()->create([
            'start_time' => now()->addMonths(2),
            'status' => 'published'
        ]);

        $response = $this->getJson('/api/events?time_filter=this_week');

        $response->assertStatus(200)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $thisWeekEvent->id);
    }

    public function test_it_can_filter_events_happening_this_month()
    {
        $thisMonthEvent = Event::factory()->create([
            'start_time' => now()->endOfMonth()->subDay()->hour(10),
            'status' => 'published'
        ]);
        $nextYearEvent = Event::factory()->create([
            'start_time' => now()->addYear()->hour(10),
            'status' => 'published'
        ]);

        $response = $this->getJson('/api/events?time_filter=this_month');

        $response->assertStatus(200)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $thisMonthEvent->id);
    }

    public function test_it_filters_upcoming_events_by_default()
    {
        $pastEvent = Event::factory()->create([
            'start_time' => now()->subDay(),
            'end_time' => now()->subDay()->addHours(2),
            'status' => 'published'
        ]);
        $futureEvent = Event::factory()->create([
            'start_time' => now()->addDay(),
            'status' => 'published'
        ]);

        $response = $this->getJson('/api/events');

        $response->assertStatus(200)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $futureEvent->id);
    }

    public function test_it_paginates_six_events_per_page()
    {
        Event::factory()->count(7)->create(['status' => 'published']);

        $response = $this->getJson('/api/events');

        $response->assertStatus(200)
            ->assertJsonPath('per_page', 6)
            ->assertJsonPath('last_page', 2)
            ->assertJsonPath('total', 7);
    }

    public function test_it_includes_aggregates_and_relations_accurately()
    {
        $event = Event::factory()->create(['status' => 'published']);
        
        // Add confirmed registrations
        Registration::factory()->create(['event_id' => $event->id, 'status' => 'approved']);
        Registration::factory()->create(['event_id' => $event->id, 'status' => 'pending']); // should not be counted as confirmed

        // Add reviews
        Review::factory()->create(['event_id' => $event->id, 'rating' => 5]);
        Review::factory()->create(['event_id' => $event->id, 'rating' => 3]);

        $response = $this->getJson('/api/events');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.confirmed_registrations_count', 1)
            ->assertJsonPath('data.0.reviews_count', 2);
        
        $this->assertEquals(4, (float) $response->json('data.0.reviews_avg_rating'));
    }

    public function test_it_can_get_system_stats()
    {
        // 1. Create published and draft events
        Event::factory()->create(['status' => 'published']);
        Event::factory()->create(['status' => 'published']);
        Event::factory()->create(['status' => 'draft']);

        // 2. Create users with roles
        User::factory()->create(['role_id' => 2]);
        User::factory()->create(['role_id' => 2]);
        User::factory()->create(['role_id' => 1]);

        // 3. Create approved registrations
        $event = Event::factory()->create(['status' => 'published']);
        Registration::factory()->create(['event_id' => $event->id, 'status' => 'approved']);
        Registration::factory()->create(['event_id' => $event->id, 'status' => 'pending']);

        $response = $this->getJson('/api/system-stats');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_events' => 3, // 2 from above + 1 for registration test event
                    'total_organizers' => 2,
                    'total_registrations' => 1,
                ]
            ]);
    }
}
