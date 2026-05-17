<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;

class EventController extends Controller
{
    /**
     * GET /api/events
     * List events with pagination, search, category filter, time filter
     */
    public function index(Request $request)
    {
        $query = Event::with('category', 'organizer:id,name')
            ->where('status', 'published');

        // Search by title
        if ($search = $request->query('search')) {
            $query->where('title', 'like', '%' . $search . '%');
        }

        // Filter by category
        if ($categoryId = $request->query('category_id')) {
            if ($categoryId !== 'all') {
                $query->where('category_id', $categoryId);
            }
        }

        // Filter by time
        $timeFilter = $request->query('time_filter', 'upcoming');
        $now = now();

        match ($timeFilter) {
            'this_week' => $query->whereBetween('start_time', [$now, $now->copy()->endOfWeek()]),
            'this_month' => $query->whereBetween('start_time', [$now, $now->copy()->endOfMonth()]),
            default => $query->where('start_time', '>=', $now), // 'upcoming'
        };

        $events = $query->orderBy('start_time', 'asc')
            ->withCount(['registrations as confirmed_registrations_count' => function ($q) {
                $q->where('status', 'approved');
            }])
            ->paginate(6);

        return response()->json($events);
    }

    /**
     * GET /api/events/{id}
     */
    public function show($id)
    {
        $event = Event::with([
            'category',
            'organizer:id,name,email,avatar',
            'registrations' => function($query) {
                $query->where('status', 'approved')
                      ->with('user:id,name,avatar');
            },
            'reviews.user:id,name,avatar'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $event
        ]);
    }
}
