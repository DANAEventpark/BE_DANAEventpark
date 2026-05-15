<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::with([
                'category',
                'organizer'
            ])
            ->withCount([
                'registrations as confirmed_registrations_count' => function ($query) {
                    $query->where('status', 'confirmed');
                }
            ])
            ->withAvg('reviews', 'rating')
            ->where('status', 'published')
            ->orderBy('start_time', 'asc')
            ->paginate(6);

        return response()->json([
            'data' => $events->items(),

            'pagination' => [
                'currentPage' => $events->currentPage(),
                'lastPage' => $events->lastPage(),
                'perPage' => $events->perPage(),
                'total' => $events->total(),
            ]
        ]);
    }
}
