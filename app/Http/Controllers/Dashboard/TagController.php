<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tag;
use App\Models\TagTranslation;
use App\Models\Category;
use App\Models\Collection;
use App\Models\ArtworkTag;
use Illuminate\Support\Facades\DB;

class TagController extends Controller
{
    public function index(Request $request)
    {
        try {
            $userPreferredLanguage = auth()->user()->preferred_language;
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

            // Translate each Tag's name based on the user's preferred language
            foreach ($tags as $tag) {
                // Translate the tag's name
                $tagTranslation = $tag->translations
                    ->where('language_id', $userPreferredLanguage)
                    ->first();
                if ($tagTranslation) {
                    $tag->name = $tagTranslation->name;
                }

                // Translate the category's name if the tag has an associated category with translations
                if ($tag->category && isset($tag->category->translations)) {
                    $categoryTranslation = $tag->category->translations
                        ->where('language_id', $userPreferredLanguage)
                        ->first();
                    if ($categoryTranslation) {
                        $tag->category->name = $categoryTranslation->name;
                    }
                }
            }

            $categories = Category::with('translations')->get();
            foreach ($categories as $category) {
                $categoryTranslation = $category->translations
                    ->where('language_id', $userPreferredLanguage)
                    ->first();
                if ($categoryTranslation) {
                    $category->name = $categoryTranslation->name;
                }
            }
            $languages = DB::table('languages')->get();

            return view('dashboard.tags.index', compact('tags', 'categories', 'languages'));
        } catch (\Exception $e) {
            \Log::error($e);
            return redirect()->back()->with('error', 'Unable to load tags. Please try again later.');
        }
    }

    public function toggleStatus($id)
    {
        $tag = Tag::findOrFail($id);
        $tag->status = $tag->status === 'published' ? 'hidden' : 'published';
        $tag->save();
        return response()->json(['success' => true, 'status' => $tag->status]);
    }

    public function show($id)
    {
        try {
            $userPreferredLanguage = auth()->user()->preferred_language;
            $tag = Tag::with(['translations', 'category', 'translations.language'])->findOrFail($id);

            // Translate the tag's name
            $tagTranslation = $tag->translations
                ->where('language_id', $userPreferredLanguage)
                ->first();
            if ($tagTranslation) {
                $tag->name = $tagTranslation->name;
                $tag->description = $tagTranslation->description;
            }

            // Optionally, translate the associated category's name if available
            if ($tag->category && isset($tag->category->translations)) {
                $categoryTranslation = $tag->category->translations
                    ->where('language_id', $userPreferredLanguage)
                    ->first();
                if ($categoryTranslation) {
                    $tag->category->name = $categoryTranslation->name;
                }
            }
            foreach ($tag->translations as $translation) {
                $translation->language->name = tt($translation->language->name);
            }

            return response()->json(['tag' => $tag]);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json(['error' => 'Unable to load tag. Please try again later.'], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'translations' => 'required|array',
            'translations.*.language_id' => 'required|exists:languages,id',
            'translations.*.name' => 'required|string|max:50',
            'translations.*.description' => 'required|string', // required description in each language
            'status' => 'required|in:published,hidden',
            'meta_keyword' => 'required|string',
            'url' => 'required|string|unique:tags,url',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // Handle image upload:
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $path = $file->store('tags', 'public');
            $imagePath = asset('storage/' . $path);
        } else {
            $imagePath = null;
        }

        // Get the default translation (assuming language_id = 1 is English)
        $defaultTranslation = collect($request->translations)->firstWhere('language_id', 1);
        if (!$defaultTranslation) {
            return redirect()->back()->with('error', 'Default language translation is required.');
        }

        // Create the tag (subcategory) record using the default translation's name and description as the common values
        $tag = Tag::create([
            'name' => $defaultTranslation['name'],
            'description' => $defaultTranslation['description'],
            'category_id' => $request->category_id,
            'status' => $request->status,
            'meta_keyword' => $request->meta_keyword,
            'url' => $request->url,
            'image' => $imagePath
        ]);

        // Create translations for each language
        foreach ($request->translations as $translation) {
            TagTranslation::create([
                'tag_id' => $tag->id,
                'language_id' => $translation['language_id'],
                'name' => $translation['name'],
                'description' => $translation['description']
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
            'translations.*.description' => 'required|string',
            'status' => 'required|in:published,hidden',
            'meta_keyword' => 'required|string',
            'url' => 'required|string|unique:tags,url,' . $tag->id,
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // Handle image upload: if a new file is provided, update; otherwise retain current image.
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $path = $file->store('tags', 'public');
            $imagePath = asset('storage/' . $path);
        } else {
            $imagePath = $tag->image;
        }

        // Get the default translation (language_id = 1)
        $defaultTranslation = collect($request->translations)->firstWhere('language_id', 1);
        if (!$defaultTranslation) {
            return redirect()->back()->with('error', 'Default language translation is required.');
        }

        // Update the tag record
        $tag->update([
            'name' => $defaultTranslation['name'],
            'description' => $defaultTranslation['description'],
            'category_id' => $request->category_id,
            'status' => $request->status,
            'meta_keyword' => $request->meta_keyword,
            'url' => $request->url,
            'image' => $imagePath
        ]);

        // Update or create translations
        foreach ($request->translations as $translation) {
            TagTranslation::updateOrCreate(
                ['tag_id' => $tag->id, 'language_id' => $translation['language_id']],
                ['name' => $translation['name'], 'description' => $translation['description']]
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
        // if exists in any collection remove it from collection tags
        $collections = Collection::all();
        foreach ($collections as $collection) {
            $tags = json_decode($collection->tags);
            if (in_array($tag->id, $tags)) {
                $tags = array_diff($tags, [$tag->id]);
                $collection->update(['tags' => json_encode($tags)]);
            }
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
