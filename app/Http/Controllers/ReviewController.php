<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Review;
use App\Models\Registration;
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

        // Check if the user has an approved registration for this event
        $isRegistered = Registration::where('event_id', $eventId)
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->exists();

        if (!$isRegistered) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn phải đăng ký và được chấp nhận tham gia sự kiện này mới có thể đánh giá!'
            ], 403);
        }

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

    public function stream($eventId)
    {
        return response()->stream(function () use ($eventId) {
            // Disable output buffering to ensure real-time delivery
            if (function_exists('apache_setenv')) {
                @apache_setenv('no-gzip', '1');
            }
            @ini_set('zlib.output_compression', 0);
            @ini_set('implicit_flush', 1);
            ob_implicit_flush(true);

            $lastReviewId = request()->query('last_id', 0);

            // Maximum loop execution to prevent resource leak (10 minutes session)
            $retryCount = 0;
            $maxRetries = 600;

            while ($retryCount < $maxRetries) {
                // Check if browser closed connection
                if (connection_aborted()) {
                    break;
                }

                // Check for new reviews
                $newReviews = Review::with('user:id,name,avatar')
                    ->where('event_id', $eventId)
                    ->where('id', '>', $lastReviewId)
                    ->orderBy('id', 'asc')
                    ->get();

                if ($newReviews->isNotEmpty()) {
                    foreach ($newReviews as $review) {
                        echo "data: " . json_encode($review) . "\n\n";
                        $lastReviewId = $review->id;
                    }
                    ob_flush();
                    flush();
                } else {
                    // Send keep-alive heartbeat comment
                    echo ": heartbeat\n\n";
                    ob_flush();
                    flush();
                }

                sleep(1);
                $retryCount++;
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no', // For Nginx support
        ]);
    }
}

