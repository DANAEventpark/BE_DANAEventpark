<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;

class EventController extends Controller
{
    public function index()
    {
       $events = Event::with('category')
    ->where('status', 'published')
    ->orderBy('start_time', 'asc')
    ->paginate(6);

        return response()->json($events);
    }
}