<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Collection;
use App\Models\CollectionTranslation;
use App\Models\Tag;
use App\Models\Artwork;
use App\Models\Language;
use App\Models\ArtworkCollection;
use Log;

class CollectionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Collection::query();

            // Search functionality based on a given filter
            if ($request->search && $request->filter) {
                $search = $request->search;
                $filter = $request->filter;
                $query->where($filter, 'like', '%' . $search . '%');
            }

            // Pagination setup
            $rowsPerPage = $request->input('rows', 10);
            $collections = $query->paginate($rowsPerPage);

            // Fetch all tags and artworks
            $tags = Tag::all();
            $artworks = Artwork::with('artist')->get();

            // Fetch available languages
            $languages = Language::all();

            // Get the user's preferred language, defaulting to English if not set
            $preferredLanguage = auth()->user()->preferred_language ?? 'en';

            return view('dashboard.collections.index', compact('collections', 'tags', 'artworks', 'languages', 'preferredLanguage'));
        } catch (\Exception $e) {
            Log::error($e);
            return redirect()->back()->with('error', 'Unable to load collections. Please try again later.');
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'translations' => 'required|array',
            'translations.*.language_id' => 'required|exists:languages,id',
            'translations.*.title' => 'required|string|max:50',
            'translations.*.description' => 'nullable|string|max:200',
            'tags' => 'nullable|array',
            'artworks' => 'nullable|array'
        ]);

        // Get default language (language_id = 1)
        $defaultTranslation = collect($request->translations)->firstWhere('language_id', 1);
        if (!$defaultTranslation) {
            return redirect()->back()->with('error', 'Default language translation is required.');
        }

        // Check uniqueness in default language title
        if (Collection::where('title', $defaultTranslation['title'])->exists()) {
            return redirect()->back()->with('error', 'Collection name already exists. Choose another name.');
        }

        // Create collection with the default language title and description
        $collection = Collection::create([
            'title' => $defaultTranslation['title'],
            'description' => $defaultTranslation['description'] ?? null,
            'tags' => json_encode($request->tags ?? [])
        ]);

        // Store translations
        foreach ($request->translations as $translation) {
            CollectionTranslation::create([
                'collection_id' => $collection->id,
                'language_id' => $translation['language_id'],
                'title' => $translation['title'],
                'description' => $translation['description'] ?? null
            ]);
        }

        // Store artworks
        if ($request->has('artworks')) {
            foreach ($request->artworks as $artworkId) {
                ArtworkCollection::create([
                    'collection_id' => $collection->id,
                    'artwork_id' => $artworkId
                ]);
            }
        }

        return redirect()->back()->with('success', 'Collection added successfully.');
    }

    public function show($id)
    {
        try {
            $collection = Collection::with('translations', 'translations.language')->findOrFail($id);

            // Assuming we want to get the user's preferred language (can be dynamic, e.g., from the request)
            $preferredLanguageId = auth()->user()->preferred_language; // Or pass as request parameter

            // Update collection's title and description based on the preferred language
            $translation = $collection->translations->firstWhere('language_id', $preferredLanguageId);

            if ($translation) {
                $collection->title = $translation->title;
                $collection->description = $translation->description;
            }

            // Decode the tag_ids stored in JSON format and map to tag names
            $collection->tag_ids = json_decode((string) $collection->tags, true) ?? [];

            $collection->tags = array_map(function ($tag_id) {
                $preferredLanguageId = auth()->user()->preferred_language;
                $tag = Tag::find($tag_id);
                $tagTranslation = $tag->translations->firstWhere('language_id', $preferredLanguageId);
                $tag->name = $tagTranslation->name ?? $tag->name;
                return $tag->name;
            }, $collection->tag_ids);

            // Get the artworks with artist details
            $artworks = $collection->artworks()->with('artist')->get();

            // If you want to also display translated names for artist (if available), you can update artist name similarly
            foreach ($artworks as $artwork) {
                $artistTranslation = $artwork->artist->translations->firstWhere('language_id', $preferredLanguageId);
                $artwork->name = $artwork->translations->firstWhere('language_id', $preferredLanguageId)->name ?? $artwork->name;
                if ($artistTranslation) {
                    $artwork->artist->first_name = $artistTranslation->first_name;
                    $artwork->artist->last_name = $artistTranslation->last_name;
                }
            }

            foreach ($collection->translations as $translation) {
                $translation->language->name = tt($translation->language->name);
            }

            return response()->json([
                'collection' => $collection,
                'artworks' => $artworks
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['error' => 'Unable to load collection.'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $collection = Collection::findOrFail($id);

        $request->validate([
            'translations' => 'required|array',
            'translations.*.language_id' => 'required|exists:languages,id',
            'translations.*.title' => 'required|string|max:50',
            'translations.*.description' => 'nullable|string|max:200',
            'tags' => 'nullable|array',
            'artworks' => 'nullable|array'
        ]);

        // Get default language (language_id = 1)
        $defaultTranslation = collect($request->translations)->firstWhere('language_id', 1);
        if (!$defaultTranslation) {
            return redirect()->back()->with('error', 'Default language translation is required.');
        }

        // Check uniqueness for default language title excluding current collection
        if (Collection::where('title', $defaultTranslation['title'])->where('id', '!=', $collection->id)->exists()) {
            return redirect()->back()->with('error', 'Collection name already exists. Choose another name.');
        }

        // Update collection details with default language title and description
        $collection->update([
            'title' => $defaultTranslation['title'],
            'description' => $defaultTranslation['description'] ?? null,
            'tags' => json_encode($request->tags ?? [])
        ]);

        // Update or insert translations
        foreach ($request->translations as $translation) {
            CollectionTranslation::updateOrCreate(
                ['collection_id' => $collection->id, 'language_id' => $translation['language_id']],
                ['title' => $translation['title'], 'description' => $translation['description'] ?? null]
            );
        }

        // Update artworks: Remove old and add new ones
        $collection->artworks()->detach();
        if ($request->has('artworks')) {
            foreach ($request->artworks as $artworkId) {
                ArtworkCollection::create([
                    'collection_id' => $collection->id,
                    'artwork_id' => $artworkId
                ]);
            }
        }

        return redirect()->back()->with('success', 'Collection updated successfully.');
    }

    public function destroy($id)
    {
        $collection = Collection::findOrFail($id);
        $collection->delete();
        return redirect()->back()->with('success', 'Collection deleted successfully.');
    }

    public function bulkDelete(Request $request)
    {
        $ids = json_decode($request->input('ids', []));
        $foundIds = Collection::whereIn('id', $ids)->pluck('id')->toArray();
        $notFound = array_diff($ids, $foundIds);
        if (count($notFound) > 0) {
            return redirect()->back()->with('error', 'Some collections could not be deleted as they no longer exist.');
        }
        Collection::whereIn('id', $ids)->delete();
        return redirect()->back()->with('success', 'Selected collections deleted successfully.');
    }

    // New method to toggle active status
    public function toggleActive($id)
    {
        $collection = Collection::findOrFail($id);
        $collection->active = !$collection->active;
        $collection->save();
        return response()->json(['success' => true, 'active' => $collection->active]);
    }
}
