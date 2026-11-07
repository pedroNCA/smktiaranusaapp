<?php

namespace App\Http\Controllers;

use App\Models\RegistrationPeriod;
use Illuminate\Http\Request;

class RegistrationPeriodController extends Controller
{
    public function index()
    {
        $periods = RegistrationPeriod::orderBy('start_date', 'desc')->get();

        return view('admin.registration-periods', compact('periods'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        RegistrationPeriod::create($validated);

        return redirect()
            ->back()
            ->with('success', 'Gelombang pendaftaran berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $period = RegistrationPeriod::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $period->update($validated);

        return redirect()
            ->back()
            ->with('success', 'Gelombang pendaftaran berhasil diperbarui.');
    }

    public function destroy($id)
    {
        RegistrationPeriod::findOrFail($id)->delete();

        return redirect()
            ->back()
            ->with('success', 'Gelombang pendaftaran berhasil dihapus.');
    }
}
