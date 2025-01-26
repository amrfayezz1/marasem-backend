<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use App\Models\Language;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        // Check if user is authenticated
        if ($user = auth('sanctum')->user()) {
            $locale = $user->preferred_language;
        } else {
            // Check if locale is set in the cookie
            $locale = $request->cookie('locale', 'en');
        }

        // Ensure the locale exists in the database
        $language = Language::where('code', $locale)->where('status', true)->first();
        if (!$language) {
            $locale = 'en'; // Fallback to English
        }

        // Set locale in application
        app()->setLocale($locale);

        // Proceed with the request
        return $next($request);
    }
}

