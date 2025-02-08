<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use Illuminate\Http\Request;
use App\Models\Artwork;
use App\Models\Category;
use App\Models\ArtworkTag;
use App\Models\Language;
use App\Models\User;
use App\Models\Tag;
use Illuminate\Support\Facades\Storage;

class ArtworkController extends Controller
{
    public function index(Request $request)
    {
        $userPreferredLanguage = auth()->user()->preferred_language;
        $query = Artwork::with(['artist', 'collections', 'tags', 'translations']);

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search, $userPreferredLanguage) {
                // Search in the artwork's own name (and exact match for id)
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('id', $search)
                    // Search in artwork translations (name)
                    ->orWhereHas('translations', function ($q) use ($search, $userPreferredLanguage) {
                        $q->where('language_id', $userPreferredLanguage)
                            ->where('name', 'LIKE', "%{$search}%");
                    })
                    // Search in the artist's base name (first or last)
                    ->orWhereHas('artist', function ($q) use ($search) {
                        $q->where('first_name', 'LIKE', "%{$search}%")
                            ->orWhere('last_name', 'LIKE', "%{$search}%");
                    })
                    // Search in the artist's translations (first or last name)
                    ->orWhereHas('artist.translations', function ($q) use ($search, $userPreferredLanguage) {
                        $q->where('language_id', $userPreferredLanguage)
                            ->where(function ($q2) use ($search) {
                                $q2->where('first_name', 'LIKE', "%{$search}%")
                                    ->orWhere('last_name', 'LIKE', "%{$search}%");
                            });
                    })
                    // Search in collection base title
                    ->orWhereHas('collections', function ($q) use ($search) {
                        $q->where('title', 'LIKE', "%{$search}%");
                    })
                    // Search in collection translations (title)
                    ->orWhereHas('collections.translations', function ($q) use ($search, $userPreferredLanguage) {
                        $q->where('language_id', $userPreferredLanguage)
                            ->where('title', 'LIKE', "%{$search}%");
                    })
                    // Search in tag base name
                    ->orWhereHas('tags', function ($q) use ($search) {
                        $q->where('name', 'LIKE', "%{$search}%");
                    })
                    // Search in tag translations (name)
                    ->orWhereHas('tags.translations', function ($q) use ($search, $userPreferredLanguage) {
                        $q->where('language_id', $userPreferredLanguage)
                            ->where('name', 'LIKE', "%{$search}%");
                    });
            });
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('reviewed', $request->status);
        }

        $artworks = $query->paginate(10);

        // Translate artwork, its artist, collections and tags
        foreach ($artworks as $artwork) {
            // Translate artwork title using its translations
            $artworkTranslation = $artwork->translations->where('language_id', $userPreferredLanguage)->first();
            if ($artworkTranslation) {
                $artwork->name = $artworkTranslation->name;
            }

            // Translate the artwork's artist details (if available)
            if ($artwork->artist && method_exists($artwork->artist, 'translations')) {
                $artistTranslation = $artwork->artist->translations->where('language_id', $userPreferredLanguage)->first();
                if ($artistTranslation) {
                    $artwork->artist->first_name = $artistTranslation->first_name;
                    $artwork->artist->last_name = $artistTranslation->last_name;
                }
            }

            // Translate each collection associated with the artwork
            foreach ($artwork->collections as $collection) {
                $collectionTranslation = $collection->translations->where('language_id', $userPreferredLanguage)->first();
                if ($collectionTranslation) {
                    $collection->title = $collectionTranslation->title;
                    $collection->description = $collectionTranslation->description;
                }
            }

            // Translate each tag associated with the artwork
            foreach ($artwork->tags as $tag) {
                $tagTranslation = $tag->translations->where('language_id', $userPreferredLanguage)->first();
                if ($tagTranslation) {
                    $tag->name = $tagTranslation->name;
                }
            }
        }

        // Independently fetch and translate artists, collections, and tags for selection lists
        $artists = User::where('is_artist', '1')->get();
        foreach ($artists as $artist) {
            if (method_exists($artist, 'translations')) {
                $artistTranslation = $artist->translations->where('language_id', $userPreferredLanguage)->first();
                if ($artistTranslation) {
                    $artist->first_name = $artistTranslation->first_name;
                    $artist->last_name = $artistTranslation->last_name;
                }
            }
        }

        $collections = Collection::all();
        foreach ($collections as $collection) {
            $collectionTranslation = $collection->translations->where('language_id', $userPreferredLanguage)->first();
            if ($collectionTranslation) {
                $collection->title = $collectionTranslation->title;
                $collection->description = $collectionTranslation->description;
            }
        }

        $tags = Tag::all();
        foreach ($tags as $tag) {
            $tagTranslation = $tag->translations->where('language_id', $userPreferredLanguage)->first();
            if ($tagTranslation) {
                $tag->name = $tagTranslation->name;
            }
        }

        $languages = Language::all();

        return view('dashboard.artworks.index', compact('artworks', 'languages', 'artists', 'collections', 'tags'));
    }

    public function show($id)
    {
        $userPreferredLanguage = auth()->user()->preferred_language;
        $artwork = Artwork::with(['artist', 'collections', 'tags', 'translations', 'translations.language'])->findOrFail($id);

        // Translate artwork title using its translation for the preferred language
        $artworkTranslation = $artwork->translations->where('language_id', $userPreferredLanguage)->first();
        if ($artworkTranslation) {
            $artwork->name = $artworkTranslation->name;
            $artwork->art_type = $artworkTranslation->art_type;
            $artwork->description = $artworkTranslation->description;

        }
        foreach ($artwork->translations as $translation) {
            $translation->language->name = tt($translation->language->name);
        }
        $artwork->artwork_status = tt($artwork->artwork_status);

        // Translate the artwork's artist details, if available
        if ($artwork->artist && method_exists($artwork->artist, 'translations')) {
            $artistTranslation = $artwork->artist->translations->where('language_id', $userPreferredLanguage)->first();
            if ($artistTranslation) {
                $artwork->artist->first_name = $artistTranslation->first_name;
                $artwork->artist->last_name = $artistTranslation->last_name;
            }
        }

        // Translate each collection associated with the artwork
        foreach ($artwork->collections as $collection) {
            $collectionTranslation = $collection->translations->where('language_id', $userPreferredLanguage)->first();
            if ($collectionTranslation) {
                $collection->title = $collectionTranslation->title;
                $collection->description = $collectionTranslation->description;
            }
        }

        // Translate each tag associated with the artwork
        foreach ($artwork->tags as $tag) {
            $tagTranslation = $tag->translations->where('language_id', $userPreferredLanguage)->first();
            if ($tagTranslation) {
                $tag->name = $tagTranslation->name;
            }
        }

        return response()->json(['artwork' => $artwork]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'translations' => 'required|array',
            'translations.*.language_id' => 'required|exists:languages,id',
            'translations.*.name' => 'required|string|max:100',
            'translations.*.art_type' => 'nullable|string|max:100',
            'translations.*.description' => 'required|string',

            'subcategories' => 'required|array',
            'photos' => 'required|image|mimes:jpeg,png|max:2048',
            'reviewed' => 'required|in:0,1',
            'artist_id' => 'required|exists:users,id',
            'sizes_prices' => 'required|array',
            'sizes_prices.*.size' => 'required|string|max:10',
            'sizes_prices.*.price' => 'required|numeric|min:0',
        ]);

        $englishTranslation = collect($request->translations)->where('language_id', 1)->first();
        $sizes_prices = [];
        $min = 99999999999;
        $max = 0;
        foreach ($request->sizes_prices as $size_price) {
            $sizes_prices[$size_price['size']] = $size_price['price'];
            if ($size_price['price'] < $min) {
                $min = $size_price['price'];
            }
            if ($size_price['price'] > $max) {
                $max = $size_price['price'];
            }
        }
        $imagePath = $request->file('photos')->store('artworks', 'public');
        $artwork = Artwork::create([
            'name' => $englishTranslation['name'],
            'art_type' => $englishTranslation['art_type'],
            'description' => $englishTranslation['description'],
            'artist_id' => $request->artist_id,
            'reviewed' => $request->reviewed,
            'artwork_status' => $request->artwork_status,
            'sizes_prices' => json_encode($sizes_prices),
            'min_price' => $min,
            'max_price' => $max,
            'photos' => json_encode([$imagePath]),
        ]);

        // Attach multiple collections
        if ($request->has('collections')) {
            $artwork->collections()->sync($request->collections);
        }

        // Store translations
        foreach ($request->translations as $translation) {
            $artwork->translations()->create([
                'language_id' => $translation['language_id'],
                'name' => $translation['name'],
                'art_type' => $translation['art_type'],
                'description' => $translation['description'],
            ]);
        }

        // Store tags
        foreach ($request->subcategories as $subcategoryId) {
            ArtworkTag::create(['artwork_id' => $artwork->id, 'tag_id' => $subcategoryId]);
        }

        return redirect()->back()->with('success', 'Artwork added successfully.');
    }


    public function update(Request $request, $id)
    {
        $artwork = Artwork::findOrFail($id);

        $request->validate([
            'translations' => 'required|array',
            'translations.*.language_id' => 'required|exists:languages,id',
            'translations.*.name' => 'required|string|max:100',
            'translations.*.art_type' => 'nullable|string|max:100',
            'translations.*.description' => 'required|string',

            'subcategories' => 'required|array',
            'photos' => 'nullable|image|mimes:jpeg,png|max:2048',
            'reviewed' => 'required|in:0,1',
            'artist_id' => 'required|exists:users,id',
            'sizes_prices' => 'required|array',
            'sizes_prices.*.size' => 'required|string|max:10',
            'sizes_prices.*.price' => 'required|numeric|min:0',
        ]);

        if ($request->hasFile('photos')) {
            if (json_decode($artwork->photos)) {
                Storage::disk('public')->delete(json_decode($artwork->photos)[0]);
            }
            $imagePath = $request->file('photos')->store('artworks', 'public');
            $artwork->photos = json_encode([$imagePath]);
        }

        // get english language from translations
        $englishTranslation = collect($request->translations)->where('language_id', 1)->first();
        $sizes_prices = [];
        $min = 99999999999;
        $max = 0;
        foreach ($request->sizes_prices as $size_price) {
            $sizes_prices[$size_price['size']] = $size_price['price'];
            if ($size_price['price'] < $min) {
                $min = $size_price['price'];
            }
            if ($size_price['price'] > $max) {
                $max = $size_price['price'];
            }
        }

        $artwork->update([
            'name' => $englishTranslation['name'],
            'art_type' => $englishTranslation['art_type'],
            'description' => $englishTranslation['description'],
            'artist_id' => $request->artist_id,
            'reviewed' => $request->reviewed,
            'artwork_status' => $request->artwork_status,
            'sizes_prices' => json_encode($sizes_prices),
            'min_price' => $min,
            'max_price' => $max
        ]);

        // Sync collections
        if ($request->has('collections')) {
            $artwork->collections()->sync($request->collections);
        }

        // Update translations
        foreach ($request->translations as $translation) {
            $artwork->translations()->updateOrCreate(
                ['language_id' => $translation['language_id']],
                [
                    'name' => $translation['name'],
                    'art_type' => $translation['art_type'],
                    'description' => $translation['description'],
                ]
            );
        }

        // Update tags
        ArtworkTag::where('artwork_id', $artwork->id)->delete();
        foreach ($request->subcategories as $subcategoryId) {
            ArtworkTag::create(['artwork_id' => $artwork->id, 'tag_id' => $subcategoryId]);
        }

        return redirect()->back()->with('success', 'Artwork updated successfully.');
    }

    public function destroy($id)
    {
        $artwork = Artwork::findOrFail($id);
        Storage::disk('public')->delete(json_decode($artwork->photos)[0]);
        $artwork->delete();

        return redirect()->back()->with('success', 'Artwork deleted successfully.');
    }

    public function bulkDelete(Request $request)
    {
        $ids = json_decode($request->input('ids', []));

        if (empty($ids)) {
            return redirect()->back()->with('error', 'No artworks selected for deletion.');
        }

        $artworks = Artwork::whereIn('id', $ids)->get();
        foreach ($artworks as $artwork) {
            Storage::disk('public')->delete(json_decode($artwork->photos)[0]);
            $artwork->delete();
        }

        return redirect()->back()->with('success', 'Selected artworks deleted successfully.');
    }
}