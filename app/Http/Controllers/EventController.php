<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function getEvents()
    {
        $events = Event::where(function ($query) {
                $query->whereNull('expires')
                    ->orWhere('expires', '>=', now());
            })
            ->orderBy('date_start', 'asc')
            ->get();

        return response()->json($events);
    }
}
