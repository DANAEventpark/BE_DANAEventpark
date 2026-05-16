<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;

class EventController extends Controller
{
    public function show($id)
    {
        $event = Event::with([
            'category',
            'organizer:id,name,email,avatar',
            'registrations' => function($query) {
                $query->where('status', 'approved')
                      ->with('user:id,name,avatar');
            },
            'reviews.user:id,name,avatar'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $event
        ]);
    }
}
