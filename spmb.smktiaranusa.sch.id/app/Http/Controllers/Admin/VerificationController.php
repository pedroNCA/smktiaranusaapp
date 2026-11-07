<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class VerificationController extends Controller
{
    public function pembayaran(Request $request)
    {
        // Menampilkan yang sedang direview dan yang sudah diverifikasi
        $query = \App\Models\Registration::with(['user', 'student', 'payments'])
            ->whereIn('status', ['PAYMENT_REVIEW', 'VERIFIED']);

        // Fitur Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('spmb_id', 'like', "%{$search}%")
                    ->orWhereHas('student', function ($sq) use ($search) {
                        $sq->where('nisn', 'like', "%{$search}%")
                            ->orWhere('nik', 'like', "%{$search}%");
                    })
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // LOGIKA SORTING: Belum diverifikasi di atas, lalu urutkan dari yang paling duluan (oldest)
        $query->orderByRaw("FIELD(status, 'PAYMENT_REVIEW', 'VERIFIED')")
            ->oldest('updated_at');

        $perPage = $request->input('per_page', 10);
        $registrations = $query->paginate($perPage)->withQueryString();

        return view('admin.verifikasi.pembayaran', compact('registrations'));
    }

    // TAMBAHAN BARU: Method untuk menyetujui/menolak pembayaran
    public function verifikasiPembayaran(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:VERIFIED,REJECTED',
            'notes' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            // Load relasi 'user' untuk mengambil nomor WA dan nama
            $registration = Registration::with('user')->findOrFail($id);

            $payment = \App\Models\Payment::where('registration_id', $registration->id)->latest()->first();
            if ($payment) {
                $payment->update([
                    'status' => $request->status,
                    'notes' => $request->notes,
                    'verified_by' => Auth::id(),
                    'verified_at' => now(),
                ]);
            }

            // --- PERSIAPAN URL WHATSAPP ---
            $waNumber = $registration->user->whatsapp ?? null;
            $nama = $registration->user->name ?? 'Siswa';
            $waLink = null;

            if ($request->status === 'VERIFIED') {
                $registration->update(['status' => 'DOCUMENT_REVIEW']);
                $pesanFlash = 'Pembayaran disetujui. Siswa kini dapat mengunggah berkas.';
                $pesanWa = "Halo {$nama},\n\nKabar baik! Pembayaran pendaftaran SPMB Anda telah *DISETUJUI*. \n\nSilakan login ke sistem untuk melanjutkan ke tahap unggah berkas kelengkapan pendaftaran.";
            } else {
                $registration->update(['status' => 'WAITING_PAYMENT']);
                $pesanFlash = 'Pembayaran ditolak. Siswa harus mengunggah ulang bukti pembayaran.';
                $catatan = $request->notes ? "\n*Catatan Admin:* " . $request->notes : "";
                $pesanWa = "Halo {$nama},\n\nMohon maaf, bukti pembayaran pendaftaran SPMB Anda *DITOLAK*.{$catatan}\n\nSilakan login ke sistem dan unggah ulang bukti pembayaran yang valid.";
            }

            // Format Nomor WA (Ubah 0 awalan dengan 62)
            if ($waNumber) {
                if (substr($waNumber, 0, 1) == '0') {
                    $waNumber = '62' . substr($waNumber, 1);
                }
                $waLink = "https://wa.me/" . $waNumber . "?text=" . urlencode($pesanWa);
            }

            DB::commit();

            // Kembalikan redirect berserta Link WA jika nomornya ada
            if ($waLink) {
                return redirect()->back()->with('success', $pesanFlash)->with('wa_link', $waLink);
            }

            return redirect()->back()->with('success', $pesanFlash);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // Jika Anda ingin hal yang sama untuk method Delete Bukti Pembayaran:
    public function destroyBuktiPembayaran($id)
    {
        try {
            DB::beginTransaction();

            // Perbaikan: Cari data registration berdasarkan $id, lalu ambil relasi payment-nya
            $registration = Registration::with(['payments', 'user'])->findOrFail($id);
            $payment = $registration->payments()->latest()->first();

            if ($payment && $payment->proof_path && \Illuminate\Support\Facades\Storage::disk('google')->exists($payment->proof_path)) {
                \Illuminate\Support\Facades\Storage::disk('google')->delete($payment->proof_path);
            }

            if ($payment) {
                $payment->delete();
            }

            $registration->update([
                'payment_proof' => null,
                'status' => 'WAITING_PAYMENT'
            ]);

            // Persiapan URL WA
            $waNumber = $registration->user->whatsapp ?? null;
            $nama = $registration->user->name ?? 'Siswa';
            $waLink = null;

            if ($waNumber) {
                if (substr($waNumber, 0, 1) == '0') {
                    $waNumber = '62' . substr($waNumber, 1);
                }
                $pesanWa = "Halo {$nama},\n\nBukti pembayaran SPMB Anda telah *DIHAPUS* oleh admin karena file bermasalah/tidak valid.\n\nSilakan login kembali ke sistem untuk mengunggah ulang bukti pembayaran.";
                $waLink = "https://wa.me/" . $waNumber . "?text=" . urlencode($pesanWa);
            }

            DB::commit();

            if ($waLink) {
                return redirect()->back()->with('success', 'Bukti berhasil dihapus.')->with('wa_link', $waLink);
            }
            return redirect()->back()->with('success', 'Bukti berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function berkas(Request $request)
    {
        // PERBAIKAN: Masukkan 'ACCEPTED' agar siswa yang sudah diverifikasi/mengunggah file tambahan tetap muncul di daftar
        $query = \App\Models\Registration::with(['student.user', 'user', 'documents'])
            ->where('status', 'DOCUMENT_REVIEW');

        // Fitur Pencarian 
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('spmb_id', 'like', "%{$search}%")
                    ->orWhereHas('student.user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter Status 
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // LOGIKA SORTING
        $query->orderByRaw("FIELD(status, 'DOCUMENT_REVIEW', 'VERIFIED', 'ACCEPTED')")
            ->oldest('updated_at');

        $perPage = $request->input('per_page', 10);
        $registrations = $query->paginate($perPage)->withQueryString();

        return view('admin.verifikasi.berkas', compact('registrations'));
    }

    // PERBAIKAN: Method Update Status Berkas
   public function updateStatusBerkas(Request $request, $id)
    {
        $registration = Registration::with('user')->findOrFail($id);
        $statusBaru = $request->status;
        
        $registration->update(['status' => $statusBaru]);

        // === LOGIKA PERAKITAN WA LINK ===
        $waNumber = $registration->user->whatsapp ?? null;
        $nama = $registration->user->name ?? 'Siswa';
        $waLink = null;

        if ($waNumber) {
            if (substr($waNumber, 0, 1) == '0') {
                $waNumber = '62' . substr($waNumber, 1);
            }
            
            if ($statusBaru == 'ACCEPTED') {
                $pesanWa = "Halo {$nama},\n\nSelamat! Berkas pendaftaran SPMB Anda telah *DISETUJUI DAN DIVERIFIKASI*. Anda telah dinyatakan LULUS verifikasi.";
            } else {
                $pesanWa = "Halo {$nama},\n\nMohon maaf, berkas pendaftaran SPMB Anda ada yang *PERLU DIPERBAIKI/DITOLAK* oleh admin. Silakan login kembali ke sistem untuk memperbaiki berkas Anda.";
            }
            
            $waLink = "https://wa.me/" . $waNumber . "?text=" . urlencode($pesanWa);
        }

        return redirect()->back()
            ->with('success', 'Status berkas berhasil diperbarui.')
            ->with('wa_link', $waLink);
    }

    // Aksi CRUD: Tambah / Unggah Dokumen oleh Admin (jika diperlukan)
    public function storeDokumenAdmin(Request $request, $registrationId)
    {
        $request->validate([
            'type' => 'required',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048'
        ]);

        $registration = Registration::findOrFail($registrationId);
        $file = $request->file('file');
        $studentName = $registration->student->user->name ?? 'Siswa';

        $filename = $registration->spmb_id . '_' . Str::slug($studentName) . '_' . Str::slug($request->type) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('Berkas_Pendaftar', $filename, 'google');

        DB::table('documents')->updateOrInsert(
            ['registration_id' => $registration->id, 'type' => $request->type],
            [
                'drive_file_id' => $path,
                'file_name' => $filename,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'status' => 'VERIFIED',
                'uploaded_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return redirect()->back()->with('success', 'Dokumen berhasil ditambahkan/diperbarui.');
    }

    // Aksi CRUD: Hapus Dokumen
    public function destroyDokumen($id)
    {
        $document = DB::table('documents')->where('id', $id)->first();
        abort_if(!$document, 404);
        if (Storage::disk('google')->exists($document->drive_file_id)) {
            Storage::disk('google')->delete($document->drive_file_id);
        }
        DB::table('documents')->where('id', $id)->delete();

        return redirect()->back()->with('success', 'Dokumen berhasil dihapus.');
    }

    // Fungsi khusus Admin untuk melihat dokumen siswa
    public function lihatDokumen($id)
    {
        $document = DB::table('documents')->where('id', $id)->first();
        abort_if(!$document, 404);

        if (Storage::disk('google')->exists($document->drive_file_id)) {
            $file = Storage::disk('google')->get($document->drive_file_id);
            $type = $document->mime_type ?? 'application/pdf';
            return response($file, 200)->header('Content-Type', $type);
        }

        return abort(404, 'File tidak ditemukan di Google Drive.');
    }
}
