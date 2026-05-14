<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::with([
            'category:id,name,image',
            'organizer:id,name,organization_name',
        ])
            ->withCount([
                'registrations as confirmed_registrations_count' => fn ($query) => $query->where('status', 'confirmed'),
                'reviews',
            ])
            ->withAvg('reviews', 'rating')
            ->where('status', 'published')
            ->orderBy('start_time', 'asc')
            ->paginate(6);

        return response()->json($events);
    }
}
