<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'start_time' => fake()->dateTimeBetween('now', '+1 month'),
            'end_time' => fake()->dateTimeBetween('+1 month', '+2 months'),
            'location' => fake()->address(),
            'organizer_id' => User::factory(),
            'status' => 'published',
            'capacity' => 100,
            'category_id' => Category::factory(),
            'image' => fake()->imageUrl(),
            'registration_deadline' => fake()->dateTimeBetween('now', '+1 month'),
        ];
    }
}
