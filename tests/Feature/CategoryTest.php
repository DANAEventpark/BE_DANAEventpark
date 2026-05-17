<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_fetch_list_of_categories_successfully()
    {
        Category::factory()->count(3)->create();

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'image', 'events_count'],
                ],
            ]);
    }

    public function test_it_includes_events_count_in_categories_list()
    {
        $category = Category::factory()->create();
        Event::factory()->create(['category_id' => $category->id, 'status' => 'published']);
        Event::factory()->create(['category_id' => $category->id, 'status' => 'published']);

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.id', $category->id)
            ->assertJsonPath('data.0.events_count', 2);
    }

    public function test_it_can_fetch_events_belonging_to_a_category()
    {
        $category = Category::factory()->create();
        $event1 = Event::factory()->create(['category_id' => $category->id, 'status' => 'published']);
        $event2 = Event::factory()->create(['category_id' => $category->id, 'status' => 'published']);

        $response = $this->getJson("/api/categories/{$category->id}/events");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('category_name', $category->name)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'success',
                'category_name',
                'data' => [
                    '*' => ['id', 'title', 'status', 'category_id'],
                ],
            ]);
    }

    public function test_it_excludes_draft_events_when_fetching_events_by_category()
    {
        $category = Category::factory()->create();
        $publishedEvent = Event::factory()->create(['category_id' => $category->id, 'status' => 'published']);
        $draftEvent = Event::factory()->create(['category_id' => $category->id, 'status' => 'draft']);

        $response = $this->getJson("/api/categories/{$category->id}/events");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $publishedEvent->id);
    }

    public function test_it_returns_404_when_fetching_events_for_non_existent_category()
    {
        $response = $this->getJson('/api/categories/999/events');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Không tìm thấy danh mục này!');
    }
}
