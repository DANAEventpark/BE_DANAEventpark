<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class EventController extends Controller
{
    private function baseEventQuery(): Builder
    {
        return Event::query()
            ->with([
                'category:id,name,image',
                'organizer:id,name,organization_name',
            ])
            ->withCount([
                'registrations as confirmed_registrations_count' => fn (Builder $query) => $query->where('status', 'confirmed'),
                'reviews',
            ])
            ->withAvg('reviews', 'rating');
    }

    public function index(): JsonResponse
    {
        $events = $this->baseEventQuery()
            ->where('status', 'published')
            ->orderBy('start_time', 'asc')
            ->paginate(6);

        return response()->json($events);
    }

    public function show(int $id): JsonResponse
    {
        $event = $this->baseEventQuery()
            ->where('status', 'published')
            ->findOrFail($id);

        return response()->json($event);
    }

    public function register(): JsonResponse
    {
        return response()->json([
            'message' => 'Event registration endpoint has not been implemented yet.',
        ], 501);
    }

    public function review(): JsonResponse
    {
        return response()->json([
            'message' => 'Event review endpoint has not been implemented yet.',
        ], 501);
    }
}
