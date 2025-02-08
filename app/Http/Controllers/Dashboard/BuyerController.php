<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserTranslation;
use App\Models\Address;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\NewBuyerNotification;

class BuyerController extends Controller
{
    public function index(Request $request)
    {
        // Get the authenticated user's preferred language
        $userPreferredLanguage = auth()->user()->preferred_language;

        // Start with buyers that have a first_name and load their translations
        $query = User::whereNotNull('first_name')
            ->with('translations');

        // When a search term is provided, search in base names as well as translations
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search, $userPreferredLanguage) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%")
                    ->orWhere('id', '=', "$search")
                    ->orWhereHas('translations', function ($q2) use ($search, $userPreferredLanguage) {
                        $q2->where('language_id', $userPreferredLanguage)
                            ->where(function ($q3) use ($search) {
                                $q3->where('first_name', 'LIKE', "%{$search}%")
                                    ->orWhere('last_name', 'LIKE', "%{$search}%");
                            });
                    });
            });
        }

        // Optional date_range filtering (assumes format "YYYY-MM-DD - YYYY-MM-DD")
        if ($request->has('date_range') && $request->date_range) {
            $dates = explode(' - ', $request->date_range);
            if (count($dates) === 2) {
                $date_start = $dates[0];
                $date_end = $dates[1];
                $query->whereBetween('created_at', [$date_start, $date_end]);
            }
        }

        // Get paginated buyers
        $buyers = $query->paginate(10);

        // Loop through each buyer to override first and last names using translations
        foreach ($buyers as $buyer) {
            $translation = $buyer->translations
                ->where('language_id', $userPreferredLanguage)
                ->first();
            if ($translation) {
                $buyer->first_name = $translation->first_name;
                $buyer->last_name = $translation->last_name;
            }
        }

        $languages = Language::all();

        return view('dashboard.buyers.index', compact('buyers', 'languages'));
    }

    public function show(Request $request, $id)
    {
        $userPreferredLanguage = auth()->user()->preferred_language;
        $buyer = User::with('translations')->findOrFail($id);

        // Override first and last name with the translation (if available)
        $translation = $buyer->translations
            ->where('language_id', $userPreferredLanguage)
            ->first();
        if ($translation) {
            $buyer->first_name = $translation->first_name;
            $buyer->last_name = $translation->last_name;
        }

        return response()->json(['buyer' => $buyer]);
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'translations' => 'required|array',
            'translations.*.language_id' => 'required|exists:languages,id',
            'translations.*.first_name' => 'required|string|max:100',
            'translations.*.last_name' => 'required|string|max:100',

            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20|unique:users,phone',
            'country_code' => 'required|string|max:10',
            'date_joined' => 'required|date',

            'address.name' => 'required|string|max:100',
            'address.city' => 'required|string|max:100',
            'address.zone' => 'required|string|max:100',
            'address.address' => 'required|string',
        ]);

        // Generate random password
        $randomPassword = Str::random(10);

        // Get default language (language_id = 1)
        $defaultTranslation = collect($request->translations)->firstWhere('language_id', 1);
        if (!$defaultTranslation) {
            return redirect()->back()->with('error', 'Default language translation is required.');
        }

        // Create user with default language values
        $buyer = User::create([
            'first_name' => $defaultTranslation['first_name'],
            'last_name' => $defaultTranslation['last_name'],
            'email' => $request->email,
            'password' => Hash::make($randomPassword),
            'phone' => $request->phone,
            'country_code' => $request->country_code,
            'created_at' => $request->date_joined,
        ]);

        // Store translations
        foreach ($request->translations as $translation) {
            UserTranslation::create([
                'user_id' => $buyer->id,
                'language_id' => $translation['language_id'],
                'first_name' => $translation['first_name'],
                'last_name' => $translation['last_name'],
            ]);
        }

        // Create address
        Address::create([
            'user_id' => $buyer->id,
            'name' => $request->address['name'],
            'city' => $request->address['city'],
            'zone' => $request->address['zone'],
            'address' => $request->address['address'],
            'phone' => $request->phone,
            'country_code' => $request->country_code,
            'is_default' => isset($request->address['is_default']) ? 1 : 0,
        ]);

        // Send email notification
        Mail::to($buyer->email)->queue(new NewBuyerNotification($buyer, $randomPassword));

        return redirect()->back()->with('success', 'Buyer added successfully. A notification email has been sent.');
    }

    public function update(Request $request, $id)
    {
        $buyer = User::findOrFail($id);

        $request->validate([
            'translations' => 'required|array',
            'translations.*.language_id' => 'required|exists:languages,id',
            'translations.*.first_name' => 'required|string|max:100',
            'translations.*.last_name' => 'required|string|max:100',

            'email' => 'required|email|unique:users,email,' . $id,
            'phone' => 'required|string|max:20|unique:users,phone,' . $id,
            'country_code' => 'required|string|max:10',
        ]);

        // Get default language (language_id = 1)
        $defaultTranslation = collect($request->translations)->firstWhere('language_id', 1);
        if (!$defaultTranslation) {
            return redirect()->back()->with('error', 'Default language translation is required.');
        }

        // Update user with default language values
        $buyer->update([
            'first_name' => $defaultTranslation['first_name'],
            'last_name' => $defaultTranslation['last_name'],
            'email' => $request->email,
            'phone' => $request->phone,
            'country_code' => $request->country_code,
        ]);

        // Update or create translations
        foreach ($request->translations as $translation) {
            UserTranslation::updateOrCreate(
                ['user_id' => $buyer->id, 'language_id' => $translation['language_id']],
                ['first_name' => $translation['first_name'], 'last_name' => $translation['last_name']]
            );
        }

        return redirect()->back()->with('success', 'Buyer details updated successfully.');
    }

    public function destroy($id)
    {
        $buyer = User::findOrFail($id);
        if ($buyer->is_admin) {
            return redirect()->back()->with('error', 'Cannot delete admin account.');
        }

        // Ensure the buyer is not linked to active transactions
        if ($buyer->orders()->exists()) {
            return redirect()->back()->with('error', 'Buyer cannot be deleted as they have active transactions.');
        }

        $buyer->delete();

        return redirect()->back()->with('success', 'Buyer deleted successfully.');
    }

    public function bulkDelete(Request $request)
    {
        $ids = json_decode($request->input('ids', []));

        if (empty($ids)) {
            return redirect()->back()->with('error', 'No buyers selected for deletion.');
        }

        foreach ($ids as $id) {
            $buyer = User::findOrFail($id);
            if (!$buyer->is_admin) {
                if (!$buyer->orders()->exists()) {
                    $buyer->delete();
                }
            }
        }

        return redirect()->back()->with('success', 'Selected buyers deleted successfully.');
    }

    public function bulkUpdateProfile(Request $request)
    {
        $ids = json_decode($request->input('ids', []));
        $newProfileType = $request->input('profile_type');

        if (empty($ids) || !in_array($newProfileType, ['regular', 'vip'])) {
            return redirect()->back()->with('error', 'Invalid selection.');
        }

        User::whereIn('id', $ids)->update(['profile_type' => $newProfileType]);

        return redirect()->back()->with('success', 'Selected buyers updated successfully.');
    }
}
