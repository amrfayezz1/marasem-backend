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
use App\Mail\NewAdminNotification;

class AdminController extends Controller
{
    private function getAvailablePrivileges()
    {
        return [
            'dashboard' => 'Dashboard & Insights',
            'collections' => 'Collections',
            'categories' => 'Categories',
            'subcategories' => 'Subcategories',
            'events' => 'Events',
            'currencies' => 'Currencies',
            'languages' => 'Languages',
            'orders' => 'Orders',
            'artworks' => 'Art List',
            'sellers' => 'Seller List',
            'buyers' => 'Buyer List',
            'admins' => 'Admins',
        ];
    }

    public function index(Request $request)
    {
        $query = User::where('is_admin', 1)->with(['adminPrivileges', 'translations']);

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
        $users = User::where('is_admin', 0)->get();
        $privileges = $this->getAvailablePrivileges();
        $languages = Language::all();

        return view('dashboard.admins.index', compact('admins', 'users', 'privileges', 'languages'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'nullable|exists:users,id', // If making an existing user an admin
            'translations' => 'required_without:user_id|array',
            'translations.*.language_id' => 'required|exists:languages,id',
            'translations.*.first_name' => 'required|string|max:100',
            'translations.*.last_name' => 'required|string|max:100',

            'email' => 'nullable|required_without:user_id|email|unique:users,email',
            'password' => 'nullable|required_without:user_id|string|min:6',
        ]);

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

            // Send email notification
            Mail::to($user->email)->queue(new NewAdminNotification($user, $request->password));
        }

        // Store translations
        foreach ($request->translations as $translation) {
            UserTranslation::updateOrCreate(
                ['user_id' => $user->id, 'language_id' => $translation['language_id']],
                ['first_name' => $translation['first_name'], 'last_name' => $translation['last_name']]
            );
        }

        AdminPrivilege::create([
            'user_id' => $user->id,
            'privileges' => json_encode([]),
        ]);

        return redirect()->back()->with('success', 'Admin added successfully.');
    }

    public function show($id)
    {
        $admin = User::with('adminPrivileges', 'translations')->findOrFail($id);
        if($admin->adminPrivileges == NULL) {
            AdminPrivilege::create([
                'user_id' => $admin->id,
                'privileges' => json_encode([]),
            ]);
        }
        return response()->json($admin);
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