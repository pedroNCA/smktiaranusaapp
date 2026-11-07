<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SchoolContact;

class ContactController extends Controller
{
    public function adminIndex()
    {
        // Mengambil baris pertama, atau membuat instance baru jika kosong
        $contact = SchoolContact::firstOrNew(['id' => 1]);
        return view('admin.contacts', compact('contact'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'email' => 'nullable|email',
        ]);

        $data = $request->all();

        // Jika nomor telepon diisi, bersihkan spasi/simbol dan ubah format 08... menjadi 8...
        if (!empty($data['phone'])) {
            // Hanya ambil angka saja
            $cleanPhone = preg_replace('/[^0-9]/', '', $data['phone']);

            // Jika diawali angka 0, buang angka 0 di depan
            if (str_starts_with($cleanPhone, '0')) {
                $cleanPhone = substr($cleanPhone, 1);
            }

            $data['phone'] = $cleanPhone;
        }

        $contact = SchoolContact::firstOrNew(['id' => 1]);
        $contact->fill($data);
        $contact->save();

        return back()->with('success', 'Informasi kontak dan sosial media berhasil diperbarui!');
    }
}
