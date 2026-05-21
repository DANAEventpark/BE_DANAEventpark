<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;

class OrganizerEventController extends Controller
{
    /**
     * Lấy các chỉ số thống kê tổng quan cho Dashboard
     */
    public function getDashboardStats(Request $request)
    {
        $organizerId = $request->user()->id;

        // Tổng sự kiện do organizer này tổ chức
        $totalEvents = Event::where('organizer_id', $organizerId)->count();

        // Tổng người đăng ký vào tất cả sự kiện của organizer này
        $totalRegistrations = Event::where('organizer_id', $organizerId)
            ->withCount('registrations')
            ->get()
            ->sum('registrations_count');

        // Điểm đánh giá trung bình
        $events = Event::where('organizer_id', $organizerId)->with('reviews')->get();
        
        $totalRating = 0;
        $totalReviews = 0;
        
        foreach ($events as $event) {
            foreach ($event->reviews as $review) {
                $totalRating += $review->rating;
                $totalReviews++;
            }
        }
        
        $averageRating = $totalReviews > 0 ? round($totalRating / $totalReviews, 1) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'total_events' => $totalEvents,
                'total_registrations' => $totalRegistrations,
                'average_rating' => $averageRating
            ]
        ]);
    }

    /**
     * Lấy danh sách 5 sự kiện gần nhất cho Dashboard
     */
    public function getRecentEvents(Request $request)
    {
        $organizerId = $request->user()->id;

        $events = Event::where('organizer_id', $organizerId)
            ->withCount('registrations')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'start_time' => $event->start_time,
                    'registrations_count' => $event->registrations_count,
                    'capacity' => $event->capacity,
                    'status' => $event->status,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $events
        ]);
    }
}
