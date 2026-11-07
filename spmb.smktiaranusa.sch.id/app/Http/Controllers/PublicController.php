<?php

namespace App\Http\Controllers;

use App\Models\Major;
use App\Models\RegistrationRequirement;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    public function berandaUtama()
    {
        // Langsung mengembalikan view karena beranda.blade.php sudah menggunakan desain statis
        return view('spmb.beranda');
    }

    public function jurusan()
    {
        $majors = Major::all();
        return view('spmb.jurusan', compact('majors'));
    }

    public function biaya()
    {
        $costs = \App\Models\RegistrationCost::all();
        return view('spmb.biaya', compact('costs'));
    }

    public function faq()
    {
        $faqs = \App\Models\Faq::all();
        return view('spmb.faq', compact('faqs'));
    }

    public function informasi()
    {
        $information = null;
        return view('spmb.informasi', compact('information'));
    }

    public function kontak()
    {
        $contact = \App\Models\SchoolContact::first();
        return view('spmb.kontak', compact('contact'));
    }

    public function persyaratan()
    {
        // Ambil semua data persyaratan, urutkan berdasarkan tipe (Umum dulu, lalu Reguler/Beasiswa)
        $requirements = RegistrationRequirement::orderBy('type', 'asc')->get();

        return view('spmb.persyaratan', compact('requirements'));
    }
}
