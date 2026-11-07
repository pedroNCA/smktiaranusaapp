@extends('layouts.admin')
@section('title', 'Status Observasi & Dokumen')

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">

    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <h2 class="text-xl font-bold text-gray-800">Ceklis Tahapan & Observasi</h2>

        <form action="" method="GET" class="flex">
            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Cari Nama/ID SPMB..."
                class="border border-gray-300 rounded-l-lg px-4 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">

            <button type="submit"
                class="bg-blue-600 text-white px-4 py-2 rounded-r-lg text-sm hover:bg-blue-700">
                Cari
            </button>
        </form>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-100 text-green-700 p-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 bg-red-100 text-red-700 p-3 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="bg-gray-100 text-gray-700 uppercase text-xs">
                    <th class="p-3 border-b">ID SPMB / Nama</th>
                    <th class="p-3 border-b text-center">1. Formulir</th>
                    <th class="p-3 border-b text-center">2. DP (Bayar)</th>
                    <th class="p-3 border-b text-center">3. TPA</th>
                    <th class="p-3 border-b text-center">4. Observasi</th>
                    <th class="p-3 border-b text-center">Aksi</th>
                    <th class="p-3 border-b text-center">WhatsApp</th>
                </tr>
            </thead>

            <tbody>
                @forelse($registrations as $reg)
                    @php
                        // TPA selesai jika sudah ada hasil CBT
                        $hasTpaResult = in_array(
        $reg->id,
        $registrationIdsWithTpa ?? []
    );

    $isTpaDone = $reg->check_tpa || $hasTpaResult;

                        // Ambil nomor WhatsApp dari data user
                        $waNumber = preg_replace(
        '/[^0-9]/',
        '',
        (string) ($reg->user->whatsapp ?? '')
    );

    // Ubah nomor 08xxxx menjadi 628xxxx
    if (str_starts_with($waNumber, '0')) {
        $waNumber = '62' . substr($waNumber, 1);
    }

    // Jika nomor langsung dimulai dengan 8xxxx
    if (str_starts_with($waNumber, '8')) {
        $waNumber = '62' . $waNumber;
    }
                    @endphp

                    <tr class="hover:bg-gray-50 border-b">
                        <td class="p-3">
                            <div class="font-bold text-gray-900">
                                {{ $reg->user->name ?? '-' }}
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ $reg->spmb_id ?? '-' }}
                            </div>
                        </td>

                        <form
                            action="{{ route('admin.observasi.update-checklist', $reg->id) }}"
                            method="POST">
                            @csrf

                            {{-- Ceklis Formulir --}}
                            <td class="p-3 text-center">
                                <input
                                    type="checkbox"
                                    name="check_formulir"
                                    value="1"
                                    class="w-5 h-5 text-blue-600 rounded"
                                    {{ $reg->check_formulir ? 'checked' : '' }}>
                            </td>

                            {{-- Ceklis DP --}}
                            <td class="p-3 text-center">
                                <input
                                    type="checkbox"
                                    name="check_dp"
                                    value="1"
                                    class="w-5 h-5 text-indigo-600 rounded"
                                    {{ $reg->check_dp ? 'checked' : '' }}>
                            </td>

                            {{-- Ceklis TPA --}}
<td class="p-3 text-center">
    <input
        type="checkbox"
        name="check_tpa"
        value="1"
        class="w-5 h-5 text-green-600 rounded"
        {{ ($reg->check_tpa || $hasTpaResult) ? 'checked' : '' }}>

    @if($hasTpaResult)
        <div class="text-[10px] text-green-600 mt-1 font-bold">
            Otomatis (Selesai CBT)
        </div>
    @elseif($reg->check_tpa)
        <div class="text-[10px] text-gray-500 mt-1">
            Manual (Disetujui)
        </div>
    @endif
</td>

                            {{-- Ceklis Observasi --}}
                            <td class="p-3 text-center">
                                <input
                                    type="checkbox"
                                    name="check_observasi"
                                    value="1"
                                    class="w-5 h-5 text-orange-600 rounded"
                                    {{ $reg->check_observasi ? 'checked' : '' }}>
                            </td>

                            {{-- Tombol Update --}}
                            <td class="p-3 text-center">
                                <button
                                    type="submit"
                                    class="bg-gray-800 text-white px-3 py-1.5 rounded text-xs font-bold hover:bg-gray-900 transition">
                                    Update
                                </button>
                            </td>
                        </form>

                        {{-- Tombol WhatsApp --}}
                        <td class="p-3 text-center">
                            @if($waNumber)
                                <a
                                    href="https://wa.me/{{ $waNumber }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1 bg-green-100 hover:bg-green-200 text-green-700 px-3 py-2 rounded-lg text-xs font-bold">

                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="w-4 h-4"
                                        viewBox="0 0 24 24"
                                        fill="currentColor">
                                        <path d="M20.52 3.48A11.86 11.86 0 0012.04 0C5.5 0 .18 5.32.18 11.86c0 2.09.55 4.13 1.6 5.93L.08 24l6.37-1.67a11.9 11.9 0 005.59 1.42h.01c6.54 0 11.86-5.32 11.86-11.86a11.8 11.8 0 00-3.39-8.41zM12.05 21.7a9.84 9.84 0 01-5.02-1.37l-.36-.21-3.78.99 1.01-3.68-.24-.38a9.82 9.82 0 01-1.51-5.19c0-5.45 4.44-9.89 9.9-9.89a9.82 9.82 0 017 2.9 9.82 9.82 0 012.89 7c0 5.45-4.44 9.89-9.89 9.89zm5.42-7.4c-.3-.15-1.77-.87-2.04-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.47-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.07-.15-.67-1.62-.92-2.22-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.49s1.07 2.89 1.22 3.09c.15.2 2.1 3.21 5.09 4.5.71.31 1.27.49 1.7.63.71.23 1.36.2 1.87.12.57-.08 1.77-.72 2.02-1.42.25-.7.25-1.3.17-1.42-.07-.13-.27-.2-.57-.35z"/>
                                    </svg>

                                    WA
                                </a>
                            @else
                                <span class="text-xs text-gray-400">
                                    No. WA kosong
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-6 text-center text-gray-500">
                            Belum ada data pendaftaran.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $registrations->links() }}
    </div>
</div>
@endsection