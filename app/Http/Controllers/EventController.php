<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    /**
     * GET /api/events
     * List events with pagination, search, category filter, time filter
     */
    public function index(Request $request)
    {
        $query = Event::with([
            'category:id,name,image',
            'organizer:id,name',
        ])
        ->where('status', 'published');

        // Search by title, description, location
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%')
                  ->orWhere('location', 'like', '%' . $search . '%');
            });
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
            'today' => $query->whereDate('start_time', $now->toDateString()),
            'this_week' => $query->whereBetween('start_time', [$now, $now->copy()->endOfWeek()]),
            'this_month' => $query->whereBetween('start_time', [$now, $now->copy()->endOfMonth()]),
            default => $query->where('start_time', '>=', $now), // 'upcoming'
        };

        $events = $query->orderBy('start_time', 'asc')
            ->withCount([
                'registrations as confirmed_registrations_count' => function ($q) {
                    $q->where('status', 'approved');
                },
                'reviews'
            ])
            ->withAvg('reviews', 'rating')
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

        // Check registration for current authenticated user
        $userRegistration = null;
        try {
            $user = auth('api')->user();
            if ($user) {
                $userRegistration = \App\Models\Registration::where('event_id', $id)
                    ->where('user_id', $user->id)
                    ->first();
            }
        } catch (\Exception $e) {
            // Ignore auth errors
        }

        return response()->json([
            'success' => true,
            'data' => $event,
            'user_registration' => $userRegistration
        ]);
    }

    /**
     * GET /api/system-stats
     * Get system statistics (events, registrations, organizers)
     */
    public function getSystemStats()
    {
        try {
            $totalEvents = DB::table('events')->where('status', 'published')->count();
            $totalRegistrations = DB::table('registrations')->where('status', 'approved')->count();
            $totalOrganizers = DB::table('users')->where('role_id', 2)->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_events' => (int) $totalEvents,
                    'total_registrations' => (int) $totalRegistrations,
                    'total_organizers' => (int) $totalOrganizers
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể lấy số liệu thống kê',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
