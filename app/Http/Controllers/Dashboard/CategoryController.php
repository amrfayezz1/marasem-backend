<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Category;
use App\Models\CategoryTranslation;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::query();
        if ($request->has('search') && $request->has('filter')) {
            $search = $request->search;
            $filter = $request->filter;
            if ($filter == 'id') {
                $query->where('id', $search);
            } else {
                $query->where($filter, 'like', '%' . $search . '%');
            }
        }
        $categories = $query->paginate(10);
        $languages = Language::all();
        return view('dashboard.categories.index', compact('categories', 'languages'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'translations' => 'required|array',
            'translations.*.language_id' => 'required|exists:languages,id',
            'translations.*.name' => 'required|string|max:50',
            'status' => 'required|in:active,inactive',
        ]);

        // Get default language (language_id = 1)
        $defaultTranslation = collect($request->translations)->firstWhere('language_id', 1);
        if (!$defaultTranslation) {
            return redirect()->back()->with('error', 'Default language translation is required.');
        }

        // Create category with default language
        $category = Category::create([
            'name' => $defaultTranslation['name'],
            'status' => $request->status,
        ]);

        // Insert category translations
        foreach ($request->translations as $translation) {
            CategoryTranslation::create([
                'category_id' => $category->id,
                'language_id' => $translation['language_id'],
                'name' => $translation['name'],
            ]);
        }

        return redirect()->back()->with('success', 'Category added successfully.');
    }

    public function show($id)
    {
        $category = Category::with('translations')->findOrFail($id);
        return response()->json($category);
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'translations' => 'required|array',
            'translations.*.language_id' => 'required|exists:languages,id',
            'translations.*.name' => 'required|string|max:50',
            'status' => 'required|in:active,inactive',
        ]);

        // Update default language (language_id = 1) in categories table
        $defaultTranslation = collect($request->translations)->firstWhere('language_id', 1);
        if (!$defaultTranslation) {
            return redirect()->back()->with('error', 'Default language translation is required.');
        }

        $category->update([
            'name' => $defaultTranslation['name'],
            'status' => $request->status,
        ]);

        // Update or insert translations
        foreach ($request->translations as $translation) {
            CategoryTranslation::updateOrCreate(
                ['category_id' => $category->id, 'language_id' => $translation['language_id']],
                ['name' => $translation['name']]
            );
        }

        return redirect()->back()->with('success', 'Category updated successfully.');
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        // Check if any tag under this category is linked to an artwork
        $hasLinkedArtworks = DB::table('artwork_tag')
            ->join('tags', 'artwork_tag.tag_id', '=', 'tags.id')
            ->where('tags.category_id', $category->id)
            ->exists();

        if ($hasLinkedArtworks) {
            return redirect()->back()->with('error', 'This category cannot be deleted as it has associated artworks.');
        }

        // Delete category if no linked artworks
        $category->delete();

        return redirect()->back()->with('success', 'Category deleted successfully.');
    }

    public function bulkDelete(Request $request)
    {
        $ids = json_decode($request->input('ids', []));
        $acceptedIds = [];
        foreach ($ids as $id) {
            $category = Category::findOrFail($id);

            // Check if any tag under this category is linked to an artwork
            $hasLinkedArtworks = DB::table('artwork_tag')
                ->join('tags', 'artwork_tag.tag_id', '=', 'tags.id')
                ->where('tags.category_id', $category->id)
                ->exists();

            if ($hasLinkedArtworks) {
                return redirect()->back()->with('error', 'One or more selected categories cannot be deleted as they have associated artworks.');
            }
            $acceptedIds[] = $id;
        }
        Category::whereIn('id', $acceptedIds)->delete();
        return redirect()->back()->with('success', 'Selected categories deleted successfully.');
    }
}
