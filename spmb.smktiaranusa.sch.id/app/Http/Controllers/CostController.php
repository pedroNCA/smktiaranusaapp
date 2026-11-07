<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RegistrationCost;

class CostController extends Controller
{
    public function adminIndex()
    {
        $costs = RegistrationCost::orderBy('id', 'asc')->get();
        return view('admin.costs', compact('costs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        RegistrationCost::create($request->all());
        return back()->with('success', 'Rincian biaya berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        RegistrationCost::findOrFail($id)->update($request->all());
        return back()->with('success', 'Rincian biaya berhasil diperbarui!');
    }

    public function destroy($id)
    {
        RegistrationCost::findOrFail($id)->delete();
        return back()->with('success', 'Rincian biaya berhasil dihapus!');
    }
}
