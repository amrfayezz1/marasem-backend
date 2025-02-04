<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserTranslation;
use App\Models\ArtistDetail;
use App\Models\ArtistPickupLocation;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\NewSellerNotification;

class SellerController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('is_artist', true)->with(['artistDetails', 'artistDetails.translations', 'translations']);

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->whereHas('artistDetails', function ($q) use ($request) {
                $q->where('status', $request->status);
            });
        }

        $sellers = $query->paginate(10);
        $languages = Language::all();
        return view('dashboard.sellers.index', compact('sellers', 'languages'));
    }

    public function show(Request $request, $id)
    {
        $artist = User::with(['artistDetails', 'artistDetails.translations', 'translations'])->findOrFail($id);
        return response()->json([
            "seller" => $artist,
            "translations" => $artist->translations
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'translations' => 'required|array',
            'translations.*.language_id' => 'required|exists:languages,id',
            'translations.*.first_name' => 'required|string|max:100',
            'translations.*.last_name' => 'required|string|max:100',

            'email' => 'required|email|unique:users,email',
            'country_code' => 'required|string|max:10',
            'phone' => 'required|string|max:20|unique:users,phone',
            'profile_picture' => 'nullable|image|max:2048',
            'status' => 'required|in:approved,pending,rejected',
        ]);

        $imagePath = $request->hasFile('profile_picture') ? $request->file('profile_picture')->store('sellers', 'public') : null;

        // Get default language (language_id = 1)
        $defaultTranslation = collect($request->translations)->firstWhere('language_id', 1);
        if (!$defaultTranslation) {
            return redirect()->back()->with('error', 'Default language translation is required.');
        }

        // Create user with default language values
        $randomPassword = Str::random(10);
        $user = User::create([
            'first_name' => $defaultTranslation['first_name'],
            'last_name' => $defaultTranslation['last_name'],
            'email' => $request->email,
            'password' => Hash::make($randomPassword),
            'country_code' => $request->country_code,
            'phone' => $request->phone,
            'is_artist' => true,
            'profile_picture' => $imagePath,
        ]);

        // Create artist detail
        ArtistDetail::create([
            'user_id' => $user->id,
            'registration_step' => 1,
            'status' => $request->status,
        ]);

        // Store translations
        foreach ($request->translations as $translation) {
            UserTranslation::create([
                'user_id' => $user->id,
                'language_id' => $translation['language_id'],
                'first_name' => $translation['first_name'],
                'last_name' => $translation['last_name'],
            ]);
        }
        Mail::to($user->email)->send(new NewSellerNotification($user, $randomPassword));
        return redirect()->back()->with('success', 'Seller added successfully.');
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $artistDetail = ArtistDetail::where('user_id', $id)->firstOrFail();

        $request->validate([
            'translations' => 'required|array',
            'translations.*.language_id' => 'required|exists:languages,id',
            'translations.*.first_name' => 'required|string|max:100',
            'translations.*.last_name' => 'required|string|max:100',

            'email' => 'required|email|unique:users,email,' . $id,
            'country_code' => 'required|string|max:10',
            'phone' => 'required|string|max:20|unique:users,phone,' . $id,
            'profile_picture' => 'nullable|image|max:2048',
            'status' => 'required|in:approved,pending,rejected',
        ]);

        if ($request->hasFile('profile_picture')) {
            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
            }
            $imagePath = $request->file('profile_picture')->store('sellers', 'public');
            $user->profile_picture = $imagePath;
        }

        // Get default language (language_id = 1)
        $defaultTranslation = collect($request->translations)->firstWhere('language_id', 1);
        if (!$defaultTranslation) {
            return redirect()->back()->with('error', 'Default language translation is required.');
        }

        // Update user with default language values
        $user->update([
            'first_name' => $defaultTranslation['first_name'],
            'last_name' => $defaultTranslation['last_name'],
            'email' => $request->email,
            'country_code' => $request->country_code,
            'phone' => $request->phone,
        ]);

        // Update artist detail status
        $artistDetail->update(['status' => $request->status]);

        // Update or create translations
        foreach ($request->translations as $translation) {
            UserTranslation::updateOrCreate(
                ['user_id' => $user->id, 'language_id' => $translation['language_id']],
                ['first_name' => $translation['first_name'], 'last_name' => $translation['last_name']]
            );
        }

        return redirect()->back()->with('success', 'Seller details updated successfully.');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->is_admin) {
            return redirect()->back()->with('error', 'Cannot delete admin user.');
        }

        if ($user->profile_picture) {
            Storage::disk('public')->delete($user->profile_picture);
        }

        ArtistDetail::where('user_id', $id)->delete();
        ArtistPickupLocation::where('artist_id', $id)->delete();
        $user->delete();

        return redirect()->back()->with('success', 'Seller deleted successfully.');
    }

    public function bulkDelete(Request $request)
    {
        $ids = json_decode($request->input('ids', []));

        if (empty($ids)) {
            return redirect()->back()->with('error', 'No sellers selected for deletion.');
        }

        foreach ($ids as $id) {
            $user = User::findOrFail($id);

            if ($user->is_admin) {
                continue;
            }

            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
            }

            ArtistDetail::where('user_id', $id)->delete();
            ArtistPickupLocation::where('artist_id', $id)->delete();
            $user->delete();
        }

        return redirect()->back()->with('success', 'Selected sellers deleted successfully.');
    }

    public function bulkUpdateStatus(Request $request)
    {
        $ids = json_decode($request->input('ids', []));
        $newStatus = $request->input('status');

        if (empty($ids) || !in_array($newStatus, ['approved', 'pending', 'rejected'])) {
            return redirect()->back()->with('error', 'Invalid selection.');
        }

        ArtistDetail::whereIn('user_id', $ids)->update(['status' => $newStatus]);

        return redirect()->back()->with('success', 'Selected sellers updated successfully.');
    }
}
