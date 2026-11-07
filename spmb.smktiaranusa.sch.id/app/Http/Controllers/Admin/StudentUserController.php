<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StudentUserController extends Controller
{
    // Menampilkan seluruh data akun siswa (Role: student)
    public function index(Request $request)
    {
        $query = User::where('role', 'student');

        // Fitur Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('whatsapp', 'like', "%{$search}%");
            });
        }

        // Paginasi 10 data per halaman
        $users = $query->orderBy('created_at', 'desc')->paginate(10);
        return view('admin.sistem.siswa.index', compact('users'));
    }

    // Menampilkan siswa yang belum mengisi formulir (Tidak ada di tabel registrations)
    public function pending()
    {
        $users = User::where('role', 'student')
            ->whereDoesntHave('registrations', function ($query) {
                $query->where('status', '!=', 'DRAFT');
            })
            ->paginate(10); // Ubah ->get() menjadi ->paginate()

        return view('admin.sistem.siswa.pending', compact('users'));
    }

    // Aksi hapus akun siswa
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return back()->with('success', 'Akun siswa berhasil dihapus.');
    }

    // Aksi ganti password
    public function updatePassword(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|min:6'
        ]);

        $user = User::findOrFail($id);
        $user->password = Hash::make($request->password);
        $user->save();

        return back()->with('success', 'Password siswa berhasil diperbarui.');
    }
}
