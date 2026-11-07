@extends('layouts.public')

@section('title', 'Bantuan FAQ - SPMB SMK Tiara Nusa')

@section('content')
<div class="max-w-screen-lg mx-auto py-16 px-4">
    <div class="text-center max-w-2xl mx-auto mb-12">
        <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight sm:text-4xl">Pertanyaan yang Sering Diajukan</h1>
        <p class="mt-4 text-lg text-gray-600">Temukan jawaban cepat seputar proses seleksi penerimaan murid baru.</p>
    </div>

    <!-- Accordion Flowbite dengan Transisi -->
    <div id="accordion-flush" data-accordion="collapse" data-active-classes="bg-white text-blue-600" data-inactive-classes="text-gray-500" class="space-y-4">
        @forelse($faqs as $index => $item)
        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden transition-all duration-200">
            <h2 id="accordion-flush-heading-{{ $index }}">
                <button type="button" class="flex items-center justify-between w-full p-6 font-medium text-left text-gray-900 bg-white hover:bg-gray-50 transition-colors duration-200" data-accordion-target="#accordion-flush-body-{{ $index }}" aria-expanded="false" aria-controls="accordion-flush-body-{{ $index }}">
                    <div class="flex items-center gap-3">
                        <span>{{ $item->question }}</span>
                        <!-- Label Kategori -->
                        @if($item->category == 'Orang Tua')
                            <span class="bg-blue-100 text-blue-800 text-[10px] font-bold px-2 py-0.5 rounded uppercase">Orang Tua</span>
                        @elseif($item->category == 'Alumni')
                            <span class="bg-green-100 text-green-800 text-[10px] font-bold px-2 py-0.5 rounded uppercase">Alumni</span>
                        @elseif($item->category == 'Calon Siswa')
                            <span class="bg-yellow-100 text-yellow-800 text-[10px] font-bold px-2 py-0.5 rounded uppercase">Calon Siswa</span>
                        @else
                            <span class="bg-gray-100 text-gray-800 text-[10px] font-bold px-2 py-0.5 rounded uppercase">Umum</span>
                        @endif
                    </div>
                    <svg data-accordion-icon class="w-4 h-4 shrink-0 transition-transform duration-200" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5 5 1 1 5"/>
                    </svg>
                </button>
            </h2>
            <div id="accordion-flush-body-{{ $index }}" class="hidden" aria-labelledby="accordion-flush-heading-{{ $index }}">
                <div class="p-6 border-t border-gray-100 text-gray-600 leading-relaxed bg-gray-50/50">
                    <p>{!! nl2br(e($item->answer)) !!}</p>
                </div>
            </div>
        </div>
        @empty
        <div class="text-center py-12 text-gray-500 bg-white rounded-3xl border border-gray-100">
            Belum ada data FAQ yang diatur oleh admin.
        </div>
        @endforelse
    </div>
</div>
@endsection