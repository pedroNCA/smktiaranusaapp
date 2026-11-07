<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Formulir Pendaftaran</title>

    <style>
        @page {
            size: 210mm 330mm;
        }

        body {
            font-family: calibri, sans-serif;
            font-size: 12pt;
            color: #000;
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        td, th {
            padding: 1px 2px;
            vertical-align: middle;
            font-weight: normal;
        }

        .header {
            width: 100%;
            margin-bottom: 2mm;
        }

        /* LOGO DIPERKECIL */
        .logo {
            width: 18mm;
            height: 18mm;
            object-fit: contain;
        }

        .logo img {
            width: 18mm;
            height: 18mm;
            object-fit: contain;
        }

        .title {
            font-size: 20pt;
            font-weight: bold;
            text-align: center;
        }

        .program {
            font-size: 9pt;
            text-align: center;
            margin-top: 1mm;
        }

        .program-box {
            display: inline-block;
            border: 1px solid #000;
            background: #c5def2;
            padding: 1px 8px;
            min-width: 75mm;
            font-weight: bold;
        }

        .section {
            background: #bdd7ee;
            text-align: center;
            font-weight: bold;
            font-size: 11pt;
            padding: 2px 0;
            margin-top: 2mm;
        }

        .form-table {
            font-size: 11pt;
        }

        .form-table td {
            height: 4.2mm;
        }

        .label {
            width: 34%;
        }

        .colon {
            width: 2%;
            text-align: center;
        }

        .value {
            width: 64%;
        }

        .sub-label {
            padding-left: 5mm;
        }

        .line {
            border-bottom: 1px solid #000;
            min-height: 4mm;
        }

        .check {
            font-family: dejavusans, sans-serif;
            font-size: 11pt;
        }

        .document-table {
            width: 48%;
            font-size: 11pt;
            margin-top: 2mm;
        }

        .document-table th,
        .document-table td {
            border: 1px solid #000;
            padding: 1px 3px;
            height: 4mm;
        }

        .document-table th {
            text-align: center;
            font-weight: bold;
        }

        .center {
            text-align: center;
        }

        .signature {
            width: 48%;
            text-align: center;
            font-size: 11pt;
            margin-top: 5mm;
        }

        .signature-line {
            border-bottom: 1px dotted #000;
            height: 8mm;
            width: 55mm;
            margin: 0 auto 2mm auto;
        }

        .footer {
            font-size: 9pt;
            text-align: right;
            margin-top: 3mm;
        }

        .recommendation td {
            height: 5mm;
        }

        .no-break {
            page-break-inside: avoid;
        }
        
        /* Utility untuk satu baris rata kanan-kiri */
        .inline-table {
            width: 100%;
            margin: 0;
            padding: 0;
            border-collapse: collapse;
        }
        .inline-table td {
            padding: 0;
            height: auto;
        }
    </style>
</head>

<body>

@foreach($registrations as $registration)

@php
    $student = $registration->student;
    $address = $student?->address;

    $parents = $student?->parents ?? collect();

    $father = $parents->firstWhere('type', 'ayah');
    $mother = $parents->firstWhere('type', 'ibu');
    $guardian = $parents->firstWhere('type', 'wali');

    // Mencegah error jika documents belum di-pass ke view
    $documentsForRegistration = isset($documents) ? ($documents[$registration->id] ?? collect()) : collect();

    $hasDocument = function ($type) use ($documentsForRegistration) {
        return $documentsForRegistration->contains(function ($document) use ($type) {
            return $document->type === $type;
        });
    };

    $birthDate = $student?->birth_date
        ? \Carbon\Carbon::parse($student->birth_date)
        : null;
@endphp

{{-- HEADER FORMULIR --}}
<table class="header-form" width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td class="header-logo" style="width: 20%; text-align: center;">
            <img src="{{ public_path('logo.png') }}" class="logo" alt="Logo Tiara Nusa">
        </td>

        <td class="header-title" style="width: 80%;">
            <div class="title">
                FORMULIR PENDAFTARAN CALON PESERTA DIDIK
            </div>

            <table class="program-table" style="margin: 0 auto; width: 80%; margin-top: 5px;" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="width: 25%; text-align: right; font-size: 12pt; font-weight: bold; padding-right: 5px;">Program :</td>
                    <td style="width: 75%; text-align: left;">
                        <div class="program-box" style="background-color: {{ $registration->major == 'TJKT' ? '#BDD7EE' : '#F8CBAD' }}">
                            {{ $registration->major == 'TJKT' ? 'TEKNIK JARINGAN KOMPUTER DAN TELEKOMUNIKASI' : ($registration->major == 'MPLB' ? 'MANAJEMEN PERKANTORAN DAN LAYANAN BISNIS' : $registration->major) }}
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<div class="section" style="background-color: {{ $registration->major == 'TJKT' ? '#BDD7EE' : '#F8CBAD' }}">IDENTITAS PESERTA DIDIK (WAJIB DI ISI)</div>

<table class="form-table">
    <tr>
        <td class="label">a. Nama Lengkap</td>
        <td class="colon">:</td>
        <td class="value">{{ $registration->user?->name ?? '' }}</td>
    </tr>
    <tr>
        <td class="label">b. Jenis Kelamin</td>
        <td class="colon">:</td>
        <td class="value">
            <span class="check">{{ $student?->gender === 'L' ? '☑' : '☐' }}</span> Laki-laki
            &nbsp;&nbsp;
            <span class="check">{{ $student?->gender === 'P' ? '☑' : '☐' }}</span> Perempuan
        </td>
    </tr>
    <tr>
        <td class="label">c. NISN</td>
        <td class="colon">:</td>
        <td class="value">{{ $student?->nisn ?? '' }}</td>
    </tr>
    <tr>
        <td class="label">d. Nama Sekolah Asal</td>
        <td class="colon">:</td>
        <td class="value">{{ $student?->school_origin ?? '' }}</td>
    </tr>
    <tr>
        <td class="label">e. Tempat Lahir</td>
        <td class="colon">:</td>
        <td class="value">{{ $student?->birth_place ?? '' }}</td>
    </tr>
    <tr>
        <td class="label">f. Tanggal Lahir</td>
        <td class="colon">:</td>
        <td class="value">
            {{ $birthDate ? $birthDate->format('d / m / Y') : '' }}
        </td>
    </tr>
    <tr>
        <td class="label">g. Agama</td>
        <td class="colon">:</td>
        <td class="value">{{ $student?->religion ?? '' }}</td>
    </tr>
    <tr>
        <td class="label">h. Berkebutuhan Khusus</td>
        <td class="colon">:</td>
        <td class="value">
            <span class="check">{{ $student?->special_needs ? '☑' : '☐' }}</span> Ya
            &nbsp;&nbsp;
            <span class="check">{{ !$student?->special_needs ? '☑' : '☐' }}</span> Tidak
        </td>
    </tr>
    <tr>
        <td class="label">i. Anak ke-</td>
        <td class="colon">:</td>
        <td class="value">
            {{ $student?->child_number ?? '' }} dari
            {{ $student?->siblings_count ?? '' }} bersaudara
        </td>
    </tr>
    <tr>
        <td class="label">j. Alamat Tempat Tinggal</td>
        <td class="colon">:</td>
        <td class="value"></td>
    </tr>
    <tr>
        <td class="sub-label">- Dusun</td>
        <td class="colon">:</td>
        <td class="value">
            <table class="inline-table">
                <tr>
                    <td align="left">{{ $address?->street ?? '' }}</td>
                    <td align="right">RT: {{ $address?->rt ?? '' }} &nbsp;&nbsp; RW: {{ $address?->rw ?? '' }}</td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td class="sub-label">- Kelurahan / Desa</td>
        <td class="colon">:</td>
        <td class="value">
            <table class="inline-table">
                <tr>
                    <td align="left">{{ $address?->village ?? '' }}</td>
                    <td align="right">Kode Pos : {{ $address?->postal_code ?? '' }}</td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td class="sub-label">- Kecamatan</td>
        <td class="colon">:</td>
        <td class="value">{{ $address?->subdistrict ?? '' }}</td>
    </tr>
    <tr>
        <td class="sub-label">- Kabupaten / Kota</td>
        <td class="colon">:</td>
        <td class="value">{{ $address?->city ?? '' }}</td>
    </tr>
    <tr>
        <td class="sub-label">- Provinsi</td>
        <td class="colon">:</td>
        <td class="value">{{ $address?->province ?? '' }}</td>
    </tr>
    <tr>
        <td class="label">k. No Telepon</td>
        <td class="colon">:</td>
        <td class="value">
            <table class="inline-table">
                <tr>
                    <td align="left">{{ $student?->phone ?? '' }}</td>
                    <td align="right">No. WA : {{ $registration->user?->whatsapp ?? '' }}</td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td class="label">l. Email Pribadi</td>
        <td class="colon">:</td>
        <td class="value">{{ $registration->user?->email ?? '' }}</td>
    </tr>
    <tr>
        <td class="label">m. No. NIK</td>
        <td class="colon">:</td>
        <td class="value">{{ $student?->nik ?? '' }}</td>
    </tr>
</table>

<div class="section" style="background-color: {{ $registration->major == 'TJKT' ? '#BDD7EE' : '#F8CBAD' }}">DATA AYAH KANDUNG (WAJIB DIISI)</div>
<table class="form-table">
    <tr>
        <td class="label">n. Nama Ayah</td>
        <td class="colon">:</td>
        <td class="value">
            <table class="inline-table">
                <tr>
                    <td align="left">{{ $father?->name ?? '' }}</td>
                    <td align="right">Tahun Lahir : {{ $father?->birth_year ?? '' }}</td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td class="sub-label">- Berkebutuhan Khusus</td>
        <td class="colon">:</td>
        <td class="value">
            <span class="check">{{ $father?->special_needs ? '☑' : '☐' }}</span> Ya
            &nbsp;&nbsp;
            <span class="check">{{ $father && !$father->special_needs ? '☑' : '☐' }}</span> Tidak
        </td>
    </tr>
    <tr>
        <td class="sub-label">- Pekerjaan</td>
        <td class="colon">:</td>
        <td class="value">{{ $father?->occupation ?? '' }}</td>
    </tr>
    <tr>
        <td class="sub-label">- Pendidikan</td>
        <td class="colon">:</td>
        <td class="value">{{ $father?->education ?? '' }}</td>
    </tr>
    <tr>
        <td class="sub-label">- Penghasilan Bulanan</td>
        <td class="colon">:</td>
        <td class="value">{{ $father?->monthly_income ?? '' }}</td>
    </tr>
</table>

<div class="section" style="background-color: {{ $registration->major == 'TJKT' ? '#BDD7EE' : '#F8CBAD' }}">DATA IBU KANDUNG (WAJIB DIISI)</div>
<table class="form-table">
    <tr>
        <td class="label">o. Nama Ibu</td>
        <td class="colon">:</td>
        <td class="value">
            <table class="inline-table">
                <tr>
                    <td align="left">{{ $mother?->name ?? '' }}</td>
                    <td align="right">Tahun Lahir : {{ $mother?->birth_year ?? '' }}</td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td class="sub-label">- Berkebutuhan Khusus</td>
        <td class="colon">:</td>
        <td class="value">
            <span class="check">{{ $mother?->special_needs ? '☑' : '☐' }}</span> Ya
            &nbsp;&nbsp;
            <span class="check">{{ $mother && !$mother->special_needs ? '☑' : '☐' }}</span> Tidak
        </td>
    </tr>
    <tr>
        <td class="sub-label">- Pekerjaan</td>
        <td class="colon">:</td>
        <td class="value">{{ $mother?->occupation ?? '' }}</td>
    </tr>
    <tr>
        <td class="sub-label">- Pendidikan</td>
        <td class="colon">:</td>
        <td class="value">{{ $mother?->education ?? '' }}</td>
    </tr>
    <tr>
        <td class="sub-label">- Penghasilan Bulanan</td>
        <td class="colon">:</td>
        <td class="value">{{ $mother?->monthly_income ?? '' }}</td>
    </tr>
</table>

<div class="section" style="background-color: {{ $registration->major == 'TJKT' ? '#BDD7EE' : '#F8CBAD' }}">DATA WALI/PENANGGUNGJAWAB</div>
<table class="form-table">
    <tr>
        <td class="label">p. Nama Wali</td>
        <td class="colon">:</td>
        <td class="value">
            <table class="inline-table">
                <tr>
                    <td align="left">{{ $guardian?->name ?? '' }}</td>
                    <td align="right">Tahun Lahir : {{ $guardian?->birth_year ?? '' }}</td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td class="sub-label">- Pekerjaan</td>
        <td class="colon">:</td>
        <td class="value">{{ $guardian?->occupation ?? '' }}</td>
    </tr>
    <tr>
        <td class="sub-label">- Pendidikan</td>
        <td class="colon">:</td>
        <td class="value">{{ $guardian?->education ?? '' }}</td>
    </tr>
    <tr>
        <td class="sub-label">- Penghasilan</td>
        <td class="colon">:</td>
        <td class="value">{{ $guardian?->monthly_income ?? '' }}</td>
    </tr>
</table>

<div class="section" style="background-color: {{ $registration->major == 'TJKT' ? '#BDD7EE' : '#F8CBAD' }}">DATA PERIODIK (WAJIB DIISI)</div>
<table class="form-table">
    <tr>
        <td class="label">q. Waktu Tempuh Berangkat ke Sekolah</td>
        <td class="colon">:</td>
        <td class="value">{{ $student?->time_to_school ?? '' }} Menit</td>
    </tr>
    <tr>
        <td class="label">r. Jarak Tempat Tinggal ke Sekolah</td>
        <td class="colon">:</td>
        <td class="value">{{ $student?->distance_to_school ?? '' }} KM</td>
    </tr>
    <tr>
        <td class="label">s. Alat Transportasi ke Sekolah</td>
        <td class="colon">:</td>
        <td class="value">
            <span class="check">{{ $student?->transportation === 'Jalan Kaki' ? '☑' : '☐' }}</span> Jalan Kaki
            &nbsp;&nbsp;
            <span class="check">{{ $student?->transportation === 'Kendaraan Pribadi' ? '☑' : '☐' }}</span> Kendaraan Pribadi
            &nbsp;&nbsp;
            <span class="check">{{ $student?->transportation === 'Kendaraan Umum' ? '☑' : '☐' }}</span> Kendaraan Umum
        </td>
    </tr>
    <tr>
        <td class="label">t. Tinggi Badan</td>
        <td class="colon">:</td>
        <td class="value">{{ $student?->height ?? '' }} Cm</td>
    </tr>
    <tr>
        <td class="label">u. Berat Badan</td>
        <td class="colon">:</td>
        <td class="value">{{ $student?->weight ?? '' }} Kg</td>
    </tr>
</table>

<table class="form-table recommendation" style="margin-top: 3mm;">
    <tr>
        <td style="width:25%; font-weight:bold; font-size:12pt;">REKOMENDASI DARI</td>
        <td style="width:2%; text-align: center;">:</td>
        <td style="width:18%;">SISWA TINUS</td>
        <td style="width:2%; text-align: center;">:</td>
        <td style="width:33%; border: 0px solid #000; padding: 1px 4px;">{{ $registration->recommendation_name ?? '' }}</td>
        <td style="width:8%; text-align: right; padding-right: 2px;">Kelas:</td>
        <td style="width:12%; border: 0px solid #000; padding: 1px 4px;">{{ $registration->recommendation_class ?? '' }}</td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td>ALUMNI</td>
        <td style="text-align: center;">:</td>
        <td colspan="3" style="border: 0px solid #000; padding: 1px 4px;">{{ $registration->recommendation_alumni ?? '' }}</td>
    </tr>
</table>

<div class="no-break">
    <table style="width:100%; margin-top:4mm;">
        <tr>
            <td style="width:50%; vertical-align:top;">
                <div style="font-size:11pt; text-decoration: underline; margin-bottom: 1mm;">Kelengkapan Administrasi</div>

                <table class="document-table" style="width: 90%;">
                    <thead>
                        <tr>
                            <th style="width:10%;">No</th>
                            <th style="width:60%;">Berkas</th>
                            <th style="width:15%;">Ada</th>
                            <th style="width:15%;">Blm Ada</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="center">1</td>
                            <td>Fotokopi NISN</td>
                            <!-- Gunakan font 'dejavusans' yang merupakan bawaan mPDF untuk karakter spesial -->
                            <td class="center" style="font-family: dejavusans;">{{ $hasDocument('NISN') ? '✓' : '' }}</td>
                            <td class="center" style="font-family: dejavusans;">{{ $hasDocument('NISN') ? '' : '✓' }}</td>
                        </tr>
                        <tr>
                            <td class="center">2</td>
                            <td>Fotokopi Akta Lahir</td>
                            <td class="center" style="font-family: dejavusans;">{{ $hasDocument('Akta_Lahir') ? '✓' : '' }}</td>
                            <td class="center" style="font-family: dejavusans;">{{ $hasDocument('Akta_Lahir') ? '' : '✓' }}</td>
                        </tr>
                        <tr>
                            <td class="center">3</td>
                            <td>Fotokopi Kartu Keluarga</td>
                            <td class="center" style="font-family: dejavusans;">{{ $hasDocument('Kartu_Keluarga') ? '✓' : '' }}</td>
                            <td class="center" style="font-family: dejavusans;">{{ $hasDocument('Kartu_Keluarga') ? '' : '✓' }}</td>
                        </tr>
                        <tr>
                            <td class="center">4</td>
                            <td>Fotokopi SKL/Ijazah SMP</td>
                            <td class="center" style="font-family: dejavusans;">{{ $hasDocument('SKL_Ijazah') ? '✓' : '' }}</td>
                            <td class="center" style="font-family: dejavusans;">{{ $hasDocument('SKL_Ijazah') ? '' : '✓' }}</td>
                        </tr>
                    </tbody>
                </table>

                <div style="font-size: 8pt; margin-top: 1mm;">
                    *) Beri tanda <span style="font-family: dejavusans;">&#10003;</span> pada kolom
                </div>

            </td>

            <td style="width:50%; vertical-align:top; text-align:center;">
                <div style="font-size:12pt; margin-bottom: 20mm; margin-top: 2mm;">
                    ......................., ........................ / ............ 20........
                </div><br><br><br><br><br><br>

                <div style="border-bottom: 1px dashed #000; height: 1px; width: 65mm; margin: 0 auto 2mm auto;"></div>
                <div style="font-size:8pt;">
                    Tanda Tangan &amp; Nama Terang
                </div>
            </td>
        </tr>
    </table>
</div>

@if (!$loop->last)
    <pagebreak />
@endif

@endforeach

</body>
</html>