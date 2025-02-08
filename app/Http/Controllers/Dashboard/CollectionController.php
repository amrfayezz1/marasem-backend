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
            $userPreferredLanguage = auth()->user()->preferred_language;
            $query = Collection::query();

            if ($request->has('search') && $request->has('filter')) {
                $search = $request->input('search');
                $filter = $request->input('filter');
                if ($filter === 'title') {
                    // Search the main title field and its translation title for the preferred language.
                    $query->where(function ($q) use ($search, $userPreferredLanguage) {
                        $q->where('title', 'like', '%' . $search . '%')
                            ->orWhereHas('translations', function ($q2) use ($search, $userPreferredLanguage) {
                                $q2->where('language_id', $userPreferredLanguage)
                                    ->where('title', 'like', '%' . $search . '%');
                            });
                    });
                } else {
                    // For any other filter, search the specified column.
                    $query->where($filter, 'like', '%' . $search . '%');
                }
            }

            $rowsPerPage = $request->input('rows', 10);
            $collections = $query->paginate($rowsPerPage);

            foreach ($collections as $collection) {
                // Translate the collection title and description
                $mainTranslation = $collection->translations
                    ->where('language_id', $userPreferredLanguage)
                    ->first();

                if ($mainTranslation) {
                    $collection->title = $mainTranslation->title;
                    $collection->description = $mainTranslation->description;
                }

                // Decode tags (ensuring we get an array) and translate each tag
                $tagsArray = is_array($collection->tags)
                    ? $collection->tags
                    : json_decode($collection->tags, true);

                $translatedTags = [];
                foreach ($tagsArray as $tagId) {
                    $tagModel = Tag::find($tagId);
                    if ($tagModel) {
                        $tagTranslation = $tagModel->translations
                            ->where('language_id', $userPreferredLanguage)
                            ->first();
                        $translatedTags[] = $tagTranslation ? $tagTranslation->name : $tagModel->name;
                    }
                }
                $collection->tags = $translatedTags;
            }

            // Translate all tags for the tag list
            $tags = Tag::all();
            foreach ($tags as $tag) {
                $tagTranslation = $tag->translations
                    ->where('language_id', $userPreferredLanguage)
                    ->first();
                if ($tagTranslation) {
                    $tag->name = $tagTranslation->name;
                }
            }

            // Translate artworks and, separately, their associated artist details
            $artworks = Artwork::with('artist')->get();
            foreach ($artworks as $artwork) {
                // Translate artwork title
                $artworkTranslation = $artwork->translations
                    ->where('language_id', $userPreferredLanguage)
                    ->first();
                if ($artworkTranslation) {
                    $artwork->name = $artworkTranslation->name;
                }

                // Translate artist details if available.
                if ($artwork->artist && method_exists($artwork->artist, 'translations')) {
                    $artistTranslation = $artwork->artist->translations
                        ->where('language_id', $userPreferredLanguage)
                        ->first();
                    if ($artistTranslation) {
                        $artwork->artist->first_name = $artistTranslation->first_name;
                        $artwork->artist->last_name = $artistTranslation->last_name;
                    }
                }
            }

            $languages = Language::all();

            return view('dashboard.collections.index', compact('collections', 'tags', 'artworks', 'languages'));
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
            $userPreferredLanguage = auth()->user()->preferred_language;
            $collection = Collection::with('translations', 'translations.language')->findOrFail($id);

            // Translate the collection title and description
            $mainTranslation = $collection->translations
                ->where('language_id', $userPreferredLanguage)
                ->first();
            if ($mainTranslation) {
                $collection->title = $mainTranslation->title;
                $collection->description = $mainTranslation->description;
            }

            // Decode tags and translate each tag
            $collection->tag_ids = json_decode((string) $collection->tags, true) ?? [];
            $translatedTags = [];
            foreach ($collection->tag_ids as $tagId) {
                $tagModel = Tag::find($tagId);
                if ($tagModel) {
                    $tagTranslation = $tagModel->translations
                        ->where('language_id', $userPreferredLanguage)
                        ->first();
                    $translatedTags[] = $tagTranslation ? $tagTranslation->name : $tagModel->name;
                }
            }
            $collection->tags = $translatedTags;

            // Retrieve artworks with their translations and associated artist translations
            $artworks = $collection->artworks()
                ->with('artist.translations', 'translations')
                ->get();

            foreach ($artworks as $artwork) {
                // Translate artwork title (or name, as used in your index function)
                $artworkTranslation = $artwork->translations
                    ->where('language_id', $userPreferredLanguage)
                    ->first();
                if ($artworkTranslation) {
                    $artwork->name = $artworkTranslation->name;
                }

                // Translate associated artist details if available
                if ($artwork->artist && method_exists($artwork->artist, 'translations')) {
                    $artistTranslation = $artwork->artist->translations
                        ->where('language_id', $userPreferredLanguage)
                        ->first();
                    if ($artistTranslation) {
                        $artwork->artist->first_name = $artistTranslation->first_name;
                        $artwork->artist->last_name = $artistTranslation->last_name;
                    }
                }
            }
            foreach ($collection->translations as $translation) {
                $translation->language->name = tt($translation->language->name);
            }

            return response()->json([
                'collection' => $collection,
                'artworks' => $artworks,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                'error' => 'Unable to load collection. Please try again later.'
            ], 500);
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
