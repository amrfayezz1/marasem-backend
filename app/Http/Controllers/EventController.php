<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Language;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * @OA\Get(
     *     path="/events",
     *     summary="Retrieve a list of upcoming events",
     *     tags={"Events"},
     *     @OA\Response(
     *         response=200,
     *         description="List of events retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1, description="Event ID"),
     *                 @OA\Property(property="title", type="string", example="Art Exhibition 2024", description="Event title"),
     *                 @OA\Property(property="description", type="string", example="A showcase of modern art.", description="Event description"),
     *                 @OA\Property(property="date_start", type="string", format="date", example="2024-03-15", description="Start date of the event"),
     *                 @OA\Property(property="date_end", type="string", format="date", nullable=true, example="2024-03-18", description="End date of the event"),
     *                 @OA\Property(property="time_start", type="string", format="time", nullable=true, example="10:00:00", description="Start time of the event"),
     *                 @OA\Property(property="time_end", type="string", format="time", nullable=true, example="18:00:00", description="End time of the event"),
     *                 @OA\Property(property="location", type="string", example="Downtown Art Gallery", description="Event location"),
     *                 @OA\Property(property="location_url", type="string", format="url", nullable=true, example="https://maps.google.com?q=downtown-art-gallery", description="Google Maps link to the location"),
     *                 @OA\Property(property="cover_img_path", type="string", format="url", nullable=true, example="https://example.com/event-cover.jpg", description="URL to the event cover image"),
     *                 @OA\Property(property="status", type="string", example="active", description="Status of the event"),
     *                 @OA\Property(property="expires", type="string", format="date", nullable=true, example="2024-03-19", description="Expiration date of the event, if applicable")
     *             )
     *         )
     *     )
     * )
     */

    public function getEvents()
    {
        $user = auth('sanctum')->user();
        $preferredLanguageId = $user ? $user->preferred_language : Language::where('code', request()->cookie('locale', 'en'))->first()->id;

        $events = Event::where(function ($query) {
            $query->whereNull('expires')
                ->orWhere('expires', '>=', now());
        })
            ->with([
                'translations' => function ($query) use ($preferredLanguageId) {
                    $query->where('language_id', $preferredLanguageId);
                }
            ])
            ->orderBy('date_start', 'asc')
            ->get();

        $localizedEvents = $events->map(function ($event) {
            $translation = $event->translations->first();
            return [
                'id' => $event->id,
                'title' => $translation->title ?? $event->title,
                'description' => $translation->description ?? $event->description,
                'date_start' => $event->date_start,
                'date_end' => $event->date_end,
                'time_start' => $event->time_start,
                'time_end' => $event->time_end,
                'location' => $translation->location ?? $event->location,
                'location_url' => $event->location_url,
                'cover_img_path' => $event->cover_img_path,
                'status' => $event->status,
                'expires' => $event->expires,
            ];
        });

        return response()->json($localizedEvents);
    }
}
