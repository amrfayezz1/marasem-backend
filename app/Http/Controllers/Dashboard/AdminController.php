<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Language;
use App\Models\User;
use App\Models\UserTranslation;
use App\Models\AdminPrivilege;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Mail\NewAdminNotification;

class AdminController extends Controller
{
    private function getAvailablePrivileges()
    {
        return [
            'dashboard' => tt('Dashboard & Insights'),
            'collections' => tt('Collections'),
            'categories' => tt('Categories'),
            'subcategories' => tt('Subcategories'),
            'events' => tt('Events'),
            'currencies' => tt('Currencies'),
            'languages' => tt('Languages'),
            'orders' => tt('Orders'),
            'artworks' => tt('Art List'),
            'sellers' => tt('Seller List'),
            'buyers' => tt('Buyer List'),
            'admins' => tt('Admins'),
        ];
    }

    public function index(Request $request)
    {
        $userPreferredLanguage = auth()->user()->preferred_language;
        $query = User::where('is_admin', '>', 0)
            ->where('id', '!=', auth()->user()->id)
            ->with(['adminPrivileges', 'translations']);

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhereHas('translations', function ($t) use ($search) {
                        $t->where('first_name', 'LIKE', "%{$search}%")
                            ->orWhere('last_name', 'LIKE', "%{$search}%");
                    });
            });
        }

        $admins = $query->paginate(10);

        // Override the admin names with their translations, if available.
        foreach ($admins as $admin) {
            $translation = $admin->translations
                ->where('language_id', $userPreferredLanguage)
                ->first();
            if ($translation) {
                $admin->first_name = $translation->first_name;
                $admin->last_name = $translation->last_name;
            }
        }

        $users = User::where('is_admin', 0)->get();
        $privileges = $this->getAvailablePrivileges();
        $languages = Language::all();

        return view('dashboard.admins.index', compact('admins', 'users', 'privileges', 'languages'));
    }

    public function show($id)
    {
        $userPreferredLanguage = auth()->user()->preferred_language;
        $admin = User::with('adminPrivileges', 'translations')->findOrFail($id);

        // Ensure adminPrivileges exist; if not, create a default record.
        if ($admin->adminPrivileges == NULL) {
            AdminPrivilege::create([
                'user_id' => $admin->id,
                'privileges' => json_encode([]),
            ]);
        }

        // Override the admin's first and last names with translated values if available.
        $translation = $admin->translations
            ->where('language_id', $userPreferredLanguage)
            ->first();
        if ($translation) {
            $admin->first_name = $translation->first_name;
            $admin->last_name = $translation->last_name;
        }

        return response()->json($admin);
    }

    public function store(Request $request)
    {
        \Log::info($request->all());
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|exists:users,id', // If making an existing user an admin

            'email' => 'nullable|required_without:user_id|email|unique:users,email',
            'password' => 'nullable|required_without:user_id|string|min:6',

            'translations' => ['nullable', 'array'],
            'translations.*.language_id' => ['required_without:user_id'],
            'translations.*.first_name' => ['required_without:user_id'],
            'translations.*.last_name' => ['required_without:user_id'],
        ]);

        // If an existing user is selected, ignore translations
        if ($request->filled('user_id')) {
            $validator->after(function ($validator) {
                $validator->sometimes('translations', 'nullable', function ($input) {
                    return false;
                });
            });
        }

        // Stop execution if validation fails
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        if ($request->user_id) {
            $user = User::findOrFail($request->user_id);
            $user->update(['is_admin' => 1]);
        } else {
            // Get default language (language_id = 1)
            $defaultTranslation = collect($request->translations)->firstWhere('language_id', 1);
            if (!$defaultTranslation) {
                return redirect()->back()->with('error', 'Default language translation is required.');
            }

            // Create user with default language values
            $user = User::create([
                'first_name' => $defaultTranslation['first_name'],
                'last_name' => $defaultTranslation['last_name'],
                'email' => $request->email,
                'country_code' => $request->country_code,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'is_admin' => 1,
            ]);

            // Store translations
            foreach ($request->translations as $translation) {
                UserTranslation::updateOrCreate(
                    ['user_id' => $user->id, 'language_id' => $translation['language_id']],
                    ['first_name' => $translation['first_name'], 'last_name' => $translation['last_name']]
                );
            }
        }


        // Send email notification
        Mail::to($user->email)->send(new NewAdminNotification($user, $request->password));
        AdminPrivilege::create([
            'user_id' => $user->id,
            'privileges' => json_encode([]),
        ]);

        return redirect()->back()->with('success', 'Admin added successfully.');
    }

    public function update(Request $request, $id)
    {
        $admin = User::findOrFail($id);

        $request->validate([
            'translations' => 'required|array',
            'translations.*.language_id' => 'required|exists:languages,id',
            'translations.*.first_name' => 'required|string|max:100',
            'translations.*.last_name' => 'required|string|max:100',

            'email' => 'required|email|unique:users,email,' . $id,
        ]);

        // Get default language (language_id = 1)
        $defaultTranslation = collect($request->translations)->firstWhere('language_id', 1);
        if (!$defaultTranslation) {
            return redirect()->back()->with('error', 'Default language translation is required.');
        }

        // Update user with default language values
        $admin->update([
            'first_name' => $defaultTranslation['first_name'],
            'last_name' => $defaultTranslation['last_name'],
            'email' => $request->email,
        ]);

        // Update or create translations
        foreach ($request->translations as $translation) {
            UserTranslation::updateOrCreate(
                ['user_id' => $admin->id, 'language_id' => $translation['language_id']],
                ['first_name' => $translation['first_name'], 'last_name' => $translation['last_name']]
            );
        }

        return redirect()->back()->with('success', 'Admin details updated successfully.');
    }

    public function remove($id)
    {
        $admin = User::findOrFail($id);
        $admin->update(['is_admin' => 0]);
        AdminPrivilege::where('user_id', $id)->delete();

        return redirect()->back()->with('success', 'Admin privileges removed.');
    }

    public function updatePrivileges(Request $request, $id)
    {
        $adminPrivileges = AdminPrivilege::where('user_id', $id)->firstOrFail();

        $adminPrivileges->update([
            'privileges' => json_encode($request->privileges),
        ]);

        return redirect()->back()->with('success', 'Admin privileges updated successfully.');
    }
}