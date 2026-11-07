<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends Controller
{
    public function rekapitulasi()
    {
        // Data Diagram Jurusan
        $jurusan = Registration::select('major', DB::raw('count(*) as total'))->groupBy('major')->pluck('total', 'major');

        // Data Diagram Asal Sekolah (Top 10)
        $sekolah = Student::select('school_origin', DB::raw('count(*) as total'))
            ->whereNotNull('school_origin')->groupBy('school_origin')->orderByDesc('total')->limit(10)->pluck('total', 'school_origin');

        // Data Diagram Jalur Masuk
        $jalur = Registration::select('entry_path', DB::raw('count(*) as total'))
            ->whereNotNull('entry_path')->groupBy('entry_path')->pluck('total', 'entry_path');

        // Data Diagram Gelombang
        $gelombang = Registration::join('registration_periods', 'registrations.period_id', '=', 'registration_periods.id')
            ->select('registration_periods.name', DB::raw('count(*) as total'))
            ->groupBy('registration_periods.name')
            ->pluck('total', 'registration_periods.name');

        return view('admin.laporan.rekapitulasi', compact('jurusan', 'sekolah', 'jalur', 'gelombang'));
    }

    public function exportExcel()
    {
        $registrations = Registration::with(['user', 'student.address', 'student.parents'])->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set Header
        $headers = ['ID SPMB', 'Nama Siswa', 'NISN', 'Jurusan', 'Status', 'Asal Sekolah', 'No WA', 'Alamat Lengkap'];
        foreach (range('A', 'H') as $index => $column) {
            $sheet->setCellValue($column . '1', $headers[$index]);
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $row = 2;
        foreach ($registrations as $reg) {
            $student = $reg->student;
            $address = $student?->address;

            $alamatLengkap = $address ? "{$address->street}, RT {$address->rt}/RW {$address->rw}, {$address->village}, {$address->city}" : '-';

            $sheet->setCellValue('A' . $row, $reg->spmb_id);
            $sheet->setCellValue('B' . $row, $reg->user->name ?? '-');
            $sheet->setCellValue('C' . $row, $student->nisn ?? '-');
            $sheet->setCellValue('D' . $row, $reg->major);
            $sheet->setCellValue('E' . $row, $reg->status);
            $sheet->setCellValue('F' . $row, $student->school_origin ?? '-');
            $sheet->setCellValue('G' . $row, $reg->user->whatsapp ?? '-');
            $sheet->setCellValue('H' . $row, $alamatLengkap);
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Data_Pendaftar_SPMB.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . urlencode($fileName) . '"');
        $writer->save('php://output');
        exit;
    }
}
