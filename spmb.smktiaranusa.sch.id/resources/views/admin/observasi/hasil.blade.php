```blade
@extends('layouts.admin')
@section('title', 'Hasil Ujian CBT TPA')

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">

    {{-- Notifikasi --}}
    @if(session('success'))
        <div class="mb-4 bg-green-100 text-green-700 p-3 rounded-lg text-sm border border-green-200">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 bg-red-100 text-red-700 p-3 rounded-lg text-sm border border-red-200">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 bg-red-100 text-red-700 p-3 rounded-lg text-sm border border-red-200">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Hasil Tes Potensi Akademik</h2>
            <p class="text-sm text-gray-500">Rekapitulasi nilai akhir peserta ujian.</p>
        </div>

        <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">

            {{-- Pencarian --}}
            <form action="{{ url()->current() }}" method="GET" class="flex w-full">
                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Cari nama atau ID SPMB..."
                    class="border border-gray-300 rounded-l-lg px-4 py-2 text-sm focus:ring-blue-500 w-full md:w-64"
                >

                <button type="submit"
                    class="bg-blue-600 text-white px-4 py-2 rounded-r-lg text-sm hover:bg-blue-700 font-bold">
                    Cari
                </button>
            </form>

            {{-- Export Excel --}}
            <a href="{{ route('admin.tpa.hasil.export', request()->only('search')) }}"
                class="inline-flex items-center justify-center gap-2 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-bold whitespace-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z"/>
                </svg>
                Export Excel
            </a>
        </div>
    </div>

    {{-- Form hapus massal --}}
    <form id="bulkDeleteForm"
        action="{{ route('admin.tpa.hasil.bulk-delete') }}"
        method="POST"
        onsubmit="return confirm('Yakin ingin menghapus semua hasil ujian yang dipilih? Data yang dihapus tidak dapat dikembalikan.');">
        @csrf
        @method('DELETE')
    </form>

    {{-- Tombol hapus terpilih --}}
    <div class="mb-4 flex flex-col sm:flex-row items-start sm:items-center gap-3">
        <button
            type="submit"
            form="bulkDeleteForm"
            id="btnBulkDelete"
            disabled
            class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white px-4 py-2 rounded-lg text-sm font-bold">
            <svg xmlns="http://www.w3.org/2000/svg"
                class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16"/>
            </svg>
            Hapus Terpilih (<span id="selectedCount">0</span>)
        </button>

        <span class="text-xs text-gray-500">
            Centang data yang ingin dihapus.
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="bg-gray-100 text-gray-700 uppercase text-xs">
                    <th class="p-4 border-b text-center">
                        <input
                            type="checkbox"
                            id="selectAll"
                            class="w-4 h-4 rounded border-gray-300 text-blue-600 cursor-pointer"
                            title="Pilih semua">
                    </th>
                    <th class="p-4 border-b">ID SPMB / Nama</th>
                    <th class="p-4 border-b text-center">Matematika</th>
                    <th class="p-4 border-b text-center">B. Indo</th>
                    <th class="p-4 border-b text-center">B. Inggris</th>
                    <th class="p-4 border-b text-center">Total Skor</th>
                    <th class="p-4 border-b">Status</th>
                    <th class="p-4 border-b">Tgl Mengerjakan</th>
                    <th class="p-4 border-b text-center">Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse($results as $res)
                <tr class="hover:bg-gray-50 border-b">

                    {{-- Checkbox per data --}}
                    <td class="p-4 text-center">
                        <input
                            type="checkbox"
                            name="ids[]"
                            value="{{ $res->id }}"
                            form="bulkDeleteForm"
                            class="result-checkbox w-4 h-4 rounded border-gray-300 text-blue-600 cursor-pointer">
                    </td>

                    <td class="p-4">
                        <div class="font-bold text-gray-900">
                            {{ $res->registration->user->name ?? '-' }}
                        </div>
                        <div class="text-xs text-gray-500">
                            {{ $res->registration->spmb_id ?? '-' }}
                        </div>
                    </td>

                    <td class="p-4 text-center text-gray-700 font-medium">
                        {{ $res->score_matematika }}
                    </td>

                    <td class="p-4 text-center text-gray-700 font-medium">
                        {{ $res->score_b_indonesia }}
                    </td>

                    <td class="p-4 text-center text-gray-700 font-medium">
                        {{ $res->score_b_inggris }}
                    </td>

                    <td class="p-4 text-center">
                        <span class="bg-blue-100 text-blue-800 font-bold px-3 py-1 rounded-full">
                            {{ $res->total_score }}
                        </span>
                    </td>

                    <td class="p-4 font-bold {{ $res->status_kelulusan_tpa == 'Lulus TPA' ? 'text-green-600' : 'text-red-600' }}">
                        {{ $res->status_kelulusan_tpa }}
                    </td>

                    <td class="p-4 text-xs text-gray-500 whitespace-nowrap">
                        {{ \Carbon\Carbon::parse($res->submitted_at)->format('d M Y, H:i') }}
                    </td>

                    {{-- Hapus satu data --}}
                    <td class="p-4 text-center">
                        <form
                            action="{{ route('admin.tpa.hasil.destroy', $res->id) }}"
                            method="POST"
                            onsubmit="return confirm('Yakin ingin menghapus hasil ujian peserta ini? Data yang dihapus tidak dapat dikembalikan.');">
                            @csrf
                            @method('DELETE')

                            <button type="submit"
                                class="inline-flex items-center gap-1 bg-red-100 hover:bg-red-200 text-red-700 px-3 py-2 rounded-lg text-xs font-bold">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16"/>
                                </svg>
                                Hapus
                            </button>
                        </form>
                    </td>
                </tr>

                @empty
                <tr>
                    <td colspan="9" class="p-6 text-center text-gray-500">
                        Belum ada siswa yang menyelesaikan ujian CBT.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $results->links() }}
    </div>
</div>

{{-- Script checkbox --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.result-checkbox');
    const btnBulkDelete = document.getElementById('btnBulkDelete');
    const selectedCount = document.getElementById('selectedCount');

    function updateSelectedCount() {
        const checked = document.querySelectorAll('.result-checkbox:checked').length;

        selectedCount.textContent = checked;
        btnBulkDelete.disabled = checked === 0;

        selectAll.checked = checkboxes.length > 0 && checked === checkboxes.length;
        selectAll.indeterminate = checked > 0 && checked < checkboxes.length;
    }

    selectAll.addEventListener('change', function () {
        checkboxes.forEach(function (checkbox) {
            checkbox.checked = selectAll.checked;
        });

        updateSelectedCount();
    });

    checkboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', updateSelectedCount);
    });

    updateSelectedCount();
});
</script>
@endsection