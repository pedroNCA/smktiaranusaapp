<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Models\RegistrationPeriod;
use App\Models\Document;
use Illuminate\Http\Request;
use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Illuminate\Support\Collection;

class CetakFormulirController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;
        $period_filter = $request->period_id;

        $periods = RegistrationPeriod::orderBy('start_date', 'desc')->get();

        $registrations = Registration::with(['user', 'student', 'period'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('spmb_id', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($period_filter, function ($query) use ($period_filter) {
                $query->where('period_id', $period_filter);
            })
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.cetak-formulir.index', compact(
            'registrations',
            'periods',
            'search',
            'period_filter'
        ));
    }

    public function cetak($id)
    {
        $registration = Registration::with([
            'user',
            'student.address',
            'student.parents',
            'documents',
        ])->findOrFail($id);

        return $this->generatePDF([$registration]);
    }

    public function cetakBulk(Request $request)
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:registrations,id'],
        ]);

        $registrations = Registration::with([
            'user',
            'student.address',
            'student.parents',
            'documents',
        ])
            ->whereIn('id', $request->ids)
            ->orderBy('id', 'asc')
            ->get();

        return $this->generatePDF($registrations);
    }

    private function generatePDF($registrations)
    {
        // Pastikan data berbentuk Collection agar pluck() dapat digunakan.
        $registrations = collect($registrations);

        // Ambil dokumen berdasarkan registration_id.
        $documents = Document::whereIn(
            'registration_id',
            $registrations->pluck('id')->all()
        )->get()->groupBy('registration_id');

        // PERBAIKAN 1: Ubah public_path menjadi storage_path sesuai lokasi upload Anda
        $fontPath = storage_path('fonts');

        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => [210, 330], // F4

            'margin_left' => 2,
            'margin_right' => 2,
            'margin_top' => 5,
            'margin_bottom' => 0,

            'fontDir' => array_merge($fontDirs, [$fontPath]),

            'fontdata' => $fontData + [
                'calibri' => [
                    'R'  => 'calibri.ttf',
                    'B'  => 'calibrib.ttf',
                    'I'  => 'calibrii.ttf',
                    // PERBAIKAN 2: Sesuaikan nama file Bold-Italic dengan yang ada di cPanel
                    'BI' => 'calibriiz.ttf', 
                ],
            ],

            'default_font' => 'calibri',
            'default_font_size' => 12,
        ]);

        $mpdf->shrink_tables_to_fit = 1;

        $html = view('admin.cetak-formulir.pdf', [
            'registrations' => $registrations,
            'documents' => $documents,
        ])->render();

        $mpdf->WriteHTML($html);

        $mpdf->SetJS('this.print();');

        return $mpdf->Output('Formulir_Pendaftaran.pdf', 'I');
    }
}
