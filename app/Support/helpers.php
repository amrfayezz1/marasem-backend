<?php

use App\Models\Language;
use App\Models\StaticTranslation;

if (!function_exists('translate_static')) {
    function translate_static($token)
    {
        // Get the current locale
        $locale = app()->getLocale();

        // Find the language by code
        $language = Language::where('code', $locale)->first();

        // If the language does not exist, return the token
        if (!$language) {
            return $token;
        }

        // Fetch the translation
        $translation = StaticTranslation::where('token', $token)
            ->where('language_id', $language->id)
            ->first();

        if (!$translation) {
            StaticTranslation::firstOrCreate([
                'token' => $token,
                'language_id' => $language->id,
            ], [
                'translation' => null, // Set as null for admin to fill later
            ]);
        }

        // Return the translation or fallback to the token
        return $translation ? $translation->translation : $token;
    }
}
