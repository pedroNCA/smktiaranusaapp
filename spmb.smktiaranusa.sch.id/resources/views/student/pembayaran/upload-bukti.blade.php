@extends('layouts.student')
@section('title', 'Upload Bukti Pembayaran - SPMB')

@section('content')
<div class="max-w-3xl mx-auto">
    
    <div class="mb-6 flex justify-between items-end">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Bukti Pembayaran Pendaftaran</h2>
            <p class="text-gray-600 mt-1">Unggah dan pantau status struk transfer Anda</p>
        </div>
        <a href="{{ route('student.pembayaran.informasi') }}" class="text-blue-600 hover:underline text-sm font-medium">Lihat Info Rekening</a>
    </div>

    <!-- Alert Success -->
    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-400 text-green-700 px-4 py-3 rounded-lg flex items-start gap-3">
            <svg class="w-6 h-6 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <div>
                <span class="block font-bold">Sukses!</span>
                <span class="block text-sm">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    <!-- Error Validation -->
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg text-red-700 text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        
        <!-- Status Pembayaran -->
        @php
            $status = $registration->status ?? 'WAITING_PAYMENT';
            $statusColor = 'bg-gray-100 text-gray-800';
            $statusText = 'Belum Ada Status';
            
            if(in_array($status, ['DRAFT', 'SUBMITTED', 'WAITING_PAYMENT'])) {
                $statusColor = 'bg-yellow-100 text-yellow-800 border border-yellow-200';
                $statusText = 'Menunggu Pembayaran';
            } elseif ($status == 'PAYMENT_REVIEW') {
                $statusColor = 'bg-blue-100 text-blue-800 border border-blue-200';
                $statusText = 'Menunggu Verifikasi Admin';
            } elseif (in_array($status, ['PAYMENT_APPROVED', 'VERIFIED', 'ACCEPTED'])) {
                $statusColor = 'bg-green-100 text-green-800 border border-green-200';
                $statusText = 'Pembayaran Lunas';
            } elseif ($status == 'PAYMENT_REJECTED') {
                $statusColor = 'bg-red-100 text-red-800 border border-red-200';
                $statusText = 'Pembayaran Ditolak (Upload Ulang)';
            }
        @endphp

        <div class="p-5 sm:p-6 border-b border-gray-200 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 bg-gray-50">
            <span class="font-medium text-gray-700">Status Pembayaran Anda:</span>
            <div class="inline-flex">
                <span class="px-4 py-2 rounded-full text-xs font-bold uppercase tracking-wider text-center shadow-sm {{ $statusColor }}">
                    {{ $statusText }}
                </span>
            </div>
        </div>

        <div class="p-6 md:p-8">
            <!-- LOGIKA YANG DIPERBAIKI: HANYA TAMPILKAN INFO JIKA STATUS SEDANG DIREVIEW ATAU SUDAH DITERIMA -->
            @if(in_array($status, ['PAYMENT_REVIEW', 'PAYMENT_APPROVED', 'VERIFIED', 'ACCEPTED']))
                <div class="text-center">
                    <div class="mb-6 bg-gray-50 border border-gray-200 rounded-xl p-6">
                        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3 text-blue-600">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <h3 class="font-bold text-gray-800 text-lg mb-1">Bukti Pembayaran Telah Diunggah</h3>
                        <p class="text-sm text-gray-600 mb-4">Anda dapat melihat kembali file foto/struk transfer yang telah dikirim</p>
                        
                        @if(!empty($registration->payment_proof))
                            <a href="{{ route('student.pembayaran.lihat-bukti-drive') }}" target="_blank" class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-sm px-6 py-3 transition shadow-sm w-full md:w-auto">
                                <i class="fas fa-external-link-alt"></i> Lihat File Bukti Pembayaran
                            </a>
                        @endif
                    </div>

                    @if($status == 'PAYMENT_REVIEW')
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-5 mb-6 text-left">
                            <h4 class="font-bold text-blue-800 mb-1">Menunggu Verifikasi Admin</h4>
                            <p class="text-sm text-blue-700">Bukti transfer Anda sudah kami terima dan sedang diperiksa. Silakan lakukan konfirmasi ke admin melalui WhatsApp di bawah ini agar segera diproses</p>
                        </div>

                        @php
                            $waNumber = '6285182565755';
                            $waText = urlencode('Halo Admin SPMB, saya ingin konfirmasi pembayaran pendaftaran atas nama ' . Auth::user()->name . '');
                            $waLink = "https://api.whatsapp.com/send?phone={$waNumber}&text={$waText}";
                        @endphp

                        <a href="{{ $waLink }}" target="_blank" class="inline-flex items-center justify-center gap-2 bg-green-500 hover:bg-green-600 text-white font-bold rounded-lg text-sm px-6 py-3 transition shadow-sm w-full md:w-auto">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M20.52 3.449a11.967 11.967 0 0 0-8.48-3.447C5.466.002 0 5.468 0 12.043c0 2.213.578 4.373 1.674 6.275L.014 24l5.856-1.536c1.83.985 3.89 1.503 6.166 1.503h.005c6.574 0 12.04-5.466 12.04-12.042a11.942 11.942 0 0 0-3.56-8.476zM12.04 21.996h-.004c-1.874 0-3.712-.504-5.32-1.458l-.382-.226-3.955 1.037 1.057-3.856-.248-.395A9.972 9.972 0 0 1 2.034 12.04C2.034 6.524 6.526 2.031 12.043 2.031a9.932 9.932 0 0 1 7.065 2.932 9.957 9.957 0 0 1 2.927 7.081c-.001 5.516-4.494 10.008-10.01 10.008zm5.495-7.513c-.301-.151-1.785-.88-2.062-.981-.277-.101-.479-.151-.68.151-.202.301-.78 1.002-.956 1.203-.176.201-.353.226-.654.075-1.507-.751-2.617-1.464-3.619-2.905-.28-.403.284-.372.873-1.547.075-.15.038-.276-.038-.427-.075-.15-.68-1.636-.931-2.241-.245-.589-.494-.509-.68-.518-.175-.008-.377-.01-.578-.01-.202 0-.528.075-.805.376-.277.301-1.056 1.03-1.056 2.508 0 1.478 1.082 2.908 1.233 3.109.151.201 2.118 3.233 5.132 4.536 1.954.845 2.766.915 3.757.771 1.134-.165 2.378-.971 2.713-1.91.336-.939.336-1.745.236-1.91-.101-.165-.378-.266-.68-.417z"/></svg>
                            Konfirmasi via WhatsApp
                        </a>
                    @elseif(in_array($status, ['PAYMENT_APPROVED', 'VERIFIED', 'ACCEPTED']))
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-green-800 font-medium">
                            Pembayaran Anda telah diverifikasi lunas oleh panitia
                        </div>
                    @endif
                </div>

            @else
                <!-- FORM UPLOAD AKAN MUNCUL JIKA STATUS MASIH 'WAITING_PAYMENT' ATAU 'PAYMENT_REJECTED' -->
                <form action="{{ route('student.pembayaran.upload-bukti.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="mb-6">
                        <label class="block mb-2 text-sm font-medium text-gray-900">Upload Foto/Screenshot Struk Transfer <span class="text-red-500">*</span></label>
                        
                        <div class="flex items-center justify-center w-full">
                            <label for="dropzone-file" class="flex flex-col items-center justify-center w-full h-64 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6 px-4 text-center">
                                    <svg class="w-10 h-10 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                    <p class="mb-2 text-sm text-gray-500 font-medium">Klik untuk memilih file foto bukti pembayaran</p>
                                    <p class="text-xs text-gray-400">Pastikan nominal dan tanggal terlihat jelas (JPEG, PNG, JPG Maks 2MB)</p>
                                </div>
                                <input id="dropzone-file" type="file" name="bukti_pembayaran" class="hidden" accept="image/png, image/jpeg, image/jpg" required />
                            </label>
                        </div>
                        
                        <p id="file-name" class="mt-2 text-sm text-blue-600 font-medium text-center"></p>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-sm px-5 py-3 transition">
                        Kirim Bukti Pembayaran
                    </button>
                </form>

                <script>
                    document.getElementById('dropzone-file').addEventListener('change', function(e) {
                        var fileName = e.target.files[0].name;
                        document.getElementById('file-name').textContent = 'File terpilih: ' + fileName;
                    });
                </script>
            @endif
        </div>
    </div>
</div>
@endsection