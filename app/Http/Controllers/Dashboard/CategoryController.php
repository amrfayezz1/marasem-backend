<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\StaticTranslation;
use App\Models\Artwork;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        try {
            $userPreferredLanguage = auth()->user()->preferred_language;
            $query = Category::query();

            if ($request->has('search') && $request->has('filter')) {
                $search = $request->input('search');
                $filter = $request->input('filter');

                if ($filter == 'id') {
                    $query->where('id', $search);
                } elseif ($filter == 'name') {
                    // Search by category name or its translation for the user's preferred language
                    $query->where(function ($q) use ($search, $userPreferredLanguage) {
                        $q->where('name', 'like', '%' . $search . '%')
                            ->orWhereHas('translations', function ($q2) use ($search, $userPreferredLanguage) {
                                $q2->where('language_id', $userPreferredLanguage)
                                    ->where('name', 'like', '%' . $search . '%');
                            });
                    });
                } elseif ($filter == 'status') {
                    // Search by the status field and via static translations
                    $query->where(function ($q) use ($search) {
                        $q->where('status', 'like', '%' . $search . '%');

                        // Retrieve any static translations where the translation matches the search term (case-insensitive)
                        $statics = StaticTranslation::whereRaw('LOWER(translation) LIKE ?', ['%' . mb_strtolower($search) . '%'])->get();
                        foreach ($statics as $static) {
                            $q->orWhere('status', 'like', '%' . $static->token . '%');
                        }
                    });
                } else {
                    // For any other filter, perform a generic search on that column.
                    $query->where($filter, 'like', '%' . $search . '%');
                }
            }

            // Get rows per page from the request; default to 10
            $rows = $request->input('rows', 10);
            // Include count of related artworks
            $categories = $query->withCount('artworks')->paginate($rows);

            // Translate category values (e.g., name and description) based on the preferred language
            foreach ($categories as $category) {
                $mainTranslation = $category->translations
                    ->where('language_id', $userPreferredLanguage)
                    ->first();
                if ($mainTranslation) {
                    $category->name = $mainTranslation->name;
                    if (isset($mainTranslation->description)) {
                        $category->description = $mainTranslation->description;
                    }
                }
                if ($category->tags) {
                    foreach ($category->tags as $tag) {
                        $tagTranslation = $tag->translations
                            ->where('language_id', $userPreferredLanguage)
                            ->first();
                        if ($tagTranslation) {
                            $tag->name = $tagTranslation->name;
                        }
                    }
                }
            }

            $languages = Language::all();

            // Fetch artworks (for selection) and translate their details as well as their associated artist details
            $artworks = Artwork::with('artist')->get();
            foreach ($artworks as $artwork) {
                $artworkTranslation = $artwork->translations
                    ->where('language_id', $userPreferredLanguage)
                    ->first();
                if ($artworkTranslation) {
                    $artwork->name = $artworkTranslation->name;
                }

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

            return view('dashboard.categories.index', compact('categories', 'languages', 'artworks'));
        } catch (\Exception $e) {
            \Log::error($e);
            return redirect()->back()->with('error', 'Unable to load categories. Please try again later.');
        }
    }

    public function store(Request $request)
    {
        // Validate other inputs along with the file upload:
        $request->validate([
            'translations' => 'required|array',
            'translations.*.language_id' => 'required|exists:languages,id',
            'translations.*.name' => 'required|string|max:50',
            'translations.*.description' => 'nullable|string|max:200',
            'meta_keyword' => 'required|string',
            'url' => 'required|url',
            'picture' => 'required|image|max:2048', // Validate the file (max 2MB, for example)
            'status' => 'required|in:active,inactive',
            // ... add validations for artworks if necessary
        ]);

        // Handle file upload:
        if ($request->hasFile('picture')) {
            $file = $request->file('picture');
            // Store the file in the 'categories' directory in public storage:
            $path = $file->store('categories', 'public');
            // Build the URL to store in the database:
            $pictureUrl = asset('storage/' . $path);
        } else {
            $pictureUrl = null;
        }

        // Create category using default language translation
        $defaultTranslation = collect($request->translations)->firstWhere('language_id', 1);
        if (!$defaultTranslation) {
            return redirect()->back()->with('error', 'Default language translation is required.');
        }

        // Check uniqueness or any other validations as needed...

        $category = Category::create([
            'name' => $defaultTranslation['name'],
            'status' => $request->status,
            'meta_keyword' => $request->meta_keyword,
            'url' => $request->url,
            'picture' => $pictureUrl,
        ]);

        // Store translations
        foreach ($request->translations as $translation) {
            CategoryTranslation::create([
                'category_id' => $category->id,
                'language_id' => $translation['language_id'],
                'name' => $translation['name'],
                'description' => $translation['description'] ?? null,
            ]);
        }

        // Sync artworks if provided
        if ($request->has('artworks')) {
            $category->artworks()->sync($request->artworks);
        }

        return redirect()->back()->with('success', 'Category added successfully.');
    }

    public function show($id)
    {
        try {
            $userPreferredLanguage = auth()->user()->preferred_language;
            $category = Category::with('translations', 'translations.language', 'artworks', 'artworks.artist')->findOrFail($id);

            // Translate the category's values
            $mainTranslation = $category->translations
                ->where('language_id', $userPreferredLanguage)
                ->first();
            if ($mainTranslation) {
                $category->name = $mainTranslation->name;
                if (isset($mainTranslation->description)) {
                    $category->description = $mainTranslation->description;
                }
            }

            // Translate each artwork and its associated artist
            foreach ($category->artworks as $artwork) {
                $artworkTranslation = $artwork->translations
                    ->where('language_id', $userPreferredLanguage)
                    ->first();
                if ($artworkTranslation) {
                    $artwork->name = $artworkTranslation->name;
                }

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
            foreach ($category->translations as $translation) {
                $translation->language->name = tt($translation->language->name);
            }

            return response()->json($category);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'error' => 'Unable to load category. Please try again later.'
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'translations' => 'required|array',
            'translations.*.language_id' => 'required|exists:languages,id',
            'translations.*.name' => 'required|string|max:50',
            'translations.*.description' => 'nullable|string|max:200',
            'meta_keyword' => 'required|string',
            'url' => 'required|url',
            'picture' => 'nullable|image|max:2048', // picture is optional on update
            'status' => 'required|in:active,inactive',
            // Optionally, you can validate artworks as well:
            // 'artworks' => 'nullable|array',
        ]);

        // Get default translation (language_id = 1)
        $defaultTranslation = collect($request->translations)->firstWhere('language_id', 1);
        if (!$defaultTranslation) {
            return redirect()->back()->with('error', 'Default language translation is required.');
        }

        // Handle picture file upload: update if a new file is provided; otherwise retain current picture
        if ($request->hasFile('picture')) {
            $file = $request->file('picture');
            // Store the file in the 'categories' folder within public storage
            $path = $file->store('categories', 'public');
            // Generate the URL for the stored file
            $pictureUrl = asset('storage/' . $path);
        } else {
            $pictureUrl = $category->picture;
        }

        // Update the common category fields
        $category->update([
            'name' => $defaultTranslation['name'],
            'status' => $request->status,
            'meta_keyword' => $request->meta_keyword,
            'url' => $request->url,
            'picture' => $pictureUrl,
        ]);

        // Update or insert each translation, including description
        foreach ($request->translations as $translation) {
            CategoryTranslation::updateOrCreate(
                ['category_id' => $category->id, 'language_id' => $translation['language_id']],
                [
                    'name' => $translation['name'],
                    'description' => $translation['description'] ?? null
                ]
            );
        }

        // Sync the artworks via the many-to-many relationship pivot table
        $category->artworks()->sync($request->has('artworks') ? $request->artworks : []);

        return redirect()->back()->with('success', 'Category updated successfully.');
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        // Check if the category has associated artworks
        if ($category->artworks()->exists()) {
            return redirect()->back()->with('error', 'This category cannot be deleted as it has associated artworks.');
        }

        $category->delete();
        return redirect()->back()->with('success', 'Category deleted successfully.');
    }

    public function bulkDelete(Request $request)
    {
        $ids = json_decode($request->input('ids', []));
        $acceptedIds = [];
        foreach ($ids as $id) {
            $category = Category::findOrFail($id);
            if ($category->artworks()->exists()) {
                return redirect()->back()->with('error', 'One or more selected categories cannot be deleted as they have associated artworks.');
            }
            $acceptedIds[] = $id;
        }
        Category::whereIn('id', $acceptedIds)->delete();
        return redirect()->back()->with('success', 'Selected categories deleted successfully.');
    }
    public function toggleStatus($id)
    {
        $category = Category::findOrFail($id);
        $category->status = $category->status === 'active' ? 'inactive' : 'active';
        $category->save();
        return response()->json(['success' => true, 'status' => $category->status]);
    }

}
