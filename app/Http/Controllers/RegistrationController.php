<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Registration;
use Illuminate\Support\Facades\Auth;

class RegistrationController extends Controller
{
    public function store(Request $request, $eventId)
    {
        $user = Auth::user();
        $event = Event::find($eventId);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => __('messages.event_not_found'),
            ], 404);
        }

        if ($event->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Sự kiện đã bị hủy, không thể đăng ký.',
            ], 400);
        }

        if ($event->status !== 'published') {
            return response()->json([
                'success' => false,
                'message' => 'Sự kiện hiện chưa mở đăng ký.',
            ], 400);
        }

        if ($event->end_time && now()->greaterThan($event->end_time)) {
            return response()->json([
                'success' => false,
                'message' => 'Sự kiện đã kết thúc, không thể đăng ký.',
            ], 400);
        }

        if ($event->registration_deadline && now()->greaterThan($event->registration_deadline)) {
            return response()->json([
                'success' => false,
                'message' => 'Sự kiện đã hết hạn đăng ký.',
            ], 400);
        }

        $validated = $request->validate([
            'registration_type' => 'nullable|in:individual,organization',
            'organization_name' => 'required_if:registration_type,organization|nullable|string|max:255',
            'quantity' => 'nullable|integer|min:1|max:100',
        ], [
            'organization_name.required_if' => 'Vui lòng nhập tên tổ chức.',
            'quantity.integer' => 'Số lượng chỗ đăng ký phải là số nguyên.',
            'quantity.min' => 'Số lượng chỗ đăng ký tối thiểu là 1.',
            'quantity.max' => 'Số lượng chỗ đăng ký tối đa mỗi lần là 100.',
        ]);

        // Check if already registered (any status: approved, pending, cancelled)
        $existing = Registration::where('event_id', $eventId)
                                ->where('user_id', $user->id)
                                ->first();

        if ($existing) {
            $statusText = $existing->status === 'approved'
                ? __('messages.approved')
                : ($existing->status === 'pending' ? __('messages.pending') : __('messages.cancelled'));
            return response()->json([
                'success' => false,
                'message' => __('messages.already_registered', ['status' => $statusText])
            ], 400);
        }

        $registrationType = $validated['registration_type'] ?? 'individual';
        $quantity = $registrationType === 'organization' ? (int) ($validated['quantity'] ?? 1) : 1;
        $organizationName = $registrationType === 'organization' ? trim((string) ($validated['organization_name'] ?? '')) : null;

        // Count current approved seats
        $approvedSeats = (int) Registration::where('event_id', $eventId)
                                           ->where('status', 'approved')
                                           ->sum('quantity');

        // Determine status based on remaining capacity
        if ($approvedSeats + $quantity <= $event->capacity) {
            $status = 'approved';
            $message = __('messages.register_event_success');
        } else {
            $status = 'pending';
            $message = __('messages.register_event_waitlist');
        }

        $registration = Registration::create([
            'event_id' => $eventId,
            'user_id' => $user->id,
            'status' => $status,
            'registration_type' => $registrationType,
            'organization_name' => $organizationName,
            'quantity' => $quantity,
        ]);

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $registration
        ]);
    }

    public function cancel($eventId)
    {
        $user = Auth::user();
        $event = Event::find($eventId);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => __('messages.event_not_found'),
            ], 404);
        }

        // Tìm đăng ký hiện tại có trạng thái approved hoặc pending của người dùng này
        $registration = Registration::where('event_id', $eventId)
                                    ->where('user_id', $user->id)
                                    ->whereIn('status', ['approved', 'pending'])
                                    ->first();

        if (!$registration) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa đăng ký tham gia sự kiện này hoặc đăng ký đã bị hủy trước đó.'
            ], 400);
        }

        // Kiểm tra thời hạn hủy (chưa tới hạn đóng đăng ký)
        $now = now();
        $deadline = $event->registration_deadline;

        if ($deadline && $now->greaterThanOrEqualTo($deadline)) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể hủy đăng ký. Đã qua hạn đóng đăng ký của sự kiện này.'
            ], 400);
        }

        // Cập nhật trạng thái thành cancelled
        $wasApproved = $registration->status === 'approved';
        $registration->update([
            'status' => 'cancelled'
        ]);

        // Tự động đẩy người tiếp theo trong danh sách chờ lên nếu đủ chỗ trống
        if ($wasApproved) {
            $approvedSeats = (int) Registration::where('event_id', $eventId)
                ->where('status', 'approved')
                ->sum('quantity');

            $pendingList = Registration::where('event_id', $eventId)
                ->where('status', 'pending')
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($pendingList as $pending) {
                $needed = (int) ($pending->quantity ?? 1);
                if ($approvedSeats + $needed <= $event->capacity) {
                    $pending->update(['status' => 'approved']);
                    $approvedSeats += $needed;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Hủy đăng ký tham gia sự kiện thành công!',
            'data' => $registration
        ]);
    }
}
