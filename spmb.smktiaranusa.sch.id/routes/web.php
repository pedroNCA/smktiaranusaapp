<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RequirementController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CostController;
use App\Http\Controllers\PendaftarController;
use App\Http\Controllers\RegistrationPeriodController;
use App\Http\Controllers\Admin\VerificationController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SystemController;
use App\Http\Controllers\Admin\StudentUserController;
use App\Http\Controllers\Student\StudentController;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Admin\ObservationController;
use App\Http\Controllers\Admin\TpaController;
use App\Http\Controllers\PublicTpaController;
use App\Http\Controllers\Admin\PendaftarController as AdminPendaftarController;

/*
|--------------------------------------------------------------------------
| ROUTING PROFIL JURUSAN (TJKT & MPLB)
|--------------------------------------------------------------------------
*/

Route::prefix('tjkt')->name('tjkt.')->group(function () {
    Route::get('/', function () {
        return view('tjkt.beranda');
    })->name('beranda');
});

Route::prefix('mplb')->name('mplb.')->group(function () {
    Route::get('/', function () {
        return view('mplb.beranda');
    })->name('beranda');
});

/*
|--------------------------------------------------------------------------
| ROUTING PUBLIK SPMB & BERANDA UTAMA
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return redirect()->route('spmb.beranda');
});

Route::prefix('spmb')->name('spmb.')->group(function () {
    Route::get('/', [PublicController::class, 'berandaUtama'])->name('beranda');
    Route::get('/informasi', [PublicController::class, 'informasi'])->name('informasi');
    Route::get('/jurusan', [PublicController::class, 'jurusan'])->name('jurusan');
    Route::get('/jadwal', [ScheduleController::class, 'publicIndex'])->name('jadwal');
    Route::get('/persyaratan', [PublicController::class, 'persyaratan'])->name('persyaratan');
    Route::get('/biaya', [PublicController::class, 'biaya'])->name('biaya');
    Route::get('/alur', function () {
        return view('spmb.alur');
    })->name('alur');
    Route::get('/faq', [PublicController::class, 'faq'])->name('faq');
    Route::get('/kontak', [PublicController::class, 'kontak'])->name('kontak');
});

/*
|--------------------------------------------------------------------------
| ROUTING PUBLIK CBT TPA SISWA (Tanpa Login)
|--------------------------------------------------------------------------
*/
Route::get('/cbt-tpa', [PublicTpaController::class, 'index'])->name('public.tpa.index');
Route::post('/cbt-tpa/verify', [PublicTpaController::class, 'verify'])->name('public.tpa.verify');
Route::get('/cbt-tpa/exam/{id}', [PublicTpaController::class, 'examSession'])->name('public.tpa.exam');
Route::post('/cbt-tpa/submit/{id}', [PublicTpaController::class, 'submitExam'])->name('public.tpa.submit');

/*
|--------------------------------------------------------------------------
| ROUTING AUTENTIKASI (GUEST)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');

    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

/*
|--------------------------------------------------------------------------
| ROUTING SISTEM (AUTH)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    // Verifikasi OTP & Logout
    Route::get('/verify-otp', [AuthController::class, 'showVerifyOtp'])->name('verify.otp');
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp'])->name('resend.otp');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard Student
    Route::get('/student/dashboard', function () {
        return view('student.dashboard');
    })->name('student.dashboard');

    // Biarkan di luar prefix jika route name aslinya tidak menggunakan admin.
    Route::resource('registration-periods', RegistrationPeriodController::class)->except(['create', 'edit', 'show']);

    Route::prefix('student')->name('student.')->middleware(['web', 'auth'])->group(function () {
        Route::get('/dashboard', [StudentController::class, 'dashboard'])->name('dashboard');

        // Grup Formulir (Multi-step form & Draft)
        Route::prefix('formulir')->name('formulir.')->group(function () {
            Route::get('/data-pendaftaran', [StudentController::class, 'dataPendaftaran'])->name('data-pendaftaran');
            Route::post('/data-pendaftaran', [StudentController::class, 'storePendaftaran'])->name('store-pendaftaran');

            Route::get('/data-diri', [StudentController::class, 'dataDiri'])->name('data-diri');
            Route::post('/data-diri', [StudentController::class, 'storeDataDiri'])->name('store-data-diri');

            Route::get('/data-orang-tua', [StudentController::class, 'dataOrangTua'])->name('data-orang-tua');
            Route::post('/data-orang-tua', [StudentController::class, 'storeOrangTua'])->name('store-orang-tua');

            Route::get('/data-periodik', [StudentController::class, 'dataPeriodik'])->name('data-periodik');
            Route::post('/data-periodik', [StudentController::class, 'storeDataPeriodik'])->name('store-data-periodik');

            Route::get('/preview', [StudentController::class, 'preview'])->name('preview');
            Route::post('/submit-final', [StudentController::class, 'submitFinal'])->name('submit-final');
        });

        // Berkas & Pembayaran
        Route::get('/berkas/upload', [StudentController::class, 'uploadBerkas'])->name('berkas.upload');
        Route::post('/berkas/upload', [StudentController::class, 'storeBerkas'])->name('berkas.upload.store');

        // Rute Pembayaran
        Route::get('/pembayaran/informasi', [StudentController::class, 'informasiPembayaran'])->name('pembayaran.informasi');
        Route::get('/pembayaran/upload-bukti', [StudentController::class, 'uploadBuktiPembayaran'])->name('pembayaran.upload-bukti');
        Route::post('/pembayaran/upload-bukti', [StudentController::class, 'storeBuktiPembayaran'])->name('pembayaran.upload-bukti.store');

        // Pengumuman & Profil
        Route::get('/pengumuman/hasil', [StudentController::class, 'pengumumanHasil'])->name('pengumuman.hasil');

        // Perbaikan nama rute profil agar sinkron dengan dropdown topbar sebelumnya
        Route::get('/profil/data-akun', [StudentController::class, 'dataAkun'])->name('data-akun');
        Route::get('/profil/ganti-password', [StudentController::class, 'gantiPassword'])->name('ganti-password');
        Route::post('/profil/update-password', [StudentController::class, 'updatePassword'])->name('update-password');
    });

    /* === GRUP KHUSUS ADMIN === */
    Route::prefix('admin')->name('admin.')->group(function () {

        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('dashboard');

        // Verifikasi Pendaftar
        Route::get('/verifikasi/pembayaran', [VerificationController::class, 'pembayaran'])->name('verifikasi.pembayaran');
        Route::put('/verifikasi/pembayaran/{id}/status', [VerificationController::class, 'verifikasiPembayaran'])->name('verifikasi.pembayaran.status');
        Route::put('/verifikasi/pembayaran/{id}', [VerificationController::class, 'verifikasiPembayaran'])->name('verifikasi.pembayaran.update');
        Route::get('/verifikasi/berkas', [VerificationController::class, 'berkas'])->name('verifikasi.berkas');

        Route::put('/verifikasi/berkas/{id}/status', [VerificationController::class, 'updateStatusBerkas'])->name('verifikasi.berkas.status');
        Route::delete('/verifikasi/berkas/dokumen/{id}', [VerificationController::class, 'destroyDokumen'])->name('verifikasi.berkas.dokumen.destroy');
        Route::get('/verifikasi/berkas/dokumen/{id}/lihat', [VerificationController::class, 'lihatDokumen'])->name('verifikasi.berkas.dokumen.lihat');

        // Manajemen Jadwal SPMB
        Route::get('/schedules', [ScheduleController::class, 'adminIndex'])->name('schedules.index');
        Route::post('/schedules', [ScheduleController::class, 'store'])->name('schedules.store');
        Route::put('/schedules/{id}', [ScheduleController::class, 'update'])->name('schedules.update');
        Route::delete('/schedules/{id}', [ScheduleController::class, 'destroy'])->name('schedules.destroy');

        // Manajemen Persyaratan SPMB
        Route::get('/requirements', [RequirementController::class, 'adminIndex'])->name('requirements.index');
        Route::post('/requirements', [RequirementController::class, 'store'])->name('requirements.store');
        Route::put('/requirements/{id}', [RequirementController::class, 'update'])->name('requirements.update');
        Route::delete('/requirements/{id}', [RequirementController::class, 'destroy'])->name('requirements.destroy');

        // Manajemen Biaya SPMB
        Route::get('/costs', [CostController::class, 'adminIndex'])->name('costs.index');
        Route::post('/costs', [CostController::class, 'store'])->name('costs.store');
        Route::put('/costs/{id}', [CostController::class, 'update'])->name('costs.update');
        Route::delete('/costs/{id}', [CostController::class, 'destroy'])->name('costs.destroy');

        // Manajemen FAQ & Kontak
        Route::get('/faqs', [FaqController::class, 'adminIndex'])->name('faqs.index');
        Route::post('/faqs', [FaqController::class, 'store'])->name('faqs.store');
        Route::put('/faqs/{id}', [FaqController::class, 'update'])->name('faqs.update');
        Route::delete('/faqs/{id}', [FaqController::class, 'destroy'])->name('faqs.destroy');
        Route::get('/contact', [ContactController::class, 'adminIndex'])->name('contact.index');
        Route::put('/contact', [ContactController::class, 'update'])->name('contact.update');

        // Laporan
        Route::get('/laporan', [ReportController::class, 'index'])->name('laporan.index');
        Route::get('/laporan/cetak', [ReportController::class, 'cetak'])->name('laporan.cetak');

        // Sistem
        Route::get('/sistem/audit-log', [SystemController::class, 'auditLog'])->name('sistem.audit-log');

        // Perbaikan Rute Manajemen User (CRUD)
        Route::get('/sistem/manajemen-user', [SystemController::class, 'userManagement'])->name('sistem.manajemen-user');
        Route::post('/sistem/manajemen-user', [SystemController::class, 'storeUser'])->name('sistem.manajemen-user.store');
        Route::put('/sistem/manajemen-user/{id}', [SystemController::class, 'updateUser'])->name('sistem.manajemen-user.update');
        Route::delete('/sistem/manajemen-user/{id}', [SystemController::class, 'destroyUser'])->name('sistem.manajemen-user.destroy');
        
        // Manajemen Akun Siswa
        Route::get('/akun-siswa', [StudentUserController::class, 'index'])->name('akun-siswa.index');
        Route::delete('/akun-siswa/{id}', [StudentUserController::class, 'destroy'])->name('akun-siswa.destroy');
        Route::put('/akun-siswa/{id}/password', [StudentUserController::class, 'updatePassword'])->name('akun-siswa.updatePassword');

        // Siswa Belum Isi Formulir
        Route::get('/akun-siswa-pending', [StudentUserController::class, 'pending'])->name('akun-siswa.pending');

        // Manajemen Pendaftar
        Route::get('/pendaftar', [PendaftarController::class, 'index'])->name('pendaftar.index');

        // Tambah Pendaftar Manual oleh Admin
        Route::get('/pendaftar/tambah', [AdminPendaftarController::class, 'create'])
            ->name('pendaftar.create');

        Route::post('/pendaftar/tambah', [AdminPendaftarController::class, 'store'])
            ->name('pendaftar.store');

        Route::get('/pendaftar/{id}', [PendaftarController::class, 'detail'])->name('pendaftar.detail');
        Route::put('/pendaftar/{id}/status', [PendaftarController::class, 'updateStatus'])->name('pendaftar.updateStatus');

        Route::get('/pendaftar/{id}/edit', [PendaftarController::class, 'edit'])->name('pendaftar.edit');
        Route::put('/pendaftar/{id}/update', [PendaftarController::class, 'update'])->name('pendaftar.update');

        Route::get('/pendaftar/{id}/lihat-bukti', [PendaftarController::class, 'lihatBukti'])
            ->name('pendaftaran.lihatBukti');

        // Laporan & Rekapitulasi
        Route::prefix('laporan')->name('laporan.')->group(function () {
            Route::get('/rekapitulasi', [App\Http\Controllers\Admin\ReportController::class, 'rekapitulasi'])->name('rekapitulasi');
            Route::get('/excel', [App\Http\Controllers\Admin\ReportController::class, 'exportExcel'])->name('excel');
        });

        // Master Data Asal Sekolah
        Route::get('/asal-sekolah/export', [App\Http\Controllers\Admin\SchoolOriginController::class, 'export'])->name('asal-sekolah.export');
        Route::resource('asal-sekolah', App\Http\Controllers\Admin\SchoolOriginController::class)->except(['create', 'show', 'edit']);

        // Modul Cetak Formulir
        Route::get('/cetak-formulir', [App\Http\Controllers\Admin\CetakFormulirController::class, 'index'])->name('cetak-formulir.index');
        Route::get('/cetak-formulir/print/{id}', [App\Http\Controllers\Admin\CetakFormulirController::class, 'cetak'])->name('cetak-formulir.print');
        Route::post('/cetak-formulir/print-bulk', [App\Http\Controllers\Admin\CetakFormulirController::class, 'cetakBulk'])->name('cetak-formulir.print-bulk');

        Route::delete('/verifikasi/pembayaran/{id}/bukti', [VerificationController::class, 'destroyBuktiPembayaran'])->name('verifikasi.pembayaran.destroyBukti');
        Route::delete('/pendaftar/{id}/destroy-total', [PendaftarController::class, 'destroyTotal'])->name('pendaftar.destroyTotal');

        // Observasi & Checklist
        Route::get('/observasi', [ObservationController::class, 'index'])->name('observasi.index');
        Route::post('/observasi/update-checklist/{id}', [ObservationController::class, 'updateChecklist'])->name('observasi.update-checklist');

        // Kelola Soal TPA
        Route::get('/observasi/soal', [TpaController::class, 'soalIndex'])->name('tpa.soal.index');
        Route::post('/observasi/soal', [TpaController::class, 'soalStore'])->name('tpa.soal.store');
        Route::get('/observasi/soal/template', [TpaController::class, 'soalTemplate'])->name('tpa.soal.template');
        Route::delete('/observasi/soal/bulk-delete', [TpaController::class, 'soalBulkDestroy'])->name('tpa.soal.bulk-delete');
        Route::put('/observasi/soal/{id}', [TpaController::class, 'soalUpdate'])->name('tpa.soal.update');
        Route::delete('/observasi/soal/{id}', [TpaController::class, 'soalDestroy'])->name('tpa.soal.destroy');
        Route::post('/observasi/soal/import', [TpaController::class, 'soalImport'])->name('tpa.soal.import');
        Route::get('/observasi/soal/template', [TpaController::class, 'soalTemplate'])->name('tpa.soal.template');
        Route::post('/observasi/soal/import', [TpaController::class, 'soalImport'])->name('tpa.soal.import');


        // Pengaturan & Hasil TPA
        Route::get('/observasi/pengaturan', [TpaController::class, 'settingsIndex'])
            ->name('tpa.settings.index');

        Route::post('/observasi/pengaturan', [TpaController::class, 'settingsUpdate'])
            ->name('tpa.settings.update');

        Route::get('/observasi/hasil', [TpaController::class, 'hasilIndex'])
            ->name('tpa.hasil.index');

        Route::get('/observasi/hasil/export', [TpaController::class, 'hasilExport'])
            ->name('tpa.hasil.export');

        // Hapus satu hasil ujian
        Route::delete('/observasi/hasil/{id}', [TpaController::class, 'hasilDestroy'])
            ->name('tpa.hasil.destroy');

        // Hapus hasil ujian yang dipilih
        Route::delete('/observasi/hasil-bulk-delete', [TpaController::class, 'hasilBulkDestroy'])
            ->name('tpa.hasil.bulk-delete');
    });

    // Rute Testing & GDrive
    Route::get('/test-gdrive', function () {
        try {
            Storage::disk('google')->put('test-koneksi.txt', 'Halo, koneksi Google Drive berhasil pada ' . now());
            return 'BERHASIL! File test-koneksi.txt telah terupload. Silakan cek folder Google Drive Anda.';
        } catch (\Exception $e) {
            return 'GAGAL! Pesan Error: ' . $e->getMessage();
        }
    });

    Route::get('/debug-gdrive', function () {
        return response()->json([
            'clientId'     => config('filesystems.disks.google.clientId') ? 'TERISI' : 'KOSONG',
            'clientSecret' => config('filesystems.disks.google.clientSecret') ? 'TERISI' : 'KOSONG',
            'refreshToken' => config('filesystems.disks.google.refreshToken') ? 'TERISI' : 'KOSONG',
            'folderId'     => config('filesystems.disks.google.folderId') ? 'TERISI' : 'KOSONG',
        ]);
    });

    Route::get('/student/pembayaran/lihat-bukti-drive', [App\Http\Controllers\Student\StudentController::class, 'lihatBuktiPembayaran'])
        ->name('student.pembayaran.lihat-bukti-drive');

    Route::get('/student/berkas/lihat/{id}', [App\Http\Controllers\Student\StudentController::class, 'lihatBerkas'])
        ->name('student.berkas.lihat');
});

Route::get('/clear-c', function() {
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
    return 'Cache berhasil dibersihkan!';
});
