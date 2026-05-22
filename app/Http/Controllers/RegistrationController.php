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
}
