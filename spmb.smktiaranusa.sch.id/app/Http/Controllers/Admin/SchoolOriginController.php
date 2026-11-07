<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SchoolOriginController extends Controller
{
    /**
     * Menampilkan daftar rekap asal sekolah berserta jumlah siswanya.
     */
    public function index(Request $request)
    {
        $search = $request->search;

        $schools = Student::select('school_origin', DB::raw('count(*) as total_students'))
            ->whereNotNull('school_origin')
            ->when($search, function($q) use ($search) {
                return $q->where('school_origin', 'like', "%{$search}%");
            })
            ->groupBy('school_origin')
            ->orderByDesc('total_students')
            ->paginate(15)
            ->withQueryString();

        return view('admin.asal-sekolah.index', compact('schools', 'search'));
    }

    /**
     * Mengubah nama sekolah secara masal pada seluruh data siswa terkait (Rename).
     */
    public function update(Request $request, $old_name)
    {
        $request->validate(['new_name' => 'required|string']);
        
        Student::where('school_origin', $old_name)->update(['school_origin' => $request->new_name]);
        
        return back()->with('success', 'Nama Asal Sekolah berhasil diperbarui pada seluruh data siswa terkait.');
    }

    /**
     * Menghapus (mengosongkan) data asal sekolah dari siswa terkait.
     */
    public function destroy($name)
    {
        Student::where('school_origin', $name)->update(['school_origin' => null]);
        
        return back()->with('success', 'Data asal sekolah dihapus (dikosongkan dari siswa terkait).');
    }

    /**
     * Export data rekapitulasi asal sekolah ke file Excel.
     */
    public function export()
    {
        $schools = Student::select('school_origin', DB::raw('count(*) as total_students'))
            ->whereNotNull('school_origin')
            ->groupBy('school_origin')
            ->orderByDesc('total_students')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Header
        $sheet->setCellValue('A1', 'Nama Sekolah Asal');
        $sheet->setCellValue('B1', 'Jumlah Pendaftar');
        
        // Styling Header
        $sheet->getStyle('A1:B1')->getFont()->setBold(true);
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);

        // Isi Data
        $row = 2;
        foreach ($schools as $school) {
            $sheet->setCellValue('A' . $row, $school->school_origin);
            $sheet->setCellValue('B' . $row, $school->total_students);
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Rekap_Asal_Sekolah_' . date('Ymd_His') . '.xlsx';

        // Set Header untuk Download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }
}