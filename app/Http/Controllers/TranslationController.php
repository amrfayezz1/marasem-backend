<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Models\StaticTranslation;
use Illuminate\Http\Request;

class TranslationController extends Controller
{
    // Function to add a new language
    public function addLanguage(Request $request)
    {
        // Validate input
        $request->validate([
            'code' => 'required|string|unique:languages,code|max:5', // e.g., 'ar', 'fr'
            'name' => 'required|string|max:50', // e.g., 'Arabic', 'French'
            'status' => 'boolean', // Optional, defaults to true
        ]);

        // Add the new language
        $language = Language::create([
            'code' => $request->code,
            'name' => $request->name,
            'status' => $request->status ?? true,
        ]);

        return response()->json([
            'message' => 'Language added successfully.',
            'language' => $language,
        ]);
    }

    public function setLocale(Request $request)
    {
        $request->validate(['locale' => 'required|string|exists:languages,code']);

        $locale = $request->locale;

        return response()->json(['message' => 'Locale set successfully.'])
            ->cookie('locale', $locale, 60 * 24 * 30); // 30 days
    }

    public function getStaticTranslation(Request $request)
    {
        $request->validate(['token' => 'required|string']);

        $translation = translate_static($request->token);

        return response()->json([
            'token' => $request->token,
            'locale' => app()->getLocale(),
            'translation' => $translation,
        ]);
    }

    public function getStaticTranslations(Request $request)
    {
        $request->validate([
            'tokens' => 'required|array',
            'tokens.*' => 'string',
        ]);

        $locale = app()->getLocale();
        $language = Language::where('code', $locale)->first();

        if (!$language) {
            return response()->json(['message' => 'Invalid locale'], 400);
        }

        // Tokenize the provided tokens
        $tokenizedTokens = collect($request->tokens)->map(function ($token) {
            return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', trim($token)));
        });

        // Fetch translations for the tokenized tokens
        $translations = StaticTranslation::whereIn('token', $tokenizedTokens)
            ->where('language_id', $language->id)
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->token => $item->translation];
            });

        // Identify missing tokens and insert them for admin review
        $missingTokens = $tokenizedTokens->diff($translations->keys());

        foreach ($missingTokens as $missingToken) {
            StaticTranslation::firstOrCreate([
                'token' => $missingToken,
                'language_id' => $language->id,
            ], [
                'translation' => null,
            ]);
        }

        // Build the response: Return translations or fallback to the original token
        $response = collect($request->tokens)
            ->mapWithKeys(function ($token) use ($translations) {
                $tokenized = strtolower(preg_replace('/[^a-z0-9]+/', '_', trim($token)));
                return [$token => $translations->get($tokenized, $token)];
            });

        return response()->json($response);
    }

}
