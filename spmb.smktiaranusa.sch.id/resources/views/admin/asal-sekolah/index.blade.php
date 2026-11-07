@extends('layouts.admin')
@section('title', 'Data Asal Sekolah')

@section('content')
<div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Rekap Asal Sekolah</h1>
        <p class="text-gray-500 text-sm mt-1">Daftar asal sekolah diurutkan dari pendaftar terbanyak.</p>
    </div>
    <a href="{{ route('admin.asal-sekolah.export') }}" class="px-4 py-2 bg-green-600 text-white font-bold rounded-xl hover:bg-green-700 transition-colors shadow-sm flex items-center gap-2 text-sm">
        <i class="fas fa-file-excel"></i> Download Excel
    </a>
</div>

{{-- Baris Pencarian --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 mb-6">
    <form method="GET" action="{{ route('admin.asal-sekolah.index') }}" class="flex gap-2">
        <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama sekolah..." class="w-full border-gray-300 rounded-xl text-sm px-3 py-2 border focus:ring-blue-500 focus:border-blue-500">
        <button type="submit" class="px-5 bg-blue-600 text-white rounded-xl font-bold text-sm hover:bg-blue-700 transition-colors">Cari</button>
        @if($search)
            <a href="{{ route('admin.asal-sekolah.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-xl hover:bg-gray-200 transition-colors text-center flex items-center">Reset</a>
        @endif
    </form>
</div>

{{-- Tabel Data --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50/75 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-4 font-bold">No</th>
                    <th class="px-6 py-4 font-bold">Nama Asal Sekolah</th>
                    <th class="px-6 py-4 font-bold text-center">Jumlah Pendaftar</th>
                    <th class="px-6 py-4 font-bold text-center">Aksi (Edit / Hapus)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($schools as $idx => $school)
                <tr class="hover:bg-blue-50/30 transition-colors">
                    <td class="px-6 py-4 font-semibold text-gray-600">{{ $schools->firstItem() + $idx }}</td>
                    
                    <td class="px-6 py-4 font-bold text-gray-900">
                        {{ $school->school_origin }}
                    </td>

                    <td class="px-6 py-4 text-center">
                        <span class="bg-blue-100 text-blue-800 text-xs font-bold px-3 py-1 rounded-full border border-blue-200">
                            {{ $school->total_students }} Siswa
                        </span>
                    </td>

                    <td class="px-6 py-4">
                        <div class="flex items-center justify-center gap-2">
                            {{-- Form Edit Inline --}}
                            <form action="{{ route('admin.asal-sekolah.update', $school->school_origin) }}" method="POST" class="flex items-center gap-2 form-edit-sekolah">
                                @csrf 
                                @method('PUT')
                                
                                {{-- Input nama baru (disabled secara default) --}}
                                <input type="text" 
                                       name="new_name" 
                                       value="{{ $school->school_origin }}" 
                                       class="input-nama-sekolah w-52 text-xs border-gray-300 rounded-lg p-2 border focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:text-gray-500 disabled:cursor-not-allowed" 
                                       required 
                                       disabled>
                                
                                {{-- Tombol Edit yang berubah jadi Simpan --}}
                                <button type="button" 
                                        onclick="toggleEditForm(this)" 
                                        class="btn-edit px-3 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-xs font-bold transition-all shadow-sm">
                                    Edit
                                </button>
                            </form>

                            {{-- Tombol Hapus --}}
                            <form action="{{ route('admin.asal-sekolah.destroy', $school->school_origin) }}" method="POST" onsubmit="return confirm('Kosongkan asal sekolah ({{ $school->school_origin }}) dari seluruh siswa terkait?')">
                                @csrf 
                                @method('DELETE')
                                <button type="submit" class="px-3 py-2 bg-red-50 hover:bg-red-600 text-red-600 hover:text-white rounded-lg text-xs font-bold transition-all shadow-sm" title="Hapus Asal Sekolah">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-12 text-center text-gray-400 italic">
                        Belum ada data asal sekolah atau tidak ditemukan.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($schools->hasPages() || $schools->total() > 0)
    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-4">
        <div class="text-xs text-gray-500">
            Menampilkan <strong>{{ $schools->firstItem() ?? 0 }}</strong> sampai <strong>{{ $schools->lastItem() ?? 0 }}</strong> dari total <strong>{{ $schools->total() }}</strong> sekolah
        </div>
        <div>
            {{ $schools->links() }}
        </div>
    </div>
    @endif
</div>

{{-- JavaScript untuk Toggle Edit Input --}}
<script>
function toggleEditForm(button) {
    const form = button.closest('form');
    const input = form.querySelector('.input-nama-sekolah');
    
    // Jika input masih tersembunyi/terkunci (Berarti mau masuk ke mode Edit)
    if (input.disabled) {
        // Buka kunci input
        input.disabled = false;
        input.focus();
        input.select();
        
        // Ubah tampilan tombol menjadi 'Simpan'
        button.textContent = 'Simpan';
        button.classList.remove('bg-amber-500', 'hover:bg-amber-600');
        button.classList.add('bg-blue-600', 'hover:bg-blue-700');
    } 
    // Jika input sudah terbuka (Berarti tombol ini sedang berfungsi sebagai tombol Simpan)
    else {
        // Tampilkan loading sebentar dan langsung submit form
        button.textContent = 'Menyimpan...';
        form.submit();
    }
}
</script>
@endsection