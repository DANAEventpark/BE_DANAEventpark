<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EventController extends Controller
{

    private function baseEventQuery(): Builder
    {
        return Event::query()

            ->with([
                'category:id,name,image',
                'organizer:id,name,organization_name',
            ])

            // COUNT REGISTRATIONS + REVIEWS
            ->withCount([
                'registrations as confirmed_registrations_count' => function ($query) {
                    $query->where('status', 'confirmed');
                },

                'reviews',
            ])

            // AVG RATING
            ->withAvg('reviews', 'rating');
    }

    /**
     * Danh sách sự kiện
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->baseEventQuery()
            ->where('status', 'published');

        if ($request->filled('search')) {

            $search = $request->input('search');

            $query->where(function ($q) use ($search) {

                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        /**
         * FILTER CATEGORY
         */
        if (
            $request->filled('category_id') &&
            $request->input('category_id') !== 'all'
        ) {

            $query->where(
                'category_id',
                $request->input('category_id')
            );
        }

        /**
         * FILTER TIME
         */
        if ($request->filled('time_filter')) {

            $timeFilter = $request->input('time_filter');

            $now = Carbon::now();

            switch ($timeFilter) {

                case 'today':

                    $query->whereDate(
                        'start_time',
                        $now->toDateString()
                    );

                    break;

                case 'this_week':

                    $startOfWeek = (clone $now)
                        ->startOfWeek()
                        ->toDateTimeString();

                    $endOfWeek = (clone $now)
                        ->endOfWeek()
                        ->toDateTimeString();

                    $query->whereBetween(
                        'start_time',
                        [$startOfWeek, $endOfWeek]
                    );

                    break;

                case 'this_month':

                    $query->whereMonth(
                        'start_time',
                        $now->month
                    )->whereYear(
                        'start_time',
                        $now->year
                    );

                    break;

                case 'upcoming':
                default:

                    $query->where(
                        'start_time',
                        '>=',
                        $now->toDateTimeString()
                    );

                    break;
            }
        } else {

            // DEFAULT = UPCOMING
            $query->where(
                'start_time',
                '>=',
                Carbon::now()->toDateTimeString()
            );
        }

        $events = $query
            ->orderBy('start_time', 'asc')
            ->paginate(6);

        return response()->json($events);
    }
}
