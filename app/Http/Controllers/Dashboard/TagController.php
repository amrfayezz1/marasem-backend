<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tag;
use App\Models\TagTranslation;
use App\Models\Category;
use App\Models\ArtworkTag;
use Illuminate\Support\Facades\DB;

class TagController extends Controller
{
    public function index(Request $request)
    {
        $query = Tag::query()->with(['category', 'translations']);
        if ($request->has('search') && !empty($request->search)) {
            $search = '%' . $request->search . '%';
            $query->whereHas('translations', function ($q) use ($search) {
                $q->where('name', 'LIKE', $search);
            });
        }
        if ($request->has('category') && $request->category !== 'all') {
            $query->where('category_id', $request->category);
        }
        $tags = $query->paginate(10);
        $categories = Category::all();
        $languages = DB::table('languages')->get();
        return view('dashboard.tags.index', compact('tags', 'categories', 'languages'));
    }

    public function show($id)
    {
        $tag = Tag::with(['translations', 'category', 'translations.language'])->findOrFail($id);
        return response()->json(['tag' => $tag]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'translations' => 'required|array',
            'translations.*.language_id' => 'required|exists:languages,id',
            'translations.*.name' => 'required|string|max:50',
            'status' => 'required|in:published,hidden',
            // 'meta_keyword' => 'required|string',
            // 'url' => 'required|string|unique:tags,url',
            // 'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // $imagePath = $request->file('image')->store('tags', 'public');

        // get the english language translation
        $englishTranslation = collect($request->translations)->where('language_id', 1)->first();
        $tag = Tag::create([
            'name' => $englishTranslation['name'],
            'category_id' => $request->category_id,
            'status' => $request->status,
            // 'meta_keyword' => $request->meta_keyword,
            // 'url' => $request->url,
            // 'image' => $imagePath
        ]);

        foreach ($request->translations as $translation) {
            TagTranslation::create([
                'tag_id' => $tag->id,
                'language_id' => $translation['language_id'],
                'name' => $translation['name'],
            ]);
        }

        return redirect()->back()->with('success', 'Subcategory added successfully.');
    }

    public function update(Request $request, $id)
    {
        $tag = Tag::findOrFail($id);

        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'translations' => 'required|array',
            'translations.*.language_id' => 'required|exists:languages,id',
            'translations.*.name' => 'required|string|max:50',
            'status' => 'required|in:published,hidden',
            // 'meta_keyword' => 'required|string',
            // 'url' => 'required|string|unique:tags,url,' . $tag->id,
            // 'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // if ($request->hasFile('image')) {
        //     $imagePath = $request->file('image')->store('tags', 'public');
        //     $tag->update(['image' => $imagePath]);
        // }
        $englishTranslation = collect($request->translations)->where('language_id', 1)->first();
        $tag->update([
            'name' => $englishTranslation['name'],
            'category_id' => $request->category_id,
            'status' => $request->status,
            // 'meta_keyword' => $request->meta_keyword,
            // 'url' => $request->url,
        ]);

        foreach ($request->translations as $translation) {
            TagTranslation::updateOrCreate(
                ['tag_id' => $tag->id, 'language_id' => $translation['language_id']],
                ['name' => $translation['name']]
            );
        }

        return redirect()->back()->with('success', 'Subcategory updated successfully.');
    }

    public function destroy($id)
    {
        $tag = Tag::findOrFail($id);

        $hasLinkedArtworks = ArtworkTag::where('tag_id', $tag->id)->exists();

        if ($hasLinkedArtworks) {
            return redirect()->back()->with('error', 'This subcategory cannot be deleted as it is linked to active artworks.');
        }

        $tag->delete();

        return redirect()->back()->with('success', 'Subcategory deleted successfully.');
    }

    public function bulkDelete(Request $request)
    {
        $ids = json_decode($request->input('ids', []));
        $acceptedIds = [];

        foreach ($ids as $id) {
            $tag = Tag::findOrFail($id);
            if (!ArtworkTag::where('tag_id', $tag->id)->exists()) {
                $acceptedIds[] = $id;
            }
        }

        Tag::whereIn('id', $acceptedIds)->delete();
        return redirect()->back()->with('success', 'Selected subcategories deleted successfully.');
    }

    public function bulkPublish(Request $request)
    {
        $ids = json_decode($request->input('ids', []));
        Tag::whereIn('id', $ids)->update(['status' => 'published']);
        return redirect()->back()->with('success', 'Selected subcategories published successfully.');
    }

    public function bulkUnpublish(Request $request)
    {
        $ids = json_decode($request->input('ids', []));
        Tag::whereIn('id', $ids)->update(['status' => 'hidden']);
        return redirect()->back()->with('success', 'Selected subcategories unpublished successfully.');
    }
}
