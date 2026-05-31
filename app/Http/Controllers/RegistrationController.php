<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Registration;
use Illuminate\Support\Facades\Auth;

class RegistrationController extends Controller
{
    public function store($eventId)
    {
        $user = Auth::user();
        $event = Event::findOrFail($eventId);

        // Check if already registered (any status: approved, pending, cancelled)
        $existing = Registration::where('event_id', $eventId)
                                ->where('user_id', $user->id)
                                ->first();

        if ($existing) {
            $statusText = $existing->status === 'approved' ? 'Chính thức' : ($existing->status === 'pending' ? 'Đang chờ' : 'Đã hủy');
            return response()->json([
                'success' => false,
                'message' => "Bạn đã đăng ký sự kiện này rồi (Trạng thái: {$statusText})."
            ], 400);
        }

        // Count current approved registrations
        $approvedCount = Registration::where('event_id', $eventId)
                                     ->where('status', 'approved')
                                     ->count();

        // Determine status based on capacity
        if ($approvedCount < $event->capacity) {
            $status = 'approved';
            $message = 'Đăng ký tham gia sự kiện thành công!';
        } else {
            $status = 'pending';
            $message = 'Sự kiện đã đủ chỗ. Bạn đã được thêm vào danh sách chờ.';
        }

        $registration = Registration::create([
            'event_id' => $eventId,
            'user_id' => $user->id,
            'status' => $status
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
        $event = Event::findOrFail($eventId);

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
        $registration->update([
            'status' => 'cancelled'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Hủy đăng ký tham gia sự kiện thành công!',
            'data' => $registration
        ]);
    }
}

