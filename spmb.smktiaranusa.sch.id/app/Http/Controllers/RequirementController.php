<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RegistrationRequirement;

class RequirementController extends Controller
{
    public function adminIndex()
    {
        $requirements = RegistrationRequirement::orderBy('type', 'asc')->get();
        return view('admin.requirements', compact('requirements'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:Umum,Reguler,Beasiswa',
            'description' => 'required|string',
        ]);

        RegistrationRequirement::create($request->all());
        return back()->with('success', 'Persyaratan berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:Umum,Reguler,Beasiswa',
            'description' => 'required|string',
        ]);

        RegistrationRequirement::findOrFail($id)->update($request->all());
        return back()->with('success', 'Persyaratan berhasil diperbarui!');
    }

    public function destroy($id)
    {
        RegistrationRequirement::findOrFail($id)->delete();
        return back()->with('success', 'Persyaratan berhasil dihapus!');
    }
}