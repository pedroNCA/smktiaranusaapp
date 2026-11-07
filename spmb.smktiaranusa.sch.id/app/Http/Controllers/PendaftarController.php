<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Registration;
use Illuminate\Support\Facades\Storage;

class PendaftarController extends Controller
{
    public function lihatBukti($id)
    {
        $registration = \App\Models\Registration::findOrFail($id);

        if ($registration && $registration->payment_proof) {
            $path = $registration->payment_proof;

            // Cek apakah file ada di Google Drive
            if (Storage::disk('google')->exists($path)) {
                $file = Storage::disk('google')->get($path);
                $type = (new \finfo(FILEINFO_MIME_TYPE))->buffer($file) ?: 'application/octet-stream';

                return response($file, 200)->header('Content-Type', $type);
            }
        }

        return abort(404, 'File bukti pembayaran tidak ditemukan di Google Drive.');
    }

    public function index(Request $request)
    {
        $query = Registration::with(['student.user', 'user', 'period'])
            ->where('status', '!=', 'DRAFT');

        // Fitur Search (berdasarkan ID SPMB atau Nama Siswa)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('spmb_id', 'like', "%{$search}%")
                    ->orWhereHas('student.user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Fitur Filter Status Pendaftaran
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Fitur Filter Jurusan
        if ($request->filled('major')) {
            $query->where('major', $request->major);
        }

        // Pagination (default 10 data per halaman, atau sesuai pilihan user)
        $perPage = $request->input('per_page', 10);
        $pendaftar = $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();

        return view('admin.pendaftar.index', compact('pendaftar'));
    }

    public function detail($id)
    {
        // Mengambil detail lengkap pendaftar berdasarkan ID registrasi
        $registration = Registration::with([
            'student.user',
            'student.address',
            'student.parents',
            'period',
            'documents', // choices.major sudah dihapus dari daftar ini
            'payments'
        ])->findOrFail($id);

        return view('admin.pendaftar.detail', compact('registration'));
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string',
        ]);

        $registration = Registration::findOrFail($id);
        $registration->update([
            'status' => $request->status
        ]);

        return back()->with('success', 'Status pendaftaran berhasil diperbarui!');
    }

    public function destroyTotal($id)
    {
        $reg = \App\Models\Registration::with(['documents', 'payments'])->findOrFail($id);

        // 1. Hapus semua file Berkas di Google Drive
        foreach ($reg->documents as $doc) {
            if (\Illuminate\Support\Facades\Storage::disk('google')->exists($doc->drive_file_id)) {
                \Illuminate\Support\Facades\Storage::disk('google')->delete($doc->drive_file_id);
            }
        }

        // 2. Hapus semua file Bukti Bayar di Google Drive
        foreach ($reg->payments as $payment) {
            if ($payment->proof_path && \Illuminate\Support\Facades\Storage::disk('google')->exists($payment->proof_path)) {
                \Illuminate\Support\Facades\Storage::disk('google')->delete($payment->proof_path);
            }
        }
        if ($reg->payment_proof && \Illuminate\Support\Facades\Storage::disk('google')->exists($reg->payment_proof)) {
            \Illuminate\Support\Facades\Storage::disk('google')->delete($reg->payment_proof);
        }

        // 3. Hapus data dari Database
        $reg->documents()->delete();
        $reg->payments()->delete();
        $reg->delete();

        return redirect()->back()->with('success', 'Data pendaftar beserta seluruh file fisiknya berhasil dihapus secara permanen.');
    }

    public function edit($id)
    {
        $registration = Registration::with(['student.user', 'student.address', 'student.parents', 'documents'])->findOrFail($id);

        // Relasi instan (fallback ke model kosong jika belum pernah diisi untuk menghindari error)
        $student = $registration->student ?? new \App\Models\Student();
        $address = $student->address ?? new \App\Models\StudentAddress();
        $parents = $student->parents ?? collect();

        $father = $parents->where('type', 'ayah')->first() ?? new \App\Models\StudentParent(['type' => 'ayah']);
        $mother = $parents->where('type', 'ibu')->first() ?? new \App\Models\StudentParent(['type' => 'ibu']);
        $guardian = $parents->where('type', 'wali')->first() ?? new \App\Models\StudentParent(['type' => 'wali']);

        return view('admin.pendaftar.edit', compact('registration', 'student', 'address', 'father', 'mother', 'guardian'));
    }

    public function update(Request $request, $id)
    {
        $registration = Registration::findOrFail($id);
        $student = $registration->student;
        $user = $student->user;

        // 1. Update Akun (User)
        $user->update([
            'name' => $request->name,
            'whatsapp' => $request->whatsapp,
        ]);

        // 2. Update Data Diri & Periodik (Student)
        $student->update([
            'nisn' => $request->nisn,
            'nik' => $request->nik,
            'school_origin' => $request->school_origin,
            'birth_place' => $request->birth_place,
            'birth_date' => $request->birth_date,
            'gender' => $request->gender,
            'religion' => $request->religion,
            'special_needs' => $request->has('student_special_needs') ? 1 : 0,
            'child_number' => $request->child_number,
            'siblings_count' => $request->siblings_count,
            'phone' => $request->phone,
            'time_to_school' => $request->time_to_school,
            'distance_to_school' => $request->distance_to_school,
            'transportation' => $request->transportation,
            'height' => $request->height,
            'weight' => $request->weight,
        ]);

        // 3. Update Alamat (StudentAddress)
        \App\Models\StudentAddress::updateOrCreate(
            ['student_id' => $student->id],
            $request->only(['street', 'rt', 'rw', 'village', 'subdistrict', 'city', 'province', 'postal_code'])
        );

        // 4. Update Orang Tua (StudentParent)
        $parentTypes = ['ayah', 'ibu', 'wali'];
        foreach ($parentTypes as $type) {
            if ($request->filled("{$type}_name")) {
                \App\Models\StudentParent::updateOrCreate(
                    ['student_id' => $student->id, 'type' => $type],
                    [
                        'name' => $request->input("{$type}_name"),
                        'birth_year' => $request->input("{$type}_birth_year"),
                        'occupation' => $request->input("{$type}_occupation"),
                        'education' => $request->input("{$type}_education"),
                        'monthly_income' => $request->input("{$type}_monthly_income"),
                        'special_needs' => $request->has("{$type}_special_needs") ? 1 : 0,
                    ]
                );
            }
        }

        // 5. Update Pilihan Jurusan & Rekomendasi (Registration)
        $registration->update([
            'major' => $request->major,
            'recommendation_name' => $request->recommendation_name,
            'recommendation_class' => $request->recommendation_class,
            'recommendation_alumni' => $request->recommendation_alumni,
        ]);

        // 6. Upload Dokumen Baru / Replace
        $dokumenTypes = ['NISN', 'Akta_Lahir', 'Kartu_Keluarga', 'SKL_Ijazah'];
        foreach ($dokumenTypes as $docType) {
            if ($request->hasFile("doc_$docType")) {
                $file = $request->file("doc_$docType");
                $path = $file->store("dokumen_pendaftar", 'public'); // Sesuaikan disk GDrive jika menggunakan Google Drive

                \App\Models\Document::updateOrCreate(
                    ['registration_id' => $registration->id, 'type' => $docType],
                    ['file_path' => $path, 'status' => 'PENDING'] // Reset status ke PENDING saat diedit admin
                );
            }
        }

        return redirect()->route('admin.pendaftar.index')->with('success', 'Data pendaftar berhasil diperbarui sepenuhnya.');
    }
}
