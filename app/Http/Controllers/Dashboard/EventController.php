<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\EventTranslation;
use App\Models\Language;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    public function index()
    {
        $userPreferredLanguage = auth()->user()->preferred_language;
        $events = Event::with('translations')->paginate(10);

        foreach ($events as $event) {
            $translation = $event->translations
                ->where('language_id', $userPreferredLanguage)
                ->first();
            if ($translation) {
                $event->title = $translation->title;
                $event->description = $translation->description;
                $event->location = $translation->location;
                // Update additional fields as needed...
            }
        }

        $languages = Language::all();
        return view('dashboard.events.index', compact('events', 'languages'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'translations' => 'required|array',
            'translations.*.language_id' => 'required|exists:languages,id',
            'translations.*.title' => 'required|string|max:100',
            'translations.*.description' => 'required|string',
            'translations.*.location' => 'required|string',
            'date_start' => 'required|date',
            'date_end' => 'required|date|after_or_equal:date_start',
            'time_start' => 'required',
            'time_end' => 'required',
            'location_url' => 'nullable|url',
            'cover_img' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'status' => 'required|in:upcoming,ended',
        ], [
            'date_start.required' => 'This field is required.',
            'date_end.required' => 'This field is required.',
            'time_start.required' => 'This field is required.',
            'time_end.required' => 'This field is required.',
            'cover_img.required' => 'This field is required.',
            'cover_img.image' => 'Invalid file type or size. Please upload a valid image.',
            'cover_img.mimes' => 'Invalid file type or size. Please upload a valid image.',
            'cover_img.max' => 'Invalid file type or size. Please upload a valid image.',
        ]);

        $imagePath = $request->file('cover_img')->store('events', 'public');

        $englishTranslation = collect($request->translations)->where('language_id', 1)->first();
        $event = Event::create([
            'title' => $englishTranslation['title'],
            'description' => $englishTranslation['description'],
            'date_start' => $request->date_start,
            'date_end' => $request->date_end,
            'time_start' => $request->time_start,
            'time_end' => $request->time_end,
            'location_url' => $request->location_url,
            'cover_img_path' => $imagePath,
            'status' => $request->status,
            'expires' => $request->date_end,
        ]);

        foreach ($request->translations as $translation) {
            EventTranslation::create([
                'event_id' => $event->id,
                'language_id' => $translation['language_id'],
                'title' => $translation['title'],
                'description' => $translation['description'],
                'location' => $translation['location'],
            ]);
        }

        return redirect()->back()->with('success', 'Event added successfully.');
    }

    public function show($id)
    {
        $userPreferredLanguage = auth()->user()->preferred_language;
        $event = Event::with('translations', 'translations.language')->findOrFail($id);

        $translation = $event->translations
            ->where('language_id', $userPreferredLanguage)
            ->first();
        if ($translation) {
            $event->title = $translation->title;
            $event->description = $translation->description;
            $event->location = $translation->location;
            // Update additional fields as needed...
        }

        foreach ($event->translations as $translation) {
            $translation->language->name = tt($translation->language->name);
        }

        return response()->json(['event' => $event]);
    }

    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        $request->validate([
            'translations' => 'required|array',
            'translations.*.language_id' => 'required|exists:languages,id',
            'translations.*.title' => 'required|string|max:100',
            'translations.*.description' => 'required|string',
            'translations.*.location' => 'required|string',
            'date_start' => 'required|date',
            'date_end' => 'required|date|after_or_equal:date_start',
            'time_start' => 'required',
            'time_end' => 'required',
            'location_url' => 'nullable|url',
            'cover_img' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'status' => 'required|in:upcoming,ended',
        ], [
            'date_start.required' => 'This field is required.',
            'date_end.required' => 'This field is required.',
            'time_start.required' => 'This field is required.',
            'time_end.required' => 'This field is required.',
            'cover_img.required' => 'This field is required.',
            'cover_img.image' => 'Invalid file type or size. Please upload a valid image.',
            'cover_img.mimes' => 'Invalid file type or size. Please upload a valid image.',
            'cover_img.max' => 'Invalid file type or size. Please upload a valid image.',
        ]);

        if ($request->hasFile('cover_img')) {
            Storage::disk('public')->delete($event->cover_img_path);
            $imagePath = $request->file('cover_img')->store('events', 'public');
            $event->update(['cover_img_path' => $imagePath]);
        }

        $event->update([
            'date_start' => $request->date_start,
            'date_end' => $request->date_end,
            'time_start' => $request->time_start,
            'time_end' => $request->time_end,
            'location_url' => $request->location_url,
            'status' => $request->status,
            'expires' => $request->date_end,
        ]);

        foreach ($request->translations as $translation) {
            EventTranslation::updateOrCreate(
                ['event_id' => $event->id, 'language_id' => $translation['language_id']],
                [
                    'title' => $translation['title'],
                    'description' => $translation['description'],
                    'location' => $translation['location'],
                ]
            );
        }

        return redirect()->back()->with('success', 'Event updated successfully.');
    }

    public function destroy($id)
    {
        $event = Event::findOrFail($id);
        Storage::disk('public')->delete($event->cover_img_path);
        $event->delete();
        return redirect()->back()->with('success', 'Event deleted successfully.');
    }

    public function bulkDelete(Request $request)
    {
        $ids = json_decode($request->input('ids', []));

        if (empty($ids)) {
            return redirect()->back()->with('error', 'No events selected for deletion.');
        }

        Event::whereIn('id', $ids)->delete();

        return redirect()->back()->with('success', 'Selected events deleted successfully.');
    }

    public function bulkPublish(Request $request)
    {
        $ids = json_decode($request->input('ids', []));

        if (empty($ids)) {
            return redirect()->back()->with('error', 'No events selected for publishing.');
        }

        Event::whereIn('id', $ids)->update(['status' => 'upcoming']);

        return redirect()->back()->with('success', 'Selected events published successfully.');
    }

    public function bulkUnpublish(Request $request)
    {
        $ids = json_decode($request->input('ids', []));
        if (empty($ids)) {
            return redirect()->back()->with('error', 'No events selected for unpublishing.');
        }
        // Set status to "ended" instead of "unpublished"
        Event::whereIn('id', $ids)->update(['status' => 'ended']);
        return redirect()->back()->with('success', 'Selected events unpublished successfully.');
    }

}
