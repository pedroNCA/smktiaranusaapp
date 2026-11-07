<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SystemController extends Controller
{
    public function auditLog()
    {
        $logs = AuditLog::with('user')->orderBy('created_at', 'desc')->paginate(20);
        return view('admin.sistem.audit-log', compact('logs'));
    }

    public function userManagement()
    {
        // PERBAIKAN: Hanya panggil user dengan role super_admin atau admin
        $users = User::whereIn('role', ['super_admin', 'admin'])->get();
        return view('admin.sistem.manajemen-user', compact('users'));
    }

    public function storeUser(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:admin,panitia,super_admin',
            'password' => 'required|string|min:8',
        ]);

        \App\Models\User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'password' => bcrypt($request->password),
        ]);

        return back()->with('success', 'User admin/panitia berhasil ditambahkan.');
    }

    public function updateUser(\Illuminate\Http\Request $request, $id)
    {
        $user = \App\Models\User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$id,
            'role' => 'required|in:admin,panitia,super_admin',
            'password' => 'nullable|string|min:8',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->role = $request->role;
        
        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }

        $user->save();

        return back()->with('success', 'Data user berhasil diperbarui.');
    }

    public function destroyUser($id)
    {
        $user = \App\Models\User::findOrFail($id);
        
        // Mencegah admin menghapus dirinya sendiri yang sedang login
        if (Auth::id() === $user->getKey()) {
            return back()->withErrors(['Tidak dapat menghapus akun Anda sendiri.']);
        }
        
        $user->delete();
        return back()->with('success', 'Akun user berhasil dihapus permanen.');
    }
}