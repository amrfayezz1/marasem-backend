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
        $query = Artwork::with(['artist', 'collections', 'tags', 'translations']);

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('name', 'LIKE', "%{$search}%")
                ->orWhereHas('collections', function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%");
                })
                ->orWhereHas('tags', function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%");
                });
        }

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('reviewed', $request->status);
        }

        $artworks = $query->paginate(10);
        $languages = Language::all();
        $artists = User::where('is_artist', '1')->get();
        $collections = Collection::all();
        $tags = Tag::all();

        return view('dashboard.artworks.index', compact('artworks', 'languages', 'artists', 'collections', 'tags'));
    }

    public function show($id)
    {
        $artwork = Artwork::with(['artist', 'collections', 'tags', 'translations'])->findOrFail($id);
        // $artwork->sizes_prices = json_encode($artwork->sizes_prices);
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