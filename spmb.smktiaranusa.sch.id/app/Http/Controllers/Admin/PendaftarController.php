<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Registration;
use App\Models\RegistrationPeriod; // Menggunakan model gelombang pendaftaran yang benar
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Student;
use App\Models\StudentAddress;
use App\Models\StudentParent;
use Illuminate\Validation\Rule;

class PendaftarController extends Controller
{
    public function create()
    {
        // Ambil gelombang yang aktif berdasarkan tanggal hari ini
        $period = RegistrationPeriod::active()->first();

        $lastReg = Registration::orderByDesc('id')->first();
        $nextId = $lastReg ? $lastReg->id + 1 : 1;

        $tahun_pelajaran = '2027/2028';

        $spmb_id = 'SPMB-' . date('Y') . '-' . str_pad(
            $nextId,
            4,
            '0',
            STR_PAD_LEFT
        );

        return view('admin.pendaftar.create', compact(
            'period',
            'spmb_id',
            'tahun_pelajaran'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'whatsapp' => ['required', 'string', 'max:20'],
            'sekolah_asal' => ['required', 'string', 'max:255'],
            'jalur_masuk' => ['required', Rule::in(['Reguler', 'Beasiswa'])],
            'pilihan_jurusan' => ['required', Rule::in(['TJKT', 'MPLB'])],

            'rekomendasi_status' => [
                'required',
                Rule::in(['Tidak', 'Alumni', 'Siswa Aktif']),
            ],

            'rekomendasi_nama' => [
                'nullable',
                'required_if:rekomendasi_status,Alumni,Siswa Aktif',
                'string',
                'max:255',
            ],

            'rekomendasi_kelas' => [
                'nullable',
                'required_if:rekomendasi_status,Siswa Aktif',
                'string',
                'max:100',
            ],
        ]);

        $activePeriod = RegistrationPeriod::active()->first();

        if (!$activePeriod) {
            return back()
                ->withInput()
                ->withErrors([
                    'period' => 'Gelombang pendaftaran aktif tidak ditemukan di pengaturan SPMB.',
                ]);
        }

        try {
            DB::transaction(function () use ($validated, $activePeriod) {
                // Buat ID SPMB berdasarkan ID registrasi berikutnya
                $lastReg = Registration::orderByDesc('id')->first();
                $nextId = $lastReg ? $lastReg->id + 1 : 1;

                $spmbId = 'SPMB-' . date('Y') . '-' . str_pad(
                    $nextId,
                    4,
                    '0',
                    STR_PAD_LEFT
                );

                // 1. Buat akun siswa
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => Hash::make('password123'),
                    'whatsapp' => $validated['whatsapp'],
                    'role' => 'student',
                ]);

                // 2. Buat profil siswa
                $student = Student::create([
                    'user_id' => $user->id,
                    'school_origin' => $validated['sekolah_asal'],
                ]);

                // 3. Buat data alamat awal
                StudentAddress::create([
                    'student_id' => $student->id,
                ]);

                // 4. Buat data orang tua awal
                StudentParent::create([
                    'student_id' => $student->id,
                    'type' => 'ayah',
                ]);

                StudentParent::create([
                    'student_id' => $student->id,
                    'type' => 'ibu',
                ]);

                // 5. Buat registrasi pendaftar
                Registration::create([
                    'user_id' => $user->id,
                    'student_id' => $student->id,
                    'spmb_id' => $spmbId,
                    'period_id' => $activePeriod->id,
                    'entry_path' => $validated['jalur_masuk'],
                    'major' => $validated['pilihan_jurusan'],
                    'recommendation_status' => $validated['rekomendasi_status'],

                    'recommendation_name' =>
                    $validated['rekomendasi_status'] !== 'Tidak'
                        ? ($validated['rekomendasi_nama'] ?? null)
                        : null,

                    'recommendation_class' =>
                    $validated['rekomendasi_status'] === 'Siswa Aktif'
                        ? ($validated['rekomendasi_kelas'] ?? null)
                        : null,

                    'status' => 'SUBMITTED',
                ]);
            });

            return redirect()
                ->route('admin.pendaftar.index')
                ->with(
                    'success',
                    'Pendaftar baru berhasil ditambahkan! Akun siswa sudah otomatis terbuat.'
                );
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->withErrors([
                    'error' => $e->getMessage(),
                ]);
        }
    }
}
