<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Registration;
use App\Mail\EventCancelledMail;
use Illuminate\Support\Facades\Mail;

class OrganizerEventController extends Controller
{
    /**
     * Lấy các chỉ số thống kê tổng quan cho Dashboard
     */
    public function getDashboardStats(Request $request)
    {
        $organizerId = $request->user()->id;

        $totalEvents = Event::where('organizer_id', $organizerId)->count();

        $totalRegistrations = \App\Models\Registration::whereHas('event', function ($query) use ($organizerId) {
            $query->where('organizer_id', $organizerId);
        })->where('status', 'approved')->sum('seats');

        $avgRatingRaw = \App\Models\Review::whereHas('event', function ($query) use ($organizerId) {
            $query->where('organizer_id', $organizerId);
        })->avg('rating');

        $averageRating = $avgRatingRaw ? round($avgRatingRaw, 1) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'total_events'        => $totalEvents,
                'total_registrations' => $totalRegistrations,
                'average_rating'      => $averageRating,
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
            ->withSum(['registrations as approved_seats_sum' => function ($q) {
                $q->where('status', 'approved');
            }], 'seats')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($event) {
                return [
                    'id'                  => $event->id,
                    'title'               => $event->title,
                    'description'         => $event->description,
                    'location'            => $event->location,
                    'start_time'          => $event->start_time ? $event->start_time->format('Y-m-d H:i:s') : null,
                    'end_time'            => $event->end_time ? $event->end_time->format('Y-m-d H:i:s') : null,
                    'registration_deadline' => $event->registration_deadline ? $event->registration_deadline->format('Y-m-d H:i:s') : null,
                    'category_id'         => $event->category_id,
                    'image'               => $event->image,
                    'registrations_count' => (int) ($event->approved_seats_sum ?? 0),
                    'capacity'            => $event->capacity,
                    'status'              => $event->status,
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $events,
        ]);
    }

    /**
     * Lấy danh sách tất cả sự kiện của Organizer
     */
    public function index(Request $request)
    {
        $organizerId = $request->user()->id;

        $events = Event::where('organizer_id', $organizerId)
            ->withSum(['registrations as approved_seats_sum' => function ($q) {
                $q->where('status', 'approved');
            }], 'seats')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($event) {
                return [
                    'id'                  => $event->id,
                    'title'               => $event->title,
                    'description'         => $event->description,
                    'location'            => $event->location,
                    'start_time'          => $event->start_time ? $event->start_time->format('Y-m-d H:i:s') : null,
                    'end_time'            => $event->end_time ? $event->end_time->format('Y-m-d H:i:s') : null,
                    'registration_deadline' => $event->registration_deadline ? $event->registration_deadline->format('Y-m-d H:i:s') : null,
                    'category_id'         => $event->category_id,
                    'image'               => $event->image,
                    'registrations_count' => (int) ($event->approved_seats_sum ?? 0),
                    'capacity'            => $event->capacity,
                    'status'              => $event->status,
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $events,
        ]);
    }

    /**
     * Lấy chi tiết sự kiện — chỉ cho phép organizer xem sự kiện của chính mình
     */
    public function show(Request $request, $id)
    {
        $organizerId = $request->user()->id;

        $event = Event::with(['category', 'registrations.user', 'reviews.user:id,name,avatar'])
            ->where('organizer_id', $organizerId)
            ->findOrFail($id);

        $confirmedList = $event->registrations
            ->where('status', 'approved')
            ->map(function ($reg) {
                return [
                    'name'          => $reg->user->name,
                    'email'         => $reg->user->email,
                    'seats'         => $reg->seats ?? 1,
                    'registered_at' => $reg->created_at->format('d/m/Y H:i'),
                ];
            })->values();

        $waitlistList = $event->registrations
            ->where('status', 'pending')
            ->sortBy('created_at')
            ->map(function ($reg) {
                return [
                    'name'          => $reg->user->name,
                    'email'         => $reg->user->email,
                    'seats'         => $reg->seats ?? 1,
                    'registered_at' => $reg->created_at->format('d/m/Y H:i'),
                ];
            })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'id'              => $event->id,
                'title'           => $event->title,
                'description'     => $event->description,
                'location'        => $event->location,
                'start_time'      => $event->start_time ? $event->start_time->format('Y-m-d H:i:s') : null,
                'end_time'        => $event->end_time   ? $event->end_time->format('Y-m-d H:i:s')   : null,
                'capacity'        => $event->capacity,
                'status'          => $event->status,
                'created_at'      => $event->created_at->format('d/m/Y'),
                'category_name'   => $event->category ? $event->category->name : 'N/A',
                'category_id'     => $event->category_id,
                'image'           => $event->image,
                'confirmed_count' => $confirmedList->sum('seats'),
                'waitlist_count'  => $waitlistList->sum('seats'),
                'confirmed_users' => $confirmedList,
                'waitlist_users'  => $waitlistList,
                'reviews' => $event->reviews->map(function ($review) {
                    return [
                        'id'         => $review->id,
                        'rating'     => $review->rating,
                        'comment'    => $review->comment,
                        'created_at' => $review->created_at->format('d/m/Y H:i'),
                        'user'       => [
                            'name'   => $review->user->name ?? 'Người dùng',
                            'avatar' => $review->user->avatar ?? null,
                        ],
                    ];
                })->sortByDesc('created_at')->values(),
                'average_rating' => $event->reviews->count() > 0
                    ? round($event->reviews->avg('rating'), 1)
                    : null,
                'total_reviews'  => $event->reviews->count(),
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
                'message' => 'Sự kiện đã hoàn thành và bị khóa, không thể thay đổi trạng thái.',
            ], 403);
        }

        $event->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái thành công',
            'data'    => $event,
        ]);
    }

    public function store(Request $request)
    {
        $organizerId = $request->user()->id;

        $validated = $request->validate([
            'title'                 => 'required|string|max:255',
            'description'           => 'nullable|string',
            'start_time'            => 'required|date|after_or_equal:today',
            'end_time'              => 'required|date|after:start_time',
            'location'              => 'required|string|max:255',
            'capacity'              => 'required|integer|min:1|max:100000',
            'category_id'           => 'nullable|exists:categories,id',
            'image'                 => 'nullable|string',
            'status'                => 'nullable|in:draft,published',
            'registration_deadline' => 'nullable|date',
        ], [
            'start_time.after_or_equal' => 'Thời gian bắt đầu không thể ở quá khứ.',
            'capacity.max'              => 'Sức chứa không được vượt quá 100.000 người.',
        ]);

        $validated['organizer_id'] = $organizerId;
        $validated['status']       = $validated['status'] ?? 'draft';

        $event = Event::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tạo sự kiện thành công.',
            'data'    => $event,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $organizerId = $request->user()->id;
        $event = Event::where('id', $id)->where('organizer_id', $organizerId)->firstOrFail();

        // Chặn sửa nội dung sự kiện đã published (chỉ cho phép nếu chỉ cập nhật status)
        if ($event->status === 'published' && count($request->except(['status'])) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể chỉnh sửa sự kiện đã đăng (published). Hãy dùng chức năng đổi trạng thái.',
            ], 403);
        }

        $validated = $request->validate([
            'title'                 => 'sometimes|required|string|max:255',
            'description'           => 'nullable|string',
            'start_time'            => 'sometimes|required|date|after_or_equal:today',
            'end_time'              => 'sometimes|required|date|after:start_time',
            'location'              => 'sometimes|required|string|max:255',
            'capacity'              => 'sometimes|required|integer|min:1|max:100000',
            'category_id'           => 'nullable|exists:categories,id',
            'image'                 => 'nullable|string',
            'status'                => 'nullable|in:draft,published,cancelled',
            'registration_deadline' => 'nullable|date',
        ], [
            'start_time.after_or_equal' => 'Thời gian bắt đầu không thể ở quá khứ.',
            'capacity.max'              => 'Sức chứa không được vượt quá 100.000 người.',
        ]);

        $event->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật sự kiện thành công.',
            'data'    => $event->fresh(),
        ]);
    }

    public function cancel(Request $request, $id)
    {
        $organizerId = $request->user()->id;
        $event = Event::where('id', $id)->where('organizer_id', $organizerId)->firstOrFail();

        $validated = $request->validate([
            'cancel_reason' => 'required|string'
        ]);

        $event->status        = 'cancelled';
        $event->cancel_reason = $validated['cancel_reason'];
        $event->save();

        $event->load('category');

        // Gửi email thông báo hủy cho tất cả người đã đăng ký (approved + pending)
        $registrations = Registration::where('event_id', $event->id)
            ->whereIn('status', ['approved', 'pending'])
            ->with('user:id,name,email')
            ->get();

        foreach ($registrations as $registration) {
            if ($registration->user && $registration->user->email) {
                Mail::to($registration->user->email)
                    ->queue(new EventCancelledMail($event, $registration->user));
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã hủy sự kiện thành công. Email thông báo đã được gửi đến người đăng ký.',
            'data'    => $event,
        ]);
    }
}
