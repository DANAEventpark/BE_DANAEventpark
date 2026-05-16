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

        // Check capacity
        $currentRegistrations = Registration::where('event_id', $eventId)
                                            ->whereIn('status', ['approved', 'pending'])
                                            ->count();

        if ($currentRegistrations >= $event->capacity) {
            return response()->json([
                'success' => false,
                'message' => 'Sự kiện đã hết chỗ.'
            ], 400);
        }

        // Check if already registered
        $existing = Registration::where('event_id', $eventId)
                                ->where('user_id', $user->id)
                                ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đã đăng ký sự kiện này.'
            ], 400);
        }

        $registration = Registration::create([
            'event_id' => $eventId,
            'user_id' => $user->id,
            'status' => 'approved'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đăng ký thành công.',
            'data' => $registration
        ]);
    }
}
