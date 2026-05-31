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

        // 1. Tổng sự kiện do organizer này tổ chức
        $totalEvents = Event::where('organizer_id', $organizerId)->count();

        // 2. Tối ưu: Dùng Join hoặc whereHas đếm trực tiếp trên CSDL thay vì tải toàn bộ sự kiện vào RAM
        $totalRegistrations = \App\Models\Registration::whereHas('event', function($query) use ($organizerId) {
             $query->where('organizer_id', $organizerId);
        })->count();

        // 3. Tối ưu: Tính trung bình trực tiếp bằng SQL Aggregate Function thay vì vòng lặp foreach trong PHP
        $avgRatingRaw = \App\Models\Review::whereHas('event', function($query) use ($organizerId) {
             $query->where('organizer_id', $organizerId);
        })->avg('rating');

        $averageRating = $avgRatingRaw ? round($avgRatingRaw, 1) : 0;

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
        // Lấy sự kiện cùng mối quan hệ đăng ký, thông tin user tương ứng và Đánh giá (reviews)
        $event = Event::with(['category', 'registrations.user', 'reviews.user:id,name,avatar'])->findOrFail($id);

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
            ->where('status', 'pending')
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

                'confirmed_users' => $confirmedList,
                'waitlist_users' => $waitlistList,

                // Thêm dữ liệu Đánh giá
                'reviews' => $event->reviews->map(function ($review) {
                    return [
                        'id' => $review->id,
                        'rating' => $review->rating,
                        'comment' => $review->comment,
                        'created_at' => $review->created_at->format('d/m/Y H:i'),
                        'user' => [
                            'name' => $review->user->name ?? 'Người dùng',
                            'avatar' => $review->user->avatar ?? null
                        ]
                    ];
                })->sortByDesc('created_at')->values(),
                'average_rating' => $event->reviews->count() > 0 ? round($event->reviews->avg('rating'), 1) : null,
                'total_reviews' => $event->reviews->count()
            ]
        ], 200);
    }

    public function updateStatus(Request $request, $id)
    {
        $organizerId = $request->user()->id;
        $event = Event::where('organizer_id', $organizerId)->findOrFail($id);

        $request->validate([
            'status' => 'required|in:draft,published,cancelled'
        ]);

        if ($event->status === 'done') {
            return response()->json([
                'success' => false,
                'message' => 'Sự kiện đã hoàn thành và bị khóa, không thể thay đổi trạng thái.'
            ], 403);
        }

        $event->update([
            'status' => $request->status
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái thành công',
            'data' => $event
        ]);
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