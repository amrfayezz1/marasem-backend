<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Collection;
use App\Models\Tag;
use App\Models\Artwork;
use App\Models\ArtworkCollection;

class CollectionController extends Controller
{
    public function index(Request $request)
    {
        $query = Collection::query();
        if ($request->has('search') && $request->has('filter')) {
            $search = $request->search;
            $filter = $request->filter;
            $query->where($filter, 'like', '%' . $search . '%');
        }
        $collections = $query->paginate(10);
        $tags = Tag::all();
        $artworks = Artwork::with('artist')->get();
        return view('dashboard.collections.index', compact('collections', 'tags', 'artworks'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:50|unique:collections',
            'tags' => 'nullable|array',
            'artworks' => 'nullable|array'
        ]);

        $collection = Collection::create([
            'title' => $request->title,
            'tags' => json_encode($request->tags ?? [])
        ]);

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
        $collection = Collection::findOrFail($id);
        $collection->tag_ids = json_decode($collection->tags, true) ?? [];
        $collection->tag = array_map(function ($tag_id) {
            return [Tag::find($tag_id)->name];
        }, $collection->tag_ids);
        $collection->tags = $collection->tag;
        $artworks = $collection->artworks()->with('artist')->get();

        return response()->json([
            'collection' => $collection,
            'artworks' => $artworks
        ]);
    }

    public function update(Request $request, $id)
    {
        $collection = Collection::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:50|unique:collections,title,' . $collection->id,
            'tags' => 'nullable|array',
            'artworks' => 'nullable|array'
        ]);

        // Update collection details
        $collection->update([
            'title' => $request->title,
            'tags' => json_encode($request->tags ?? [])
        ]);

        // Update artworks: Remove old and add new ones
        $collection->artworks()->detach(); // Remove existing artworks
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
        Collection::whereIn('id', $ids)->delete();
        return redirect()->back()->with('success', 'Selected collections deleted successfully.');
    }
}
