@extends('layouts.admin')
@section('title', 'Cetak Formulir Pendaftaran')

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Cetak Formulir</h2>
            <p class="text-sm text-gray-500">Pilih dan cetak formulir pendaftaran siswa.</p>
        </div>
    </div>

    <!-- Filter & Search -->
    <form action="{{ route('admin.cetak-formulir.index') }}" method="GET" class="flex flex-col md:flex-row gap-3 mb-6">
        <select name="period_id" class="border-gray-300 rounded-lg text-sm" onchange="this.form.submit()">
            <option value="">-- Semua Gelombang --</option>
            @foreach($periods as $p)
                <option value="{{ $p->id }}" {{ $period_filter == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
        </select>
        <div class="flex flex-1">
            <input type="text" name="search" value="{{ $search }}" placeholder="Cari ID SPMB atau Nama..." class="w-full border-gray-300 rounded-l-lg text-sm">
            <button type="submit" class="px-4 bg-blue-600 text-white rounded-r-lg hover:bg-blue-700">Cari</button>
        </div>
    </form>

    <!-- Tabel Data -->
    <form action="{{ route('admin.cetak-formulir.print-bulk') }}" method="POST" target="_blank">
        @csrf
        <div class="flex justify-end mb-3">
            <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm font-bold rounded-lg hover:bg-green-700 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Cetak Terpilih
            </button>
        </div>

        <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th class="p-4 w-4">
                            <input type="checkbox" id="selectAll" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                        </th>
                        <th class="px-6 py-3">ID SPMB</th>
                        <th class="px-6 py-3">Nama Siswa</th>
                        <th class="px-6 py-3">Jurusan</th>
                        <th class="px-6 py-3">Gelombang</th>
                        <th class="px-6 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($registrations as $reg)
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="p-4">
                            <input type="checkbox" name="ids[]" value="{{ $reg->id }}" class="rowCheckbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                        </td>
                        <td class="px-6 py-4 font-bold text-gray-900">{{ $reg->spmb_id }}</td>
                        <td class="px-6 py-4">{{ $reg->user->name ?? '-' }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs font-bold rounded {{ $reg->major == 'TJKT' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800' }}">
                                {{ $reg->major }}
                            </span>
                        </td>
                        <td class="px-6 py-4">{{ $reg->period->name ?? '-' }}</td>
                        <td class="px-6 py-4">
                            <a href="{{ route('admin.cetak-formulir.print', $reg->id) }}" target="_blank" class="text-white bg-gray-800 hover:bg-gray-900 px-3 py-1.5 rounded text-xs font-bold inline-flex items-center gap-1">
                                Cetak
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">Tidak ada data pendaftar.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>
    
    <div class="mt-4">
        {{ $registrations->links() }}
    </div>
</div>

<script>
    document.getElementById('selectAll').addEventListener('change', function() {
        let checkboxes = document.querySelectorAll('.rowCheckbox');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });
</script>
@endsection