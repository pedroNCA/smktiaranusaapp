@extends('layouts.student')
@section('title', 'Informasi Pembayaran - SPMB')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Informasi Pembayaran Formulir</h2>
        <p class="text-gray-600 mt-1">Panduan transfer biaya formulir SPMB SMK Tiara Nusa</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <!-- Header Status -->
        <div class="bg-blue-600 px-6 py-4 flex justify-between items-center text-white">
            <div>
                <h3 class="font-bold text-lg">Biaya Formulir SPMB</h3>
                <p class="text-blue-100 text-sm">Tahun Ajaran 2026/2027</p>
            </div>
            <div class="text-right">
                <span class="block text-sm text-blue-100 mb-1">Total Tagihan</span>
                <span class="font-bold text-2xl">Rp 150.000</span>
            </div>
        </div>

        <div class="p-6 md:p-8">
            <h4 class="font-bold text-gray-900 mb-4 text-lg border-b pb-2">Metode Pembayaran (Transfer Bank)</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Bank BSI -->
                <div class="border border-gray-200 rounded-lg p-4 flex items-start gap-4 bg-gray-50">
                    <div class="w-16 h-12 bg-white rounded border border-gray-300 flex items-center justify-center font-bold text-teal-700 shrink-0">
                        BSI
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Bank Syariah Indonesia (BSI)</p>
                        <p class="font-bold text-xl text-gray-900 tracking-wider">7203140554</p>
                        <p class="text-sm text-gray-700 font-medium">a.n Yayasan Tiara Utama</p>
                    </div>
                </div>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-5">
                <h4 class="font-bold text-yellow-800 mb-2 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Instruksi Pembayaran
                </h4>
                <ul class="list-decimal list-inside text-sm text-yellow-700 space-y-2">
                    <li>Pastikan Anda mentransfer tepat sesuai dengan <strong>Total Tagihan</strong>.</li>
                    <li>Simpan struk / bukti transfer (baik dari ATM, Mobile Banking, maupun Internet Banking).</li>
                    <li>Foto atau screenshot bukti transfer tersebut dengan jelas.</li>
                    <li>Lanjutkan ke menu <strong>Upload Bukti Pembayaran</strong> untuk mengunggah foto struk Anda.</li>
                </ul>
            </div>

            <div class="mt-8 flex justify-end">
                <a href="{{ route('student.pembayaran.upload-bukti') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg text-sm px-6 py-3 transition flex items-center gap-2">
                    Lanjut Upload Bukti Pembayaran &rarr;
                </a>
            </div>
        </div>
    </div>
</div>
@endsection