@extends('layouts.admin')

@section('content')
<!-- Kita hanya gunakan Alpine untuk modal tambah di div utama -->
<div x-data="{ showAddModal: false }">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Pengaturan Gelombang Pendaftaran</h2>
        <button @click="showAddModal = true" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            + Tambah Gelombang
        </button>
    </div>

    @if(session('success'))
        <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50" role="alert">
            {{ session('success') }}
        </div>
    @endif

    <!-- Tabel Data -->
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">Nama Gelombang</th>
                    <th scope="col" class="px-6 py-3">Tanggal Mulai</th>
                    <th scope="col" class="px-6 py-3">Tanggal Selesai</th>
                    <th scope="col" class="px-6 py-3">Status</th>
                    <th scope="col" class="px-6 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($periods as $period)
               @php
    $today = now()->toDateString();
    $start = \Carbon\Carbon::parse($period->start_date);
    $end = \Carbon\Carbon::parse($period->end_date);

    $isActive = $today >= $start->toDateString()
        && $today <= $end->toDateString();

    $isUpcoming = $today < $start->toDateString();
@endphp
                
                <!-- Alpine data scope khusus untuk baris ini saja -->
                <tr x-data="{ showRowEdit: false }" class="bg-white border-b hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium text-gray-900">{{ $period->name }}</td>
                    <td class="px-6 py-4">{{ $start->format('d M Y') }}</td>
                    <td class="px-6 py-4">{{ $end->format('d M Y') }}</td>
                    <td class="px-6 py-4">
    @if($isActive)
        <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-1 rounded border border-green-400">
            Sedang Aktif
        </span>
    @elseif($isUpcoming)
        <span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-1 rounded border border-yellow-400">
            Akan Datang
        </span>
    @else
        <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-1 rounded border border-gray-300">
            Telah Berakhir
        </span>
    @endif
</td>
                    <td class="px-6 py-4 flex space-x-2 relative">
                        <button type="button" @click="showRowEdit = true" class="font-medium text-blue-600 hover:underline">Edit</button>
                        
                        <form action="{{ route('registration-periods.destroy', $period->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus gelombang ini?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="font-medium text-red-600 hover:underline">Hapus</button>
                        </form>

                        <!-- PERBAIKAN: Tambahkan style="display: none;" pada Modal Edit -->
                        <div x-show="showRowEdit" style="display: none;" class="fixed inset-0 z-50 flex justify-center items-center bg-black bg-opacity-50" x-cloak>
                            <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6" @click.away="showRowEdit = false">
                                <h3 class="text-xl font-semibold mb-4 text-gray-900">Edit Gelombang</h3>
                                
                                <form action="{{ route('registration-periods.update', $period->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700">Nama Gelombang</label>
                                        <input type="text" name="name" value="{{ $period->name }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                    </div>
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700">Tanggal Mulai</label>
                                        <input type="date" name="start_date" value="{{ \Carbon\Carbon::parse($period->start_date)->format('Y-m-d') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                    </div>
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700">Tanggal Selesai</label>
                                        <input type="date" name="end_date" value="{{ \Carbon\Carbon::parse($period->end_date)->format('Y-m-d') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                    </div>
                                    <div class="flex justify-end space-x-2 mt-6">
                                        <button type="button" @click="showRowEdit = false" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-lg">Batal</button>
                                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- PERBAIKAN: Tambahkan style="display: none;" pada Modal Tambah -->
    <div x-show="showAddModal" style="display: none;" class="fixed inset-0 z-50 flex justify-center items-center bg-black bg-opacity-50" x-cloak>
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6" @click.away="showAddModal = false">
            <h3 class="text-xl font-semibold mb-4 text-gray-900">Tambah Gelombang Baru</h3>
            
            <form action="{{ route('registration-periods.store') }}" method="POST" @submit="showAddModal = false">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Nama Gelombang</label>
                    <input type="text" name="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Tanggal Mulai</label>
                    <input type="date" name="start_date" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Tanggal Selesai</label>
                    <input type="date" name="end_date" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div class="flex justify-end space-x-2 mt-6">
                    <button type="button" @click="showAddModal = false" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-lg">Batal</button>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection