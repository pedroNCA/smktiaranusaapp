<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Registration;
use App\Models\Document;
use App\Models\RegistrationPeriod;
use App\Models\Student;
use App\Models\StudentAddress;
use App\Models\StudentParent;
use App\Models\RegistrationCost;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();

        // Ambil data murid untuk ditampilkan di dashboard
        $student = Student::where('user_id', $user->id)->first();
        $registration = Registration::where('user_id', $user->id)->first();

        return view('student.dashboard', compact('user', 'student', 'registration'));
    }

    public function dataPendaftaran()
    {
        $user = Auth::user();
        $registration = Registration::where('user_id', $user->id)->first();
        $period = RegistrationPeriod::where('is_active', 1)->first();

        return view('student.formulir.data-pendaftaran', compact('registration', 'period'));
    }

    public function storePendaftaran(Request $request)
    {
        $request->validate([
            'jalur_masuk'        => 'required',
            'pilihan_jurusan'    => 'required',
            'rekomendasi_status' => 'required',
            'rekomendasi_nama'   => 'required_if:rekomendasi_status,Alumni,Siswa Aktif',
            'rekomendasi_kelas'  => 'required_if:rekomendasi_status,Siswa Aktif',
        ], [
            'jalur_masuk.required'        => 'Pilihan jalur masuk wajib diisi.',
            'pilihan_jurusan.required'    => 'Pilihan jurusan wajib diisi.',
            'rekomendasi_status.required' => 'Status rekomendasi wajib dipilih.',
            'rekomendasi_nama.required_if' => 'Nama pemberi rekomendasi wajib diisi.',
            'rekomendasi_kelas.required_if' => 'Kelas pemberi rekomendasi wajib diisi.',
        ]);

        $user = Auth::user();
        
        // Siapkan array data yang akan disimpan
        $data = [
            'entry_path' => $request->jalur_masuk,
            'major'      => $request->pilihan_jurusan,
            'recommendation_status' => $request->rekomendasi_status,
        ];

        // Logika detail rekomendasi
        if ($request->rekomendasi_status === 'Tidak') {
            $data['recommendation_name']  = null;
            $data['recommendation_class'] = null;
        } else {
            $data['recommendation_name']  = $request->rekomendasi_nama;
            $data['recommendation_class'] = $request->rekomendasi_status === 'Siswa Aktif' ? $request->rekomendasi_kelas : null;
        }

        // Cek apakah ini pendaftaran baru
        $registration = Registration::where('user_id', $user->id)->first();
        
        if (!$registration) {
            // Jika baru pertama kali simpan, generate ID SPMB dan set status default
            $period = RegistrationPeriod::where('is_active', 1)->first();
            $data['spmb_id']   = 'SPMB-' . date('Y') . '-' . str_pad($user->id, 4, '0', STR_PAD_LEFT);
            $data['status']    = 'DRAFT';
            $data['period_id'] = $period ? $period->id : null;
        }

        // Simpan data (Buat baru jika belum ada, update jika sudah ada)
        Registration::updateOrCreate(
            ['user_id' => $user->id],
            $data
        );

        return redirect()->back()->with('success', 'Data pendaftaran berhasil disimpan');
    }

    public function dataDiri()
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->first();
        $address = $student ? StudentAddress::where('student_id', $student->id)->first() : null;

        $sekolah_list = Student::whereNotNull('school_origin')
            ->where('school_origin', '!=', '')
            ->distinct()
            ->pluck('school_origin');

        return view('student.formulir.data-diri', compact('student', 'address', 'sekolah_list'));
    }

    public function storeDataDiri(Request $request)
    {
        $request->validate([
            'nisn'                => 'required|digits_between:1,10',
            'sekolah_asal'        => 'required',
            'tempat_lahir'        => 'required',
            'tanggal_lahir'       => 'required|date',
            'agama'               => 'required',
            'berkebutuhan_khusus' => 'required',
            'nik'                 => 'required|digits:16',
            'no_akta'             => 'required',
            'kelurahan'           => 'required',
            'dusun'               => 'required', // Tambahan validasi Dusun
            'rt'                  => 'required|digits_between:1,3',
            'rw'                  => 'required|digits_between:1,3',
            'kecamatan'           => 'required',
            'provinsi'            => 'required',
            'kabupaten'           => 'required',
            'kode_pos'            => 'required|digits:5',
        ], [
            'nisn.required'       => 'NISN wajib diisi.',
            'nisn.digits_between' => 'NISN harus berupa angka maksimal 10 digit.',
            'nik.required'        => 'NIK wajib diisi.',
            'nik.digits'          => 'NIK harus tepat 16 digit angka.',
            'rt.digits_between'   => 'RT maksimal 3 digit angka.',
            'rw.digits_between'   => 'RW maksimal 3 digit angka.',
            'kode_pos.digits'     => 'Kode pos harus tepat 5 digit angka.',
            'required'            => ':attribute wajib diisi.',
        ]);

        $user = Auth::user();

        $student = Student::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nisn'          => $request->nisn,
                'nik'           => $request->nik,
                'school_origin' => $request->sekolah_asal,
                'birth_place'   => $request->tempat_lahir,
                'birth_date'    => $request->tanggal_lahir,
                'gender'        => $request->jenis_kelamin,
                'religion'      => $request->agama,
                'special_needs' => $request->berkebutuhan_khusus == 'Ya' ? 1 : 0,
                'child_number'  => $request->anak_ke,
                'siblings_count' => $request->jumlah_saudara,
                'birth_cert_no' => $request->no_akta,
            ]
        );

        Registration::where('user_id', $user->id)->update(['student_id' => $student->id]);

        StudentAddress::updateOrCreate(
            ['student_id' => $student->id],
            [
                'village'      => $request->kelurahan,
                'district'     => $request->dusun, // Menyimpan dusun ke kolom district
                'rt'           => $request->rt,
                'rw'           => $request->rw,
                'subdistrict'  => $request->kecamatan,
                'city'         => $request->kabupaten,
                'province'     => $request->provinsi,
                'postal_code'  => $request->kode_pos,
            ]
        );

        return redirect()->back()->with('success', 'Data diri berhasil disimpan');
    }

    public function dataOrangTua()
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->first();

        $ayah = $student ? StudentParent::where('student_id', $student->id)->where('type', 'ayah')->first() : null;
        $ibu = $student ? StudentParent::where('student_id', $student->id)->where('type', 'ibu')->first() : null;
        $wali = $student ? StudentParent::where('student_id', $student->id)->where('type', 'wali')->first() : null;

        return view('student.formulir.data-orang-tua', compact('student', 'ayah', 'ibu', 'wali'));
    }

    public function storeOrangTua(Request $request)
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            return redirect()->back()->with('error', 'Isi dan simpan data diri terlebih dahulu sebelum mengisi data orang tua.');
        }

        StudentParent::updateOrCreate(
            ['student_id' => $student->id, 'type' => 'ayah'],
            [
                'name'           => $request->nama_ayah,
                'birth_year'     => $request->tahun_lahir_ayah,
                'special_needs'  => $request->bk_ayah == 'Ya' ? 1 : 0,
                'occupation'     => $request->pekerjaan_ayah,
                'education'      => $request->pendidikan_ayah,
                'monthly_income' => $request->penghasilan_ayah,
            ]
        );

        StudentParent::updateOrCreate(
            ['student_id' => $student->id, 'type' => 'ibu'],
            [
                'name'           => $request->nama_ibu,
                'birth_year'     => $request->tahun_lahir_ibu,
                'special_needs'  => $request->bk_ibu == 'Ya' ? 1 : 0,
                'occupation'     => $request->pekerjaan_ibu,
                'education'      => $request->pendidikan_ibu,
                'monthly_income' => $request->penghasilan_ibu,
            ]
        );

        if ($request->filled('nama_wali')) {
            StudentParent::updateOrCreate(
                ['student_id' => $student->id, 'type' => 'wali'],
                [
                    'name'           => $request->nama_wali,
                    'birth_year'     => $request->tahun_lahir_wali,
                    'special_needs'  => $request->bk_wali == 'Ya' ? 1 : 0,
                    'occupation'     => $request->pekerjaan_wali,
                    'education'      => $request->pendidikan_wali,
                    'monthly_income' => $request->penghasilan_wali,
                ]
            );
        }

        return redirect()->back()->with('success', 'Data orang tua berhasil disimpan');
    }

    public function dataPeriodik()
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->first();

        return view('student.formulir.data-periodik', compact('student'));
    }

    public function storeDataPeriodik(Request $request)
    {
        $request->validate([
            'tinggi_badan'      => 'required|numeric',
            'berat_badan'       => 'required|numeric',
            'jarak_sekolah'     => 'required|numeric',
            'waktu_tempuh'      => 'required|integer',
            'alat_transportasi' => 'required|string',
        ], [
            'numeric' => 'Kolom :attribute hanya boleh diisi dengan angka.',
            'integer' => 'Kolom :attribute hanya boleh diisi dengan angka bulat.',
        ]);

        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->first();

        if ($student) {
            $student->update([
                'time_to_school'     => $request->waktu_tempuh,
                'distance_to_school' => $request->jarak_sekolah,
                'transportation'     => $request->alat_transportasi,
                'height'             => $request->tinggi_badan,
                'weight'             => $request->berat_badan,
            ]);
            return redirect()->back()->with('success', 'Data periodik berhasil disimpan');
        }

        return redirect()->back()->with('error', 'Isi data diri terlebih dahulu');
    }

    public function preview()
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->first();
        $registration = Registration::where('user_id', $user->id)->first();

        $unfilled_fields = [];

        if (!$registration || empty($registration->entry_path) || empty($registration->major)) {
            $unfilled_fields['Data Pendaftaran'] = route('student.formulir.data-pendaftaran');
        }

        if (!$student || empty($student->nisn) || empty($student->nik)) {
            $unfilled_fields['Data Diri'] = route('student.formulir.data-diri');
        }

        if ($student) {
            $ayah = StudentParent::where('student_id', $student->id)->where('type', 'ayah')->first();
            $ibu = StudentParent::where('student_id', $student->id)->where('type', 'ibu')->first();

            if (!$ayah || empty($ayah->name) || !$ibu || empty($ibu->name)) {
                $unfilled_fields['Data Orang Tua'] = route('student.formulir.data-orang-tua');
            }
        } else {
            $unfilled_fields['Data Orang Tua'] = route('student.formulir.data-orang-tua');
        }

        if (!$student || empty($student->distance_to_school) || empty($student->transportation)) {
            $unfilled_fields['Data Periodik'] = route('student.formulir.data-periodik');
        }

        return view('student.formulir.preview', compact('student', 'registration', 'unfilled_fields'));
    }

    public function submitFinal(Request $request)
    {
        $user = Auth::user();
        $registration = Registration::where('user_id', $user->id)->first();

        if ($registration) {
            $registration->update(['status' => 'SUBMITTED']);
        }

        return redirect()->route('student.dashboard')->with('success', 'Formulir pendaftaran berhasil dikirim permanen!');
    }

    public function informasiPembayaran()
    {
        $user = Auth::user();
        $registration = Registration::where('user_id', $user->id)->first();
        $biaya = \App\Models\RegistrationCost::first();

        return view('student.pembayaran.informasi', compact('registration'));
    }

    public function uploadBuktiPembayaran()
    {
        $user = Auth::user();
        $registration = Registration::where('user_id', $user->id)->first();

        return view('student.pembayaran.upload-bukti', compact('registration'));
    }

    public function storeBuktiPembayaran(Request $request)
    {
        $request->validate([
            'bukti_pembayaran' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ], [
            'bukti_pembayaran.required' => 'File bukti pembayaran wajib diunggah.',
            'bukti_pembayaran.image'    => 'File harus berupa gambar.',
            'bukti_pembayaran.mimes'    => 'Format gambar harus JPEG, PNG, atau JPG.',
            'bukti_pembayaran.max'      => 'Ukuran gambar maksimal 2 MB.',
        ]);

        $user = Auth::user();
        $registration = Registration::where('user_id', $user->id)->first();

        if ($registration) {
            if ($request->hasFile('bukti_pembayaran')) {
                $file = $request->file('bukti_pembayaran');
                $namaBersih = str_replace(' ', '_', $user->name);
                $tanggal = now()->format('d-m-Y_H-i-s');
                $filename = 'bukti_' . $namaBersih . '_' . $tanggal . '.' . $file->getClientOriginalExtension();

                $path = $file->storeAs('pembayaran', $filename, 'google');

                // 1. Simpan ke tabel Registrations
                $registration->payment_proof = $path;
                $registration->status = 'PAYMENT_REVIEW'; // Status berubah otomatis
                $registration->save();

                // 2. Simpan juga ke tabel Payments untuk riwayat (Sinkronisasi)
                \App\Models\Payment::create([
                    'registration_id' => $registration->id,
                    'amount' => 0,
                    'proof_path' => $path,
                    'status' => 'PENDING', // <--- UBAH DARI 'UNVERIFIED' MENJADI 'PENDING'
                ]);
            }

            return redirect()->route('student.pembayaran.upload-bukti')
                ->with('success', 'Bukti pembayaran berhasil diunggah dan sedang menunggu verifikasi');
        }

        return redirect()->back()->with('error', 'Data pendaftaran tidak ditemukan.');
    }

    public function lihatBuktiPembayaran()
    {
        $user = Auth::user();
        $registration = Registration::where('user_id', $user->id)->first();

        if ($registration && $registration->payment_proof) {
            $path = $registration->payment_proof;
            if (Storage::disk('google')->exists($path)) {
                $file = Storage::disk('google')->get($path);
                $type = (new \finfo(FILEINFO_MIME_TYPE))->buffer($file) ?: 'application/octet-stream';
                return response($file, 200)->header('Content-Type', $type);
            }
        }

        return abort(404, 'File bukti pembayaran tidak ditemukan di Google Drive.');
    }

    public function uploadBerkas()
    {
        $registration = \App\Models\Registration::with('documents')->where('user_id', Auth::id())->first();

        if (!$registration || in_array($registration->status, ['DRAFT', 'SUBMITTED', 'WAITING_PAYMENT', 'PAYMENT_REVIEW'])) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Anda harus menyelesaikan dan menunggu verifikasi pembayaran terlebih dahulu.');
        }

        return view('student.berkas.upload-berkas', compact('registration'));
    }

    public function lihatBerkas($id)
    {
        $registration = Registration::where('user_id', Auth::id())
            ->whereHas('documents', function ($query) use ($id) {
                $query->whereKey($id);
            })
            ->firstOrFail();
        $document = $registration->documents()->findOrFail($id);

        if (\Illuminate\Support\Facades\Storage::disk('google')->exists($document->drive_file_id)) {
            $file = \Illuminate\Support\Facades\Storage::disk('google')->get($document->drive_file_id);
            $type = $document->mime_type ?? 'application/pdf';
            return response($file, 200)->header('Content-Type', $type);
        }

        return abort(404, 'File tidak ditemukan di Google Drive.');
    }

    public function storeBerkas(Request $request)
    {
        $user = Auth::user();
        $registration = \App\Models\Registration::with('documents')->where('user_id', $user->id)->firstOrFail();

        // Pengecekan apakah user sudah pernah mengunggah Akta Lahir & KK sebelumnya
        $hasAkta = $registration->documents->where('type', 'Akta_Lahir')->isNotEmpty();
        $hasKK   = $registration->documents->where('type', 'Kartu_Keluarga')->isNotEmpty();

        // Validasi Dinamis: Jika sudah diunggah, file menjadi opsi (nullable). Jika belum, wajib (required).
        $request->validate([
            'nisn' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'akta' => ($hasAkta ? 'nullable' : 'required') . '|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'kk'   => ($hasKK ? 'nullable' : 'required') . '|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'skl'  => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ], [
            // Kustomisasi pesan error yang lebih spesifik
            'required' => 'File :attribute wajib diunggah karena belum pernah dilampirkan.',
            'mimes'    => 'Format file :attribute harus PDF, JPG, JPEG, atau PNG.',
            'max'      => 'Ukuran file :attribute masing-masing maksimal 2MB.',
            'file'     => 'File :attribute yang diunggah tidak dapat dibaca oleh sistem.'
        ]);

        $studentName = $user->name;
        $files = [
            'NISN'           => $request->file('nisn'),
            'Akta_Lahir'     => $request->file('akta'),
            'Kartu_Keluarga' => $request->file('kk'),
            'SKL_Ijazah'     => $request->file('skl')
        ];

        foreach ($files as $type => $file) {
            if ($file) {
                // Format: SPMBID_NamaSiswa_JenisBerkas.ext
                $filename = $registration->spmb_id . '_' . \Illuminate\Support\Str::slug($studentName) . '_' . \Illuminate\Support\Str::slug($type) . '.' . $file->getClientOriginalExtension();

                // Simpan ke Google Drive
                $path = $file->storeAs('Berkas_Pendaftar', $filename, 'google');

                // Simpan record ke database
                DB::table('documents')->updateOrInsert(
                    [
                        'registration_id' => $registration->id,
                        'type'            => $type
                    ],
                    [
                        'drive_file_id'   => $path,
                        'file_name'       => $filename,
                        'mime_type'       => $file->getMimeType(),
                        'size'            => $file->getSize(),
                        'status'          => 'PENDING',
                        'uploaded_at'     => now(),
                    ]
                );
            }
        }

        $registration->update(['status' => 'DOCUMENT_REVIEW']);

        return redirect()->route('student.dashboard')->with('success', 'Berkas berhasil diunggah dan sedang menunggu verifikasi');
    }

    public function pengumumanHasil()
    {
        $user = Auth::user();
        $registration = Registration::where('user_id', $user->id)->first();

        return view('student.pengumuman.hasil', compact('registration'));
    }
}
