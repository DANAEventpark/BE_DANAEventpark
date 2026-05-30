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
            $statusText = $existing->status === 'approved' 
                ? __('messages.approved') 
                : ($existing->status === 'pending' ? __('messages.pending') : __('messages.cancelled'));
            return response()->json([
                'success' => false,
                'message' => __('messages.already_registered', ['status' => $statusText])
            ], 400);
        }

        // Count current approved registrations
        $approvedCount = Registration::where('event_id', $eventId)
                                     ->where('status', 'approved')
                                     ->count();

        // Determine status based on capacity
        if ($approvedCount < $event->capacity) {
            $status = 'approved';
            $message = __('messages.register_event_success');
        } else {
            $status = 'pending';
            $message = __('messages.register_event_waitlist');
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
