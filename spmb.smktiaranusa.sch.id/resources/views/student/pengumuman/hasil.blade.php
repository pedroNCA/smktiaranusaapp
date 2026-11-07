@extends('layouts.student')
@section('title', 'Pengumuman Pendaftaran - SPMB SMK Tiara Nusa')

@section('content')
<div class="max-w-4xl mx-auto mt-6 mb-12">
    
    @php
        $status = $registration->status ?? 'DRAFT';
        
        // Mapping nama jurusan agar tidak singkatan (sesuaikan jika ada jurusan lain)
        $major = $registration->major ?? '-';
        $fullMajor = $major;
        if (strtoupper($major) == 'TJKT') {
            $fullMajor = 'Teknik Jaringan Komputer dan Telekomunikasi';
        } elseif (strtoupper($major) == 'MPLB') {
            $fullMajor = 'Manajemen Perkantoran dan Layanan Bisnis';
        }
    @endphp

    @if($status == 'ACCEPTED')
        <!-- TAMPILAN RESMI: SELESAI PENDAFTARAN (DIVERIFIKASI) -->
        <div class="bg-white rounded-xl shadow-2xl overflow-hidden border border-gray-200">
            {{-- Kop Surat / Header Formal --}}
            <!-- TAMPILAN RESMI: SELESAI PENDAFTARAN (DIVERIFIKASI) -->
        <div class="bg-white rounded-xl shadow-2xl overflow-hidden border border-gray-200">
            {{-- Kop Surat / Header Formal dengan Logo --}}
            <div class="bg-green-700 p-8 text-center border-b-8 border-green-500 relative">
                <div class="absolute top-0 left-0 w-full h-full opacity-10 bg-pattern"></div>
                <div class="relative z-10">
                    
                    {{-- Bingkai Logo --}}
                    <div class="w-24 h-24 bg-white rounded-full flex items-center justify-center mx-auto mb-5 shadow-xl border-4 border-green-600 p-2 relative z-20">
                        <img src="{{ asset('logo.png') }}" alt="Logo SMK Tiara Nusa" class="w-full h-full object-contain">
                    </div>
                    
                    <h2 class="text-2xl md:text-3xl font-bold text-white uppercase tracking-widest font-serif">Pemberitahuan</h2>
                    <p class="text-green-100 mt-1.5 font-medium tracking-wide">Penerimaan Peserta Didik Baru SMK Tiara Nusa</p>
                </div>
            </div>
            
            <div class="p-8 md:p-12 text-gray-800">
                <div class="text-right mb-6 text-sm text-gray-500">
                    Tanggal: {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}
                </div>
                
                <p class="mb-6 leading-relaxed">
                    Dengan hormat,<br>
                    Panitia Seleksi Penerimaan Peserta Didik Baru (SPMB) SMK Tiara Nusa menyatakan bahwa calon peserta didik dengan data berikut:
                </p>

                {{-- Kotak Data Diri Resmi --}}
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-6 shadow-inner">
                    <table class="w-full text-left border-collapse">
                        <tbody>
                            <tr>
                                <td class="py-2 w-1/3 text-gray-600 font-semibold align-top">Nomor Pendaftaran</td>
                                <td class="py-2 w-[5%] align-top">:</td>
                                <td class="py-2 font-bold text-gray-900 align-top">{{ $registration->spmb_id ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="py-2 w-1/3 text-gray-600 font-semibold align-top">Nama Lengkap</td>
                                <td class="py-2 w-[5%] align-top">:</td>
                                <td class="py-2 font-bold text-gray-900 align-top">{{ Auth::user()->name }}</td>
                            </tr>
                            <tr>
                                <td class="py-2 w-1/3 text-gray-600 font-semibold align-top">Program Keahlian</td>
                                <td class="py-2 w-[5%] align-top">:</td>
                                <td class="py-2 font-bold text-green-700 align-top">{{ $fullMajor }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p class="mb-6 leading-relaxed text-justify">
                    Telah <strong class="text-green-700">LOLOS VERIFIKASI ADMINISTRASI</strong>. Seluruh berkas pendaftaran dan bukti pembayaran administrasi awal Anda telah kami terima dan disahkan secara resmi oleh panitia
                </p>

                {{-- Agenda / Tahap Selanjutnya --}}
                <div class="mt-8 mb-8 border-l-4 border-green-600 pl-5">
                    <h4 class="font-bold text-gray-900 text-lg mb-4">Agenda & Tahapan Selanjutnya:</h4>
                    <ol class="list-decimal list-inside space-y-3 text-gray-700 marker:font-bold marker:text-green-600">
                        <li>
                            <span class="font-semibold text-gray-900">Pembayaran *Down Payment* (DP)</span>
                            <p class="ml-5 mt-1 text-sm text-gray-600">Penyelesaian pembayaran awal (DP) sebagai konfirmasi dan tanda jadi peserta didik baru SMK Tiara Nusa</p>
                        </li>
                        <li>
                            <span class="font-semibold text-gray-900">Tes Potensi Akademik (TPA)</span>
                            <p class="ml-5 mt-1 text-sm text-gray-600">Mengikuti uji pemetaan kemampuan akademik dasar yang akan diselenggarakan sesuai jadwal dari panitia</p>
                        </li>
                        <li>
                            <span class="font-semibold text-gray-900">Observasi Calon Siswa & Orang Tua</span>
                            <p class="ml-5 mt-1 text-sm text-gray-600">Sesi wawancara dan observasi langsung yang <span class="font-semibold text-red-600">wajib</span> dihadiri oleh calon siswa didampingi oleh orang tua/wali</p>
                        </li>
                    </ol>
                </div>

                <p class="mb-8 leading-relaxed text-sm text-gray-500">
                    Demikian pemberitahuan ini kami sampaikan. Atas perhatian dan kerja samanya, kami ucapkan terima kasih.
                </p>

                {{-- Action Buttons --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-gray-200 pt-8">
                    {{-- Ganti URL di href dengan Link Grup WA yang sebenarnya --}}
                    <a href="https://chat.whatsapp.com/ID_GRUP_WA_SEKOLAH_ANDA" target="_blank" class="flex flex-col items-center justify-center p-4 bg-green-50 text-green-700 font-bold rounded-xl border border-green-200 hover:bg-green-600 hover:text-white transition-all group">
                        <i class="fab fa-whatsapp text-3xl mb-2 text-green-500 group-hover:text-white transition-colors"></i>
                        <span class="text-sm">Masuk Grup WA Siswa Baru</span>
                    </a>

                    {{-- Tombol Bantuan WA Panitia --}}
                    <a href="https://wa.me/6285182565755" target="_blank" class="flex flex-col items-center justify-center p-4 bg-gray-50 text-gray-700 font-bold rounded-xl border border-gray-200 hover:bg-gray-100 transition-all">
                        <i class="fas fa-headset text-3xl mb-2 text-gray-400"></i>
                        <span class="text-sm">Tanya Panitia (WA)</span>
                    </a>
                </div>

            </div>
        </div>

    @elseif($status == 'NOT_ACCEPTED')
        <!-- TAMPILAN RESMI: DITOLAK -->
        <div class="bg-white rounded-xl shadow-2xl overflow-hidden border border-gray-200">
            <div class="bg-red-700 p-8 text-center border-b-8 border-red-500">
                <div class="w-24 h-24 bg-white rounded-full flex items-center justify-center mx-auto mb-5 shadow-xl border-4 border-green-600 p-2 relative z-20">
                        <img src="{{ asset('logo.png') }}" alt="Logo SMK Tiara Nusa" class="w-full h-full object-contain">
                    </div>
                <h2 class="text-2xl font-bold text-white uppercase tracking-widest font-serif">Pemberitahuan</h2>
            </div>
            
            <div class="p-8 md:p-12 text-gray-800 text-center">
                <p class="text-lg mb-4">Mohon maaf <strong>{{ Auth::user()->name }}</strong> ({{ $registration->spmb_id ?? '-' }}),</p>
                <p class="mb-6 leading-relaxed">
                    Berkas pendaftaran Anda dinyatakan <strong class="text-red-600">TIDAK MEMENUHI SYARAT</strong> secara administrasi oleh panitia SPMB
                </p>
                
                <div class="mt-8 flex justify-center">
                    <a href="https://wa.me/6285182565755" target="_blank" class="inline-flex items-center px-6 py-3 bg-gray-100 text-gray-700 font-bold rounded-lg border border-gray-300 hover:bg-gray-200 transition-all">
                        <i class="fas fa-headset mr-2"></i> Hubungi Panitia (WA)
                    </a>
                </div>
            </div>
        </div>

    @else
        <!-- TAMPILAN RESMI: MENUNGGU VERIFIKASI -->
        <div class="bg-white rounded-xl shadow-2xl overflow-hidden border border-gray-200 text-center py-16 px-8">
            <div class="w-24 h-24 bg-white rounded-full flex items-center justify-center mx-auto mb-5 shadow-xl border-4 border-green-600 p-2 relative z-20">
                        <img src="{{ asset('logo.png') }}" alt="Logo SMK Tiara Nusa" class="w-full h-full object-contain">
                    </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-3 font-serif">Verifikasi Sedang Berlangsung</h3>
            <p class="text-gray-600 max-w-lg mx-auto leading-relaxed">
                Panitia saat ini sedang melakukan validasi terhadap dokumen pendaftaran dan bukti pembayaran Anda. Hasil administrasi akan segera diumumkan melalui halaman ini
            </p>
            
            <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('student.dashboard') }}" class="px-6 py-3 bg-gray-800 text-white font-bold rounded-lg hover:bg-gray-900 transition-all">
                    <i class="fas fa-arrow-left mr-2"></i> Ke Dashboard
                </a>
                <a href="https://wa.me/6285182565755" target="_blank" class="px-6 py-3 bg-white text-green-700 border border-green-600 font-bold rounded-lg hover:bg-green-50 transition-all">
                    <i class="fab fa-whatsapp mr-2"></i> Hubungi Admin SPMB
                </a>
            </div>
        </div>
    @endif

</div>
@endsection