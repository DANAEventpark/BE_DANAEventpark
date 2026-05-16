<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function store(Request $request, $eventId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string',
        ]);

        $user = Auth::user();
        $event = Event::findOrFail($eventId);

        $review = Review::create([
            'event_id' => $eventId,
            'user_id' => $user->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        // Load user info for response
        $review->load('user:id,name,avatar');

        return response()->json([
            'success' => true,
            'message' => 'Bình luận thành công.',
            'data' => $review
        ]);
    }
}
