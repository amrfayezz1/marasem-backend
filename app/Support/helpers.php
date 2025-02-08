<?php

use App\Models\Language;
use App\Models\StaticTranslation;

if (!function_exists('tt')) {
    function tt($token, $lang = '')
    {
        if ($lang) {
            $locale = $lang;
        } else {
            // Get the current locale
            if ((auth()->user() && auth()->user()->language) || (auth('sanctum')->user() && auth('sanctum')->user()->language)) {
                $locale = auth()->user()->language->code;
            } else {
                $locale = cookie('locale', 'en');
                $locale = explode(';', $locale)[0];
                $locale = explode('=', $locale)[1];
            }
        }
        // Find the language by code
        $language = Language::where('code', $locale)->first();

        // If the language does not exist, return the token
        if (!$language) {
            return $token;
        }
        $formattedToken = str_replace(' ', '_', strtolower($token));

        // Fetch the translation
        $translation = StaticTranslation::where('token', $formattedToken)
            ->where('language_id', $language->id)
            ->first();

        if (!$translation) {
            StaticTranslation::firstOrCreate([
                'token' => $formattedToken,
                'language_id' => $language->id,
            ], [
                'translation' => null, // Set as null for admin to fill later
            ]);
        }
        // Return the translation or fallback to the token
        return $translation && $translation->translation ? $translation->translation : $token;
    }
}
