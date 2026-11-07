<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RegistrationSchedule;

class ScheduleController extends Controller
{
    // ==========================================
    // 1. HALAMAN PUBLIK
    // ==========================================
    public function publicIndex()
    {
        $schedules = RegistrationSchedule::orderBy('start_date', 'asc')->get();
        return view('spmb.jadwal', compact('schedules'));
    }

    // ==========================================
    // 2. HALAMAN ADMIN (CRUD JADWAL)
    // ==========================================
    public function adminIndex()
    {
        $schedules = RegistrationSchedule::orderBy('start_date', 'asc')->get();
        return view('admin.schedules', compact('schedules'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:150',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:Menunggu,Dibuka,Selesai'
        ]);

        RegistrationSchedule::create($request->all());
        return back()->with('success', 'Jadwal pendaftaran berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:150',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:Menunggu,Dibuka,Selesai'
        ]);

        RegistrationSchedule::findOrFail($id)->update($request->all());
        return back()->with('success', 'Jadwal pendaftaran berhasil diperbarui!');
    }

    public function destroy($id)
    {
        RegistrationSchedule::findOrFail($id)->delete();
        return back()->with('success', 'Jadwal pendaftaran berhasil dihapus!');
    }
}