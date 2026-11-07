@extends('layouts.admin')
@section('title', 'Manajemen User - SPMB')
@section('content')

<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Manajemen Pengguna</h1>
        <p class="text-gray-500 text-sm mt-1">Kelola data admin, panitia, atau staf yang memiliki akses ke sistem.</p>
    </div>
    
    {{-- Tombol Buka Modal Tambah --}}
    <button type="button" onclick="openModal('add')" class="inline-flex items-center px-4 py-2.5 bg-blue-600 text-white text-sm font-bold rounded-xl hover:bg-blue-700 shadow-sm transition">
        <i class="fas fa-plus mr-2"></i> Tambah User Baru
    </button>
</div>

@if(session('success'))
<div class="p-4 mb-6 text-sm text-green-800 rounded-xl bg-green-50 border border-green-200 flex items-center shadow-sm">
    <i class="fas fa-check-circle mr-2 text-green-600"></i> {{ session('success') }}
</div>
@endif

@if($errors->any())
<div class="p-4 mb-6 text-sm text-red-800 rounded-xl bg-red-50 border border-red-200 shadow-sm">
    <ul class="list-disc pl-5">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto w-full">
        <table class="w-full text-sm text-left text-gray-600 whitespace-nowrap">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-5 py-4 font-bold">Nama Lengkap</th>
                    <th class="px-5 py-4 font-bold">Email</th>
                    <th class="px-5 py-4 font-bold text-center">Hak Akses (Role)</th>
                    <th class="px-5 py-4 font-bold text-center">Aksi Manajemen</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($users ?? [] as $user)
                <tr class="hover:bg-blue-50/50 transition-colors">
                    <td class="px-5 py-4 font-bold text-gray-900">{{ $user->name }}</td>
                    <td class="px-5 py-4 font-medium text-gray-600">{{ $user->email }}</td>
                    
                    <td class="px-5 py-4 text-center">
                        @php
                            $roleColors = [
                                'super_admin' => 'bg-purple-100 text-purple-700 border-purple-200',
                                'admin' => 'bg-blue-100 text-blue-700 border-blue-200',
                                'panitia' => 'bg-amber-100 text-amber-700 border-amber-200',
                            ];
                            $colorClass = $roleColors[$user->role ?? ''] ?? 'bg-gray-100 text-gray-700 border-gray-200';
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-1 text-[11px] font-bold rounded-full border {{ $colorClass }}">
                            {{ ucwords(str_replace('_', ' ', $user->role ?? 'Unknown')) }}
                        </span>
                    </td>
                    
                    <td class="px-5 py-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            {{-- Tombol Edit --}}
                            <button type="button" 
                                onclick="openModal('edit', {{ $user->id }}, '{{ $user->name }}', '{{ $user->email }}', '{{ $user->role }}')" 
                                class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-600 hover:text-white transition-colors shadow-sm">
                                <i class="fas fa-edit mr-1"></i> Edit
                            </button>
                            
                            {{-- Tombol Hapus (Cegah hapus diri sendiri) --}}
                            @if(auth()->id() !== $user->id)
                                <form action="{{ route('admin.sistem.manajemen-user.destroy', $user->id) }}" method="POST" data-title="Hapus Pengguna?" data-text="Data pengguna admin ini akan dihapus dari sistem secara permanen.">
                                    @csrf @method('DELETE')
                                    <button type="button" onclick="konfirmasiAksi(this, 'Ya, Hapus', '#d33')" class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-600 hover:text-white transition-colors shadow-sm">
                                        <i class="fas fa-trash-alt mr-1"></i> Hapus
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-5 py-12 text-center text-gray-400 font-medium">Belum ada data pengguna yang terdaftar.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- MODAL TAMBAH / EDIT USER --}}
<div id="userModal" class="fixed inset-0 z-50 bg-gray-900 bg-opacity-50 items-center justify-center" style="display: none;">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden transform transition-all">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <h3 id="modalTitle" class="text-lg font-bold text-gray-900">Tambah User Baru</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        
        <form id="userForm" action="{{ route('admin.sistem.manajemen-user.store') }}" method="POST" class="p-6">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Lengkap</label>
                    <input type="text" name="name" id="userName" required class="w-full text-sm border-gray-300 rounded-xl px-3 py-2 border focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Alamat Email</label>
                    <input type="email" name="email" id="userEmail" required class="w-full text-sm border-gray-300 rounded-xl px-3 py-2 border focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Hak Akses (Role)</label>
                    <select name="role" id="userRole" required class="w-full text-sm border-gray-300 rounded-xl px-3 py-2 border focus:ring-blue-500 focus:border-blue-500">
                        <option value="admin">Admin</option>
                        <option value="panitia">Panitia</option>
                        <option value="super_admin">Super Admin</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Password <span id="passwordHelp" class="text-xs text-gray-400 font-normal">(Kosongkan jika tidak ingin diubah)</span>
                    </label>
                    <input type="password" name="password" id="userPassword" class="w-full text-sm border-gray-300 rounded-xl px-3 py-2 border focus:ring-blue-500 focus:border-blue-500" placeholder="Minimal 8 karakter">
                </div>
            </div>
            
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="closeModal()" class="px-4 py-2 text-sm font-semibold text-gray-600 bg-gray-100 rounded-xl hover:bg-gray-200">Batal</button>
                <button type="submit" class="px-5 py-2 text-sm font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(mode, id = null, name = '', email = '', role = 'admin') {
    const modal = document.getElementById('userModal');
    const form = document.getElementById('userForm');
    const title = document.getElementById('modalTitle');
    const method = document.getElementById('formMethod');
    const passwordHelp = document.getElementById('passwordHelp');
    const passwordInput = document.getElementById('userPassword');
    
    if (mode === 'add') {
        title.innerText = 'Tambah User Baru';
        form.action = "{{ route('admin.sistem.manajemen-user.store') }}";
        method.value = 'POST';
        
        document.getElementById('userName').value = '';
        document.getElementById('userEmail').value = '';
        document.getElementById('userRole').value = 'admin';
        
        passwordHelp.classList.add('hidden'); // Password wajib saat tambah
        passwordInput.required = true;
    } else {
        title.innerText = 'Edit Data User';
        form.action = `/admin/sistem/manajemen-user/${id}`;
        method.value = 'PUT';
        
        document.getElementById('userName').value = name;
        document.getElementById('userEmail').value = email;
        document.getElementById('userRole').value = role;
        
        passwordHelp.classList.remove('hidden'); // Password opsional saat edit
        passwordInput.required = false;
    }
    
    modal.style.display = 'flex';
}

function closeModal() {
    document.getElementById('userModal').style.display = 'none';
}

function konfirmasiAksi(button, confirmText, confirmColor) {
    const form = button.closest('form');
    Swal.fire({
        title: form.getAttribute('data-title'),
        text: form.getAttribute('data-text'),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: confirmColor,
        cancelButtonColor: '#6b7280',
        confirmButtonText: confirmText,
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({ title: 'Memproses...', text: 'Mohon tunggu.', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});
            form.submit(); // form.submit() diaktifkan
        }
    });
}
</script>
@endsection