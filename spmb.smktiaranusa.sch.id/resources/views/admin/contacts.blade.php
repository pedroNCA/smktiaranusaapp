@extends('layouts.admin')

@section('title', 'Pengaturan Kontak - SPMB')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Pengaturan Kontak & Media Sosial</h1>
    <p class="text-gray-500 text-sm mt-1">Kelola informasi alamat, kontak, dan tautan yang tampil di halaman publik.</p>
</div>

<div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6 lg:p-8 max-w-4xl">
    <form action="{{ route('admin.contact.update') }}" method="POST">
        @csrf @method('PUT')
        
        <h2 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">Informasi Utama</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="md:col-span-2">
                <label class="block mb-2 text-sm font-medium text-gray-900">Alamat Lengkap</label>
                <textarea name="address" rows="3" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-3">{{ $contact->address }}</textarea>
            </div>
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-900">Telepon / WhatsApp</label>
                <input type="text" name="phone" value="{{ $contact->phone }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-3" placeholder="Contoh: 08123456789">
            </div>
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-900">Email Resmi</label>
                <input type="email" name="email" value="{{ $contact->email }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-3">
            </div>
        </div>

        <h2 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">Media Sosial & Lokasi</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-900">Link Instagram</label>
                <input type="url" name="instagram" value="{{ $contact->instagram }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-3" placeholder="https://instagram.com/...">
            </div>
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-900">Link Facebook</label>
                <input type="url" name="facebook" value="{{ $contact->facebook }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-3" placeholder="https://facebook.com/...">
            </div>
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-900">Link YouTube</label>
                <input type="url" name="youtube" value="{{ $contact->youtube }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-3" placeholder="https://youtube.com/...">
            </div>
            <div class="md:col-span-2">
                <label class="block mb-2 text-sm font-medium text-gray-900">Tautan Embed Google Maps (Src Iframe)</label>
                <input type="text" name="gmaps_link" value="{{ $contact->gmaps_link }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-3" placeholder="https://www.google.com/maps/embed?pb=...">
                <p class="text-xs text-gray-500 mt-1">Masukkan hanya URL dari atribut `src="..."` saat Anda meng-copy embed map dari Google Maps.</p>
            </div>
        </div>

        <button type="submit" class="text-white bg-blue-600 hover:bg-blue-700 font-bold rounded-xl text-sm px-8 py-3.5 shadow-md active:scale-95 transition-all">
            Simpan Pengaturan
        </button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    @if(session('success'))
        Swal.fire({ icon: 'success', title: 'Berhasil', text: '{{ session('success') }}', buttonsStyling: false, customClass: { popup: 'rounded-3xl', confirmButton: 'bg-green-500 hover:bg-green-600 text-white font-bold py-2.5 px-6 rounded-xl' }});
    @endif
</script>
@endsection