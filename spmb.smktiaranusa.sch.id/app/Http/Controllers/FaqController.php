<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Faq;

class FaqController extends Controller
{
    public function adminIndex()
    {
        $faqs = Faq::orderBy('category', 'asc')->get();
        return view('admin.faqs', compact('faqs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:255',
            'category' => 'required|in:Calon Siswa,Orang Tua,Alumni,Umum',
            'answer' => 'required|string',
        ]);

        Faq::create($request->all());
        return back()->with('success', 'FAQ berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'question' => 'required|string|max:255',
            'category' => 'required|in:Calon Siswa,Orang Tua,Alumni,Umum',
            'answer' => 'required|string',
        ]);

        Faq::findOrFail($id)->update($request->all());
        return back()->with('success', 'FAQ berhasil diperbarui!');
    }

    public function destroy($id)
    {
        Faq::findOrFail($id)->delete();
        return back()->with('success', 'FAQ berhasil dihapus!');
    }
}
