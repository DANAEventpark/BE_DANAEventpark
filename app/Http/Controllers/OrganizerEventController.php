<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;

class OrganizerEventController extends Controller
{
    private const MAX_EVENT_CAPACITY = 100000;

    /**
     * Format event data consistently for dashboard and event list responses.
     */
    private function formatEventSummary(Event $event): array
    {
        return [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'location' => $event->location,
            'category_id' => $event->category_id,
            'image' => $event->image,
            'start_time' => $event->start_time ? $event->start_time->format('Y-m-d H:i:s') : null,
            'end_time' => $event->end_time ? $event->end_time->format('Y-m-d H:i:s') : null,
            'registration_deadline' => $event->registration_deadline ? $event->registration_deadline->format('Y-m-d H:i:s') : null,
            'registrations_count' => $event->registrations_count,
            'capacity' => $event->capacity,
            'status' => $event->status,
            'cancel_reason' => $event->cancel_reason,
        ];
    }

    /**
     * Custom Vietnamese validation messages for event creation and updates.
     */
    private function eventValidationMessages(): array
    {
        return [
            'start_time.after' => 'Thời gian bắt đầu phải ở trong tương lai (sau thời điểm hiện tại).',
            'end_time.after' => 'Thời gian kết thúc phải sau thời gian bắt đầu.',
            'registration_deadline.before_or_equal' => 'Hạn đăng ký phải trước hoặc bằng thời gian bắt đầu sự kiện.',
            'capacity.integer' => 'Sức chứa phải là số nguyên hợp lệ.',
            'capacity.min' => 'Sức chứa phải tối thiểu là 1 người.',
            'capacity.max' => 'Sức chứa không được vượt quá 100.000 người.',
        ];
    }

    /**
     * Lấy các chỉ số thống kê tổng quan cho Dashboard
     */
    public function getDashboardStats(Request $request)
    {
        $organizerId = $request->user()->id;

        $totalEvents = Event::where('organizer_id', $organizerId)->count();

        $totalRegistrations = \App\Models\Registration::whereHas('event', function ($query) use ($organizerId) {
            $query->where('organizer_id', $organizerId);
        })->count();

        $avgRatingRaw = \App\Models\Review::whereHas('event', function ($query) use ($organizerId) {
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
            ->map(fn (Event $event) => $this->formatEventSummary($event));

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
            ->map(fn (Event $event) => $this->formatEventSummary($event));

        return response()->json([
            'success' => true,
            'data' => $events
        ]);
    }

    public function show(Request $request, $id)
    {
        $organizerId = $request->user()->id;

        $event = Event::with(['category', 'registrations.user', 'reviews.user:id,name,avatar'])
            ->where('organizer_id', $organizerId)
            ->find($id);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => __('messages.event_not_found'),
            ], 404);
        }

        $confirmedList = $event->registrations
            ->where('status', 'approved')
            ->map(function ($registration) {
                return [
                    'name' => $registration->user->name,
                    'email' => $registration->user->email,
                    'registration_type' => $registration->registration_type ?? 'individual',
                    'organization_name' => $registration->organization_name,
                    'quantity' => $registration->quantity ?? 1,
                    'registered_at' => $registration->created_at->format('d/m/Y H:i'),
                ];
            })->values();

        $waitlistList = $event->registrations
            ->where('status', 'pending')
            ->sortBy('created_at')
            ->map(function ($registration) {
                return [
                    'name' => $registration->user->name,
                    'email' => $registration->user->email,
                    'registration_type' => $registration->registration_type ?? 'individual',
                    'organization_name' => $registration->organization_name,
                    'quantity' => $registration->quantity ?? 1,
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
                'registration_deadline' => $event->registration_deadline ? $event->registration_deadline->format('Y-m-d H:i:s') : null,
                'capacity' => $event->capacity,
                'status' => $event->status,
                'cancel_reason' => $event->cancel_reason,
                'created_at' => $event->created_at->format('d/m/Y'),
                'category_name' => $event->category ? $event->category->name : 'N/A',
                'category_id' => $event->category_id,
                'image' => $event->image,
                'confirmed_count' => $confirmedList->sum('quantity'),
                'waitlist_count' => $waitlistList->sum('quantity'),
                'confirmed_users' => $confirmedList,
                'waitlist_users' => $waitlistList,
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
        $event = Event::where('organizer_id', $organizerId)->find($id);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => __('messages.event_not_found'),
            ], 404);
        }

        $request->validate([
            'status' => 'required|in:draft,published,cancelled'
        ]);

        if ($event->status === 'done') {
            return response()->json([
                'success' => false,
                'message' => 'Sự kiện đã hoàn thành và bị khóa, không thể thay đổi trạng thái.'
            ], 403);
        }

        if ($request->status === 'published' && $event->start_time && $event->start_time->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xuất bản sự kiện có thời gian bắt đầu ở quá khứ.'
            ], 422);
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
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
            'location' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1|max:' . self::MAX_EVENT_CAPACITY,
            'category_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|string',
            'status' => 'nullable|in:draft,published',
            'registration_deadline' => 'nullable|date|before_or_equal:start_time'
        ], $this->eventValidationMessages());

        $validated['organizer_id'] = $organizerId;
        if (!isset($validated['status'])) {
            $validated['status'] = 'draft';
        }

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
        $event = Event::where('id', $id)->where('organizer_id', $organizerId)->first();

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => __('messages.event_not_found'),
            ], 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'sometimes|required|date|after:now',
            'end_time' => 'sometimes|required|date|after:start_time',
            'location' => 'sometimes|required|string|max:255',
            'capacity' => 'sometimes|required|integer|min:1|max:' . self::MAX_EVENT_CAPACITY,
            'category_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|string',
            'status' => 'nullable|in:draft,published,cancelled',
            'registration_deadline' => 'nullable|date|before_or_equal:start_time'
        ], $this->eventValidationMessages());

        if (isset($validated['status']) && $validated['status'] === 'published' && count($validated) === 1) {
            if ($event->start_time && $event->start_time->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xuất bản sự kiện có thời gian bắt đầu ở quá khứ.'
                ], 422);
            }
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
        $event = Event::where('id', $id)->where('organizer_id', $organizerId)->first();

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => __('messages.event_not_found'),
            ], 404);
        }

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
