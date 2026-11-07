@extends('layouts.admin')

@section('title', 'Detail Pendaftar - SPMB Admin')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Detail Lengkap Pendaftar</h1>
        <p class="text-gray-500 text-sm mt-1">Informasi lengkap formulir pendaftaran peserta didik baru.</p>
    </div>
    <a href="{{ route('admin.pendaftar.index') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors shadow-sm">
        <i class="fas fa-arrow-left mr-2"></i> Kembali
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    {{-- Kolom Kiri & Tengah: Informasi Utama (2 Kolom) --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- Program / Pilihan Jurusan --}}
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-2xl p-6 text-white shadow-sm">
            <span class="text-xs uppercase tracking-wider font-semibold text-blue-200">Kompetensi Keahlian / Program</span>
            <h2 class="text-xl font-bold mt-1">
                {{ $registration->major ?? '-' }}
            </h2>
            <div class="mt-3 flex items-center gap-4 text-sm text-blue-100">
                <span><i class="far fa-id-card mr-1"></i> ID SPMB: <strong>{{ $registration->spmb_id }}</strong></span>
                <span><i class="far fa-calendar-alt mr-1"></i> Gelombang: <strong>{{ $registration->period->name ?? '-' }}</strong></span>
            </div>
        </div>

        {{-- A. Identitas Peserta Didik --}}
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6">
            <h3 class="text-base font-bold text-gray-900 border-b border-gray-100 pb-3 mb-4 flex items-center gap-2">
                <i class="fas fa-user-graduate text-blue-600"></i> A. Identitas Peserta Didik
            </h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-400 block text-xs">Nama Lengkap</span>
                    <strong class="text-gray-800 text-base">{{ $registration->student->user->name ?? ($registration->user->name ?? '-') }}</strong>
                </div>
                <div>
                    <span class="text-gray-400 block text-xs">Jenis Kelamin</span>
                    <span class="text-gray-800 font-medium">{{ $registration->student->gender == 'L' ? 'Laki-laki' : ($registration->student->gender == 'P' ? 'Perempuan' : '-') }}</span>
                </div>
                <div>
                    <span class="text-gray-400 block text-xs">NISN</span>
                    <span class="text-gray-800 font-medium">{{ $registration->student->nisn ?? '-' }}</span>
                </div>
                <div>
                    <span class="text-gray-400 block text-xs">NIK</span>
                    <span class="text-gray-800 font-medium">{{ $registration->student->nik ?? '-' }}</span>
                </div>
                <div>
                    <span class="text-gray-400 block text-xs">Tempat, Tanggal Lahir</span>
                    <span class="text-gray-800 font-medium">{{ $registration->student->birth_place ?? '-' }}, {{ $registration->student->birth_date ? date('d-m-Y', strtotime($registration->student->birth_date)) : '-' }}</span>
                </div>
                <div>
                    <span class="text-gray-400 block text-xs">Agama</span>
                    <span class="text-gray-800 font-medium">{{ $registration->student->religion ?? '-' }}</span>
                </div>
                <div>
                    <span class="text-gray-400 block text-xs">Asal Sekolah</span>
                    <span class="text-gray-800 font-medium">{{ $registration->student->school_origin ?? '-' }}</span>
                </div>
                <div>
                    <span class="text-gray-400 block text-xs">Berkebutuhan Khusus</span>
                    <span class="text-gray-800 font-medium">{{ $registration->student->special_needs ? 'Ya' : 'Tidak' }}</span>
                </div>
                <div>
                    <span class="text-gray-400 block text-xs">Anak ke- / Jumlah Bersaudara</span>
                    <span class="text-gray-800 font-medium">{{ $registration->student->child_number ?? '-' }} dari {{ $registration->student->siblings_count ?? '-' }} bersaudara</span>
                </div>
                
                {{-- No. WhatsApp dengan Tombol Disampingnya --}}
                <div>
                    <span class="text-gray-400 block text-xs">No. WhatsApp / HP</span>
                    @php
                        $rawWa = $registration->student->user->whatsapp ?? ($registration->user->whatsapp ?? null);
                        $wa = $rawWa;
                        if($wa && substr($wa, 0, 1) == '0') {
                            $wa = '62' . substr($wa, 1);
                        }
                    @endphp
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-gray-800 font-medium">{{ $rawWa ?? '-' }}</span>
                        @if($rawWa)
                            <a href="https://wa.me/{{ $wa }}" target="_blank" class="inline-flex items-center px-2.5 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded-lg hover:bg-green-200 transition-colors">
                                <i class="fab fa-whatsapp mr-1"></i> Chat WA
                            </a>
                        @endif
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <span class="text-gray-400 block text-xs">Email Pribadi</span>
                    <span class="text-gray-800 font-medium">{{ $registration->student->user->email ?? ($registration->user->email ?? '-') }}</span>
                </div>
            </div>

            {{-- Alamat Tempat Tinggal --}}
            <div class="mt-6 pt-4 border-t border-gray-100">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-2">Alamat Tempat Tinggal</span>
                <div class="bg-gray-50 p-4 rounded-xl text-sm text-gray-700 space-y-1">
                    @php $addr = $registration->student->address ?? null; @endphp
                    <p><strong>Dusun :</strong> {{ $addr->district ?? '-' }}</p>
                    <p><strong>RT/RW:</strong> {{ $addr->rt ?? '00' }} / {{ $addr->rw ?? '00' }} | <strong>Kelurahan/Desa:</strong> {{ $addr->village ?? '-' }}</p>
                    <p><strong>Kecamatan:</strong> {{ $addr->subdistrict ?? '-' }} | <strong>Kabupaten/Kota:</strong> {{ $addr->city ?? '-' }}</p>
                    <p><strong>Provinsi:</strong> {{ $addr->province ?? '-' }} | <strong>Kode Pos:</strong> {{ $addr->postal_code ?? '-' }}</p>
                </div>
            </div>
        </div>

        {{-- B. Data Orang Tua / Wali --}}
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6">
            <h3 class="text-base font-bold text-gray-900 border-b border-gray-100 pb-3 mb-4 flex items-center gap-2">
                <i class="fas fa-users text-blue-600"></i> B. Data Orang Tua & Wali
            </h3>

            @php 
                $father = $registration->student->parents->where('type', 'ayah')->first() ?? null;
                $mother = $registration->student->parents->where('type', 'ibu')->first() ?? null;
                $guardian = $registration->student->parents->where('type', 'wali')->first() ?? null;
            @endphp

            {{-- Ayah --}}
            <div class="mb-5 pb-4 border-b border-gray-100 text-sm">
                <h4 class="font-bold mb-2 text-xs uppercase tracking-wider text-blue-600">Data Ayah Kandung</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div><span class="text-gray-400 block text-xs">Nama Ayah</span> <span class="font-medium text-gray-800">{{ $father->name ?? '-' }}</span></div>
                    <div><span class="text-gray-400 block text-xs">Tahun Lahir</span> <span class="font-medium text-gray-800">{{ $father->birth_year ?? '-' }}</span></div>
                    <div><span class="text-gray-400 block text-xs">Pekerjaan</span> <span class="font-medium text-gray-800">{{ $father->occupation ?? '-' }}</span></div>
                    <div><span class="text-gray-400 block text-xs">Pendidikan</span> <span class="font-medium text-gray-800">{{ $father->education ?? '-' }}</span></div>
                    <div><span class="text-gray-400 block text-xs">Penghasilan Bulanan</span> <span class="font-medium text-gray-800">{{ $father->monthly_income ?? '-' }}</span></div>
                </div>
            </div>

            {{-- Ibu --}}
            <div class="mb-5 pb-4 border-b border-gray-100 text-sm">
                <h4 class="font-bold mb-2 text-xs uppercase tracking-wider text-blue-600">Data Ibu Kandung</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div><span class="text-gray-400 block text-xs">Nama Ibu</span> <span class="font-medium text-gray-800">{{ $mother->name ?? '-' }}</span></div>
                    <div><span class="text-gray-400 block text-xs">Tahun Lahir</span> <span class="font-medium text-gray-800">{{ $mother->birth_year ?? '-' }}</span></div>
                    <div><span class="text-gray-400 block text-xs">Pekerjaan</span> <span class="font-medium text-gray-800">{{ $mother->occupation ?? '-' }}</span></div>
                    <div><span class="text-gray-400 block text-xs">Pendidikan</span> <span class="font-medium text-gray-800">{{ $mother->education ?? '-' }}</span></div>
                    <div><span class="text-gray-400 block text-xs">Penghasilan Bulanan</span> <span class="font-medium text-gray-800">{{ $mother->monthly_income ?? '-' }}</span></div>
                </div>
            </div>

            {{-- Wali --}}
            @if($guardian)
            <div class="text-sm">
                <h4 class="font-bold mb-2 text-xs uppercase tracking-wider text-blue-600">Data Wali / Penanggung Jawab</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div><span class="text-gray-400 block text-xs">Nama Wali</span> <span class="font-medium text-gray-800">{{ $guardian->name ?? '-' }}</span></div>
                    <div><span class="text-gray-400 block text-xs">Tahun Lahir</span> <span class="font-medium text-gray-800">{{ $guardian->birth_year ?? '-' }}</span></div>
                    <div><span class="text-gray-400 block text-xs">Pekerjaan</span> <span class="font-medium text-gray-800">{{ $guardian->occupation ?? '-' }}</span></div>
                    <div><span class="text-gray-400 block text-xs">Pendidikan</span> <span class="font-medium text-gray-800">{{ $guardian->education ?? '-' }}</span></div>
                    <div><span class="text-gray-400 block text-xs">Penghasilan Bulanan</span> <span class="font-medium text-gray-800">{{ $guardian->monthly_income ?? '-' }}</span></div>
                </div>
            </div>
            @endif
        </div>

        {{-- C. Data Periodik & Rekomendasi --}}
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6">
            <h3 class="text-base font-bold text-gray-900 border-b border-gray-100 pb-3 mb-4 flex items-center gap-2">
                <i class="fas fa-chart-line text-blue-600"></i> C. Data Periodik & Rekomendasi
            </h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm mb-6">
                <div>
                    <span class="text-gray-400 block text-xs">Waktu Tempuh ke Sekolah</span>
                    <span class="text-gray-800 font-medium">{{ $registration->student->time_to_school ?? '-' }} Menit</span>
                </div>
                <div>
                    <span class="text-gray-400 block text-xs">Jarak Tempat Tinggal</span>
                    <span class="text-gray-800 font-medium">{{ $registration->student->distance_to_school ?? '-' }} KM</span>
                </div>
                <div>
                    <span class="text-gray-400 block text-xs">Alat Transportasi</span>
                    <span class="text-gray-800 font-medium">{{ $registration->student->transportation ?? '-' }}</span>
                </div>
                <div>
                    <span class="text-gray-400 block text-xs">Tinggi & Berat Badan</span>
                    <span class="text-gray-800 font-medium">
                        {{ $registration->student->height ? round($registration->student->height) : '-' }} Cm / 
                        {{ $registration->student->weight ? round($registration->student->weight) : '-' }} Kg
                    </span>
                </div>
            </div>

            <div class="pt-4 border-t border-gray-100 text-sm">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-2">Informasi Rekomendasi</span>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 bg-gray-50 p-4 rounded-xl">
                    <div><span class="text-gray-400 block text-xs">Status Rekomendasi</span> <span class="font-medium text-gray-800">{{ $registration->recommendation_status ?? '-' }}</span></div>
                    <div><span class="text-gray-400 block text-xs">Nama Rekomendasi</span> <span class="font-medium text-gray-800">{{ $registration->recommendation_name ?? '-' }}</span></div>
                    <div><span class="text-gray-400 block text-xs">Kelas / Keterangan</span> <span class="font-medium text-gray-800">{{ $registration->recommendation_class ?? '-' }}</span></div>
                </div>
            </div>
        </div>

    </div>

    {{-- Kolom Kanan: Status & Panel Aksi (1 Kolom) --}}
    <div class="space-y-6">

        {{-- Form Ubah Status Pendaftaran --}}
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6">
            <h3 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
                <i class="fas fa-sliders-h text-blue-600"></i> Status Pendaftaran Saat Ini
            </h3>

            <form action="{{ route('admin.pendaftar.updateStatus', $registration->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label for="status" class="block text-xs font-semibold text-gray-600 uppercase mb-2">Ubah Status Pendaftaran</label>
                    <select name="status" id="status" class="w-full text-sm border-gray-300 rounded-xl shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2.5 border">
                        <option value="DRAFT" {{ $registration->status == 'DRAFT' ? 'selected' : '' }}>Draf (Belum Selesai)</option>
                        <option value="SUBMITTED" {{ $registration->status == 'SUBMITTED' ? 'selected' : '' }}>Terkirim (Menunggu Proses)</option>
                        <option value="DOCUMENT_REVIEW" {{ $registration->status == 'DOCUMENT_REVIEW' ? 'selected' : '' }}>Pemeriksaan Berkas</option>
                        <option value="WAITING_PAYMENT" {{ $registration->status == 'WAITING_PAYMENT' ? 'selected' : '' }}>Menunggu Pembayaran</option>
                        <option value="PAYMENT_REVIEW" {{ $registration->status == 'PAYMENT_REVIEW' ? 'selected' : '' }}>Pemeriksaan Pembayaran</option>
                        <option value="VERIFIED" {{ $registration->status == 'VERIFIED' ? 'selected' : '' }}>Terverifikasi</option>
                        <option value="ACCEPTED" {{ $registration->status == 'ACCEPTED' ? 'selected' : '' }}>Diterima</option>
                    </select>
                </div>

                <button type="submit" class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm rounded-xl transition-all shadow-sm">
                    Perbarui Status
                </button>
            </form>
        </div>

        {{-- Status Pembayaran & Berkas (Dilengkapi Tombol Lihat Bukti & Lihat Berkas) --}}
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6">
            <h3 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
                <i class="fas fa-file-invoice-dollar text-blue-600"></i> Status Pembayaran & Berkas
            </h3>

            <div class="space-y-4 text-sm">
                {{-- Bagian Pembayaran --}}
                <div>
                    <span class="text-gray-400 block text-xs font-semibold uppercase mb-1">Riwayat Pembayaran:</span>
                    @if($registration->payments && $registration->payments->count() > 0)
                        @foreach($registration->payments as $payment)
                            <div class="mt-1 p-3 bg-gray-50 rounded-xl border border-gray-100">
                                
                                <p><strong>Status:</strong> <span class="font-bold {{ $payment->status == 'VERIFIED' ? 'text-green-600' : 'text-orange-600' }}">{{ $payment->status }}</span></p>
                                
                                {{-- Tombol Lihat Bukti Pembayaran --}}
                                @if($payment->proof_path || $registration->payment_proof)
                                    <div class="mt-2">
                                        <a href="{{ route('admin.pendaftaran.lihatBukti', $registration->id) }}" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                                            <i class="fas fa-eye mr-1.5"></i> Lihat Bukti Pembayaran
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <p class="text-gray-400 italic text-xs mt-1">Belum ada transaksi pembayaran</p>
                    @endif
                </div>

                {{-- Bagian Berkas Lampiran --}}
                <div class="pt-3 border-t border-gray-100">
                    <span class="text-gray-400 block text-xs font-semibold uppercase mb-2">Dokumen Lampiran Berkas:</span>
                    @if($registration->documents && $registration->documents->count() > 0)
                        <ul class="space-y-2">
                            @foreach($registration->documents as $doc)
                                <li class="text-xs flex justify-between items-center bg-gray-50 p-2.5 rounded-lg border border-gray-100">
                                    <span class="font-medium text-gray-700">{{ $doc->type ?? 'Dokumen Berkas' }}</span>
                                    <a href="{{ route('admin.verifikasi.berkas.dokumen.lihat', $doc->id) }}" target="_blank" class="inline-flex items-center px-2.5 py-1 text-xs font-semibold text-indigo-700 bg-indigo-50 rounded-md hover:bg-indigo-100 transition-colors">
                                        <i class="fas fa-file-alt mr-1"></i> Lihat Berkas
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-gray-400 italic text-xs">Belum ada berkas yang diunggah oleh siswa.</p>
                    @endif

{{-- Bagian Checklist Tahapan & Observasi --}}
<div class="pt-3 border-t border-gray-100">
    <span class="text-gray-400 block text-xs font-semibold uppercase mb-2">
        Tahapan & Observasi:
    </span>

    <div class="space-y-2">

        {{-- Checklist Formulir --}}
        <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg border border-gray-100 cursor-default">
            <input
                type="checkbox"
                disabled
                {{ $registration->check_formulir ? 'checked' : '' }}
                class="w-4 h-4 rounded text-blue-600 border-gray-300 opacity-100">

            <span class="text-sm font-medium text-gray-700">
                1. Formulir
            </span>
        </label>

        {{-- Checklist DP --}}
        <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg border border-gray-100 cursor-default">
            <input
                type="checkbox"
                disabled
                {{ $registration->check_dp ? 'checked' : '' }}
                class="w-4 h-4 rounded text-indigo-600 border-gray-300 opacity-100">

            <span class="text-sm font-medium text-gray-700">
                2. DP (Bayar)
            </span>
        </label>

        {{-- Checklist TPA --}}
@php
    $hasTpaResult = \App\Models\TpaResult::where(
        'registration_id',
        $registration->id
    )->exists();

    $isTpaDone = $registration->check_tpa || $hasTpaResult;
@endphp

<label class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg border border-gray-100 cursor-default">
    <input
        type="checkbox"
        disabled
        {{ $isTpaDone ? 'checked' : '' }}
        class="w-4 h-4 rounded text-green-600 border-gray-300 opacity-100">

    <span class="text-sm font-medium text-gray-700">
        3. TPA

        @if($hasTpaResult)
            <span class="text-xs text-green-600 font-semibold">
                (Selesai CBT)
            </span>
        @elseif($registration->check_tpa)
            <span class="text-xs text-gray-500">
                (Manual)
            </span>
        @endif
    </span>
</label>

        {{-- Checklist Observasi --}}
        <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg border border-gray-100 cursor-default">
            <input
                type="checkbox"
                disabled
                {{ $registration->check_observasi ? 'checked' : '' }}
                class="w-4 h-4 rounded text-orange-600 border-gray-300 opacity-100">

            <span class="text-sm font-medium text-gray-700">
                4. Observasi
            </span>
        </label>

    </div>
</div>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection