<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Models\TpaResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ObservationController extends Controller
{
    /**
     * Menampilkan daftar pendaftar dan status checklist.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $registrations = Registration::with(['user', 'student'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('spmb_id', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $registrationIds = $registrations->getCollection()->pluck('id');

        // Ambil pendaftar yang sudah memiliki hasil CBT TPA.
        $registrationIdsWithTpa = TpaResult::whereIn(
            'registration_id',
            $registrationIds
        )
            ->pluck('registration_id')
            ->unique()
            ->values()
            ->toArray();

        // Sinkronkan status TPA ke database.
        if (!empty($registrationIdsWithTpa)) {
            Registration::whereIn('id', $registrationIdsWithTpa)
                ->where('check_tpa', '!=', 1)
                ->update([
                    'check_tpa' => 1,
                ]);
        }

        return view('admin.observasi.index', compact(
            'registrations',
            'search',
            'registrationIdsWithTpa'
        ));
    }

    /**
     * Memperbarui checklist pendaftar.
     */
    public function updateChecklist(Request $request, $id)
    {
        $request->validate([
            'check_formulir' => ['nullable', 'boolean'],
            'check_dp' => ['nullable', 'boolean'],
            'check_tpa' => ['nullable', 'boolean'],
            'check_observasi' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($request, $id) {
            $reg = Registration::where('id', $id)
                ->lockForUpdate()
                ->firstOrFail();

            // Jika hasil CBT sudah ada, TPA otomatis selesai.
            $hasTpaResult = TpaResult::where(
                'registration_id',
                $reg->id
            )->exists();

            $reg->update([
                'check_formulir' => $request->boolean('check_formulir'),
                'check_dp' => $request->boolean('check_dp'),

                // Tetap bisa manual jika belum mengerjakan CBT.
                'check_tpa' => $hasTpaResult
                    ? 1
                    : $request->boolean('check_tpa'),

                'check_observasi' => $request->boolean('check_observasi'),
            ]);
        });

        return back()->with(
            'success',
            'Status checklist berhasil diperbarui.'
        );
    }
}