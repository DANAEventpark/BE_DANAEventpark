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
                    'start_time' => $event->start_time ? $event->start_time->format('Y-m-d H:i:s') : null,
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

    /**
     * Lấy danh sách tất cả sự kiện của Organizer
     */
    public function index(Request $request)
    {
        $organizerId = $request->user()->id;

        $events = Event::where('organizer_id', $organizerId)
            ->withCount('registrations')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'start_time' => $event->start_time ? $event->start_time->format('Y-m-d H:i:s') : null,
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

    public function show($id)
    {
        // Lấy sự kiện cùng mối quan hệ đăng ký và thông tin user tương ứng
        $event = Event::with(['category', 'registrations.user'])->findOrFail($id);

        // 1. Lọc và định dạng danh sách người đã đăng ký chính thức (confirmed)
        $confirmedList = $event->registrations
            ->where('status', 'approved')
            ->map(function ($registration) {
                return [
                    'name' => $registration->user->name,
                    'email' => $registration->user->email,
                    'registered_at' => $registration->created_at->format('d/m/Y H:i'),
                ];
            })->values(); // Sử dụng values() để reset lại index của mảng sau khi filter

        // 2. Lọc và định dạng danh sách người đang nằm ở hàng đợi (waitlist)
        $waitlistList = $event->registrations
            ->where('status', 'waitlist')
            ->sortBy('created_at') // Sắp xếp theo thứ tự đăng ký sớm nhất lên đầu để đôn ghế chuẩn FIFO
            ->map(function ($registration) {
                return [
                    'name' => $registration->user->name,
                    'email' => $registration->user->email,
                    'registered_at' => $registration->created_at->format('d/m/Y H:i'),
                ];
            })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'location' => $event->location,
                'start_time' => $event->start_time ? $event->start_time->format('Y-m-d H:i:s') : null,
                'end_time' => $event->end_time ? $event->end_time->format('Y-m-d H:i:s') : null,
                'capacity' => $event->capacity,
                'status' => $event->status,
                'created_at' => $event->created_at->format('d/m/Y'),
                'category_name' => $event->category ? $event->category->name : 'N/A',
                'category_id' => $event->category_id,
                'image' => $event->image,

                // Số lượng tổng quan để hiển thị ở khối Widget bên phải
                'confirmed_count' => $confirmedList->count(),
                'waitlist_count' => $waitlistList->count(),

                // Trả về 2 mảng danh sách người dùng riêng biệt cho cấu trúc bảng
                'confirmed_users' => $confirmedList,
                'waitlist_users' => $waitlistList
            ]
        ], 200);
    }

    public function store(Request $request)
    {
        $organizerId = $request->user()->id;

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'location' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'category_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|string'
        ]);

        $validated['organizer_id'] = $organizerId;
        $validated['status'] = 'draft';

        $event = Event::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tạo sự kiện thành công.',
            'data' => $event
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $organizerId = $request->user()->id;
        $event = Event::where('id', $id)->where('organizer_id', $organizerId)->firstOrFail();

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'sometimes|required|date',
            'end_time' => 'sometimes|required|date|after:start_time',
            'location' => 'sometimes|required|string|max:255',
            'capacity' => 'sometimes|required|integer|min:1',
            'category_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|string',
            'status' => 'nullable|in:draft,published,cancelled'
        ]);

        if (isset($validated['status']) && $validated['status'] === 'published') {
            // Allow publishing even if previously published (no-op)
            $event->status = 'published';
            $event->save();
        } else {
            if ($event->status === 'published') {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể chỉnh sửa sự kiện đã đăng (published).'
                ], 403);
            }
            $event->update($validated);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật sự kiện thành công.',
            'data' => $event
        ]);
    }

    public function cancel(Request $request, $id)
    {
        $organizerId = $request->user()->id;
        $event = Event::where('id', $id)->where('organizer_id', $organizerId)->firstOrFail();

        $validated = $request->validate([
            'cancel_reason' => 'required|string'
        ]);

        $event->status = 'cancelled';
        $event->cancel_reason = $validated['cancel_reason'];
        $event->save();

        return response()->json([
            'success' => true,
            'message' => 'Đã hủy sự kiện thành công.',
            'data' => $event
        ]);
    }
}