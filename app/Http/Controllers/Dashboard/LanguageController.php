<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Language;
use App\Models\StaticTranslation;

class LanguageController extends Controller
{
    public function index(Request $request)
    {
        $query = Language::query();

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('name', 'LIKE', "%{$search}%")
                ->orWhere('code', 'LIKE', "%{$search}%");
            $statics = StaticTranslation::where('translation', $search)->get();
            foreach ($statics as $static) {
                $tokenLower = mb_strtolower($static->token);
                $query->orWhereRaw('LOWER(name) LIKE ?', ["%{$tokenLower}%"]);
            }
        }

        $languages = $query->paginate(10);

        return view('dashboard.languages.index', compact('languages'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:languages,code|max:5',
            'name' => 'required|string|max:50',
            'status' => 'required|boolean',
        ]);

        Language::create($request->only(['code', 'name', 'status']));

        return redirect()->back()->with('success', 'Language added successfully.');
    }

    public function edit($id)
    {
        $language = Language::findOrFail($id);
        return response()->json(['language' => $language]);
    }

    public function update(Request $request, $id)
    {
        $language = Language::findOrFail($id);

        $request->validate([
            'code' => 'required|string|unique:languages,code,' . $language->id . '|max:5',
            'name' => 'required|string|max:50',
            'status' => 'required|boolean',
        ]);

        $language->update($request->only(['code', 'name', 'status']));

        return redirect()->back()->with('success', 'Language updated successfully.');
    }

    public function destroy($id)
    {
        Language::findOrFail($id)->delete();

        return redirect()->back()->with('success', 'Language deleted successfully.');
    }

    public function getLanguage(Request $request, $language_id)
    {
        $language = Language::findOrFail($language_id);

        // Get all unique tokens from the static_translations table (regardless of language)
        $allTokens = StaticTranslation::distinct()->pluck('token');

        // Check if there is a 'search' input in the request and if it has a value
        if ($request->has('search') && $request->input('search')) {
            $searchTerm = $request->input('search');

            // Format the search term to the required format (e.g., 'welcome_to_marasem')
            $formattedSearchTerm = str_replace(' ', '_', strtolower($searchTerm));

            // Filter tokens that match either the formatted token or the translation text (case-insensitive)
            $allTokens = $allTokens->filter(function ($token) use ($formattedSearchTerm, $searchTerm, $language_id) {
                $tokenLower = strtolower($token);
                // Check if the token itself contains the formatted search term.
                $tokenMatch = strpos($tokenLower, $formattedSearchTerm) !== false;

                // Get the translation for this token for the given language.
                $translationRecord = StaticTranslation::where('language_id', $language_id)
                    ->where('token', $token)
                    ->first();
                $translationMatch = false;
                if ($translationRecord) {
                    $translationLower = strtolower($translationRecord->translation);
                    $translationMatch = strpos($translationLower, strtolower($searchTerm)) !== false;
                }

                return $tokenMatch || $translationMatch;
            });
        }

        // Get the translations matching the filtered tokens
        $translations = $allTokens->map(function ($token) use ($language_id) {
            return StaticTranslation::where('language_id', $language_id)
                ->where('token', $token)
                ->first() ??
                new StaticTranslation(['token' => $token, 'language_id' => $language_id, 'translation' => '']);
        });

        // Manual pagination (since we're dealing with a collection)
        $perPage = 10;
        $page = $request->get('page', 1);
        $translations = new \Illuminate\Pagination\LengthAwarePaginator(
            $translations->slice(($page - 1) * $perPage, $perPage)->values(),
            $translations->count(),
            $perPage,
            $page,
            ['path' => $request->url()]
        );

        return view('dashboard.languages.language', compact('translations', 'language'));
    }

    public function updateLanguage(Request $request, $language_id)
    {
        $language = Language::findOrFail($language_id);

        $request->validate([
            'translations' => 'required|array',
            'translations.*.id' => 'nullable|exists:static_translations,id',
            'translations.*.token' => 'required|string',
            'translations.*.translation' => 'nullable|string',
        ]);

        foreach ($request->translations as $data) {
            StaticTranslation::updateOrCreate(
                ['id' => $data['id'] ?? null, 'language_id' => $language_id],
                ['token' => $data['token'], 'translation' => $data['translation']]
            );
        }
        return response()->json(['message' => 'Translations updated successfully.']);
    }
}
