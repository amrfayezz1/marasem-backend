<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Currency;

class CurrencyController extends Controller
{
    public function index(Request $request)
    {
        $query = Currency::query();

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('symbol', 'LIKE', "%{$search}%")
                    ->orWhere('rate', 'LIKE', "%{$search}%");
            });
        }

        $currencies = $query->paginate(10);
        return view('dashboard.currencies.index', compact('currencies'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:currencies,name|max:100',
            'symbol' => 'required|string|unique:currencies,symbol|max:10',
            'rate' => 'required|numeric|min:0',
        ], [
            'name.required' => 'All fields are required.',
            'symbol.required' => 'All fields are required.',
            'rate.required' => 'All fields are required.',
            'rate.numeric' => 'Rate must be a numeric value.',
        ]);

        Currency::create($request->only(['name', 'symbol', 'rate']));

        return redirect()->back()->with('success', 'Currency added successfully.');
    }

    public function show($id)
    {
        $currency = Currency::findOrFail($id);
        return response()->json(['currency' => $currency]);
    }

    public function update(Request $request, $id)
    {
        $currency = Currency::findOrFail($id);

        $request->validate([
            'name' => 'required|string|unique:currencies,name,' . $currency->id . '|max:100',
            'symbol' => 'required|string|unique:currencies,symbol,' . $currency->id . '|max:10',
            'rate' => 'required|numeric|min:0',
        ], [
            'name.required' => 'All fields are required.',
            'symbol.required' => 'All fields are required.',
            'rate.required' => 'All fields are required.',
            'rate.numeric' => 'Rate must be a numeric value.',
        ]);

        $currency->update($request->only(['name', 'symbol', 'rate']));

        return redirect()->back()->with('success', 'Currency updated successfully.');
    }

    public function destroy($id)
    {
        Currency::findOrFail($id)->delete();

        return redirect()->back()->with('success', 'Currency deleted successfully.');
    }

    public function bulkDelete(Request $request)
    {
        $ids = json_decode($request->input('ids', []));

        if (empty($ids)) {
            return redirect()->back()->with('error', 'No currencies selected for deletion.');
        }

        Currency::whereIn('id', $ids)->delete();

        return redirect()->back()->with('success', 'Selected currencies deleted successfully.');
    }
}