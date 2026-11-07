@extends('layouts.admin')
@section('title', 'Pengaturan CBT TPA')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 md:p-8">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Pengaturan CBT TPA</h2>
                <p class="text-sm text-gray-500">Atur ketersediaan server ujian dan batas waktu pengerjaan.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-green-100 text-green-700 p-4 rounded-lg text-sm border border-green-200 font-medium">{{ session('success') }}</div>
        @endif

        <form action="{{ route('admin.tpa.settings.update') }}" method="POST">
            @csrf
            
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-3">Status Ujian / Server CBT</label>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_active" class="sr-only peer" {{ $setting->is_active ? 'checked' : '' }}>
                    <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-blue-600"></div>
                    <span class="ml-3 text-sm font-medium {{ $setting->is_active ? 'text-blue-600 font-bold' : 'text-gray-500' }}">
                        {{ $setting->is_active ? 'CBT Terbuka (Siswa dapat mengerjakan)' : 'CBT Ditutup' }}
                    </span>
                </label>
            </div>

            <div class="mb-8">
                <label class="block text-sm font-bold text-gray-700 mb-1">Durasi Pengerjaan (Menit)</label>
                <p class="text-xs text-gray-500 mb-2">Lama waktu hitung mundur yang diberikan untuk seluruh soal.</p>
                <div class="relative w-48">
                    <input type="number" name="duration_minutes" value="{{ $setting->duration_minutes }}" required min="10" class="block w-full px-4 py-3 border border-gray-300 rounded-lg text-gray-900 bg-gray-50 focus:ring-blue-500 focus:border-blue-500 font-bold text-lg">
                    <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-gray-500">
                        Menit
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-200 pt-6">
                <button type="submit" class="w-full md:w-auto px-8 py-3 bg-gray-800 text-white font-bold rounded-lg hover:bg-gray-900 shadow-md transition">
                    Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection