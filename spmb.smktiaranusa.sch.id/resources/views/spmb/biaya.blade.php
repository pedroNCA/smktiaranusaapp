@extends('layouts.public')

@section('title', 'Informasi Biaya - SPMB SMK Tiara Nusa')

@section('content')
<div class="max-w-screen-lg mx-auto py-16 px-4 transition-all duration-500">
    <!-- Header Bagian Atas -->
    <div class="text-center max-w-2xl mx-auto mb-12">
        <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight sm:text-4xl">Rincian Biaya Pendaftaran</h1>
        <p class="mt-4 text-lg text-gray-600">Transparansi biaya pendidikan untuk gelombang pendaftaran tahun ajaran baru.</p>
    </div>

    <!-- Tabel Rincian Biaya dengan Efek Transisi -->
    <div class="bg-white border border-gray-100 rounded-3xl shadow-lg overflow-hidden transition-all duration-300 hover:shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-600">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50/80 border-b border-gray-100">
                    <tr>
                        <th scope="col" class="px-6 py-4 font-bold tracking-wider">Komponen Biaya</th>
                        <th scope="col" class="px-6 py-4 font-bold tracking-wider">Keterangan</th>
                        <th scope="col" class="px-6 py-4 font-bold tracking-wider text-right">Nominal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @php $totalBiaya = 0; @endphp
                    @forelse($costs as $index => $item)
                    @php $totalBiaya += $item->amount; @endphp
                    <tr class="hover:bg-blue-50/30 transition-all duration-200 group">
                        <td class="px-6 py-4 font-bold text-gray-900 group-hover:text-blue-600 transition-colors">
                            <div class="flex items-center gap-3">
                                <span class="w-7 h-7 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-xs font-semibold shrink-0">
                                    {{ $index + 1 }}
                                </span>
                                {{ $item->name }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-500">
                            {{ $item->description ?? 'Tidak ada keterangan tambahan.' }}
                        </td>
                        <td class="px-6 py-4 font-extrabold text-blue-600 text-right whitespace-nowrap">
                            Rp {{ number_format($item->amount, 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="px-6 py-12 text-center text-gray-400 italic">
                            Informasi biaya belum diatur oleh admin.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                
                @if($costs->isNotEmpty())
                <!-- Bagian Total Keseluruhan (Grand Total) -->
                <tfoot class="bg-gray-50/80 border-t-2 border-gray-200">
                    <tr>
                        <th colspan="2" class="px-6 py-4 font-extrabold text-gray-900 uppercase text-sm tracking-wider text-left">
                            Total Keseluruhan Biaya
                        </th>
                        <th class="px-6 py-4 font-extrabold text-blue-700 text-lg text-right whitespace-nowrap">
                            Rp {{ number_format($totalBiaya, 0, ',', '.') }}
                        </th>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    <!-- Tombol Aksi Pendaftaran di Bawah Tabel -->
    @if($costs->isNotEmpty())
    <div class="mt-10 text-center transition-all duration-300">
        <a href="{{ route('register') }}" class="inline-flex items-center justify-center py-3.5 px-8 text-base font-bold text-white bg-blue-600 rounded-2xl hover:bg-blue-700 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95 gap-2">
            <span>Daftar Sekarang</span>
            <svg class="w-5 h-5 transition-transform duration-200 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
        </a>
    </div>
    @endif
</div>
@endsection