<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TpaQuestion;
use App\Models\TpaSetting;
use App\Models\TpaResult;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\IOFactory;

class TpaController extends Controller
{

    // --- PENGATURAN TPA ---
    public function settingsIndex()
    {
        $setting = TpaSetting::firstOrCreate(['id' => 1], ['is_active' => 0, 'duration_minutes' => 60]);
        // Sesuaikan dengan file settings.blade.php di folder observasi
        return view('admin.observasi.settings', compact('setting'));
    }

    public function settingsUpdate(Request $request)
    {
        $setting = TpaSetting::first();
        $setting->update([
            'is_active' => $request->has('is_active') ? 1 : 0,
            'duration_minutes' => $request->duration_minutes
        ]);
        return back()->with('success', 'Pengaturan CBT TPA berhasil disimpan.');
    }

    // --- KELOLA SOAL ---
    public function soalIndex(Request $request)
    {
        $search = $request->search;
        $mapel = $request->mapel;

        $questions = TpaQuestion::when($search, function ($q) use ($search) {
            $q->where('question', 'like', "%{$search}%");
        })
            ->when($mapel, function ($q) use ($mapel) {
                $q->where('mapel', $mapel);
            })
            ->orderBy('id', 'asc') // Diubah ke 'asc' agar urut dari nomor 1 ke bawah dengan normal
            ->paginate(15);

        return view('admin.observasi.soal', compact('questions', 'search', 'mapel'));
    }

    // 2. Proses Import Soal dari file CSV menggunakan native fgetcsv yang stabil

    // Import Soal dari file Excel XLSX
    public function soalImport(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|file|mimes:xlsx|max:5120',
        ], [
            'file_excel.required' => 'Silakan pilih file Excel terlebih dahulu.',
            'file_excel.mimes' => 'Format file harus Excel .xlsx.',
            'file_excel.max' => 'Ukuran file maksimal 5 MB.',
        ]);

        try {
            $file = $request->file('file_excel');

            // Baca file Excel
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();

            // Ambil seluruh data mulai dari baris pertama
            $rows = $sheet->toArray(null, true, true, false);

            $importedCount = 0;
            $errors = [];

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 1;

                // Lewati baris pertama karena merupakan header
                if ($rowNumber === 1) {
                    continue;
                }

                // Pastikan jumlah kolom minimal 8
                $row = array_pad($row, 8, '');

                // Bersihkan nilai setiap kolom
                $row = array_map(function ($value) {
                    return trim((string) ($value ?? ''));
                }, $row);

                // Lewati baris kosong
                if (count(array_filter($row, fn($value) => $value !== '')) === 0) {
                    continue;
                }

                $mapel = $row[0];
                $question = $row[1];
                $optionA = $row[2];
                $optionB = $row[3];
                $optionC = $row[4];
                $optionD = $row[5];
                $optionE = $row[6];
                $correctAnswer = strtoupper($row[7]);

                // Validasi mata pelajaran
                if (!in_array($mapel, [
                    'Matematika',
                    'Bahasa Indonesia',
                    'Bahasa Inggris'
                ], true)) {
                    $errors[] = "Baris {$rowNumber}: Mata pelajaran tidak valid.";
                    continue;
                }

                // Validasi pertanyaan dan pilihan wajib
                if ($question === '' || $optionA === '' || $optionB === '') {
                    $errors[] = "Baris {$rowNumber}: Pertanyaan, pilihan A, dan pilihan B wajib diisi.";
                    continue;
                }

                // Validasi kunci jawaban
                if (!in_array($correctAnswer, ['A', 'B', 'C', 'D', 'E'], true)) {
                    $errors[] = "Baris {$rowNumber}: Kunci jawaban harus A, B, C, D, atau E.";
                    continue;
                }

                // Pastikan pilihan yang menjadi kunci jawaban tersedia
                $options = [
                    'A' => $optionA,
                    'B' => $optionB,
                    'C' => $optionC,
                    'D' => $optionD,
                    'E' => $optionE,
                ];

                if ($options[$correctAnswer] === '') {
                    $errors[] = "Baris {$rowNumber}: Pilihan {$correctAnswer} belum diisi, tetapi digunakan sebagai kunci jawaban.";
                    continue;
                }

                try {
                    TpaQuestion::create([
                        'mapel' => $mapel,
                        'question' => $question,
                        'option_a' => $optionA,
                        'option_b' => $optionB,
                        'option_c' => $optionC,
                        'option_d' => $optionD,
                        'option_e' => $optionE,
                        'correct_answer' => $correctAnswer,
                        'image' => null,
                    ]);

                    $importedCount++;
                } catch (\Throwable $e) {
                    $errors[] = "Baris {$rowNumber}: Gagal menyimpan soal.";
                }
            }

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            if ($importedCount === 0) {
                return back()->with(
                    'error',
                    'Tidak ada soal yang berhasil diimpor. ' . implode(' | ', $errors)
                );
            }

            $message = "Berhasil mengimpor {$importedCount} soal TPA.";

            if (!empty($errors)) {
                $message .= ' Terdapat ' . count($errors)
                    . ' baris yang gagal: ' . implode(' | ', $errors);
            }

            return back()->with(
                empty($errors) ? 'success' : 'warning',
                $message
            );
        } catch (\Throwable $e) {
            return back()->with(
                'error',
                'Gagal membaca file Excel. Pastikan file menggunakan format .xlsx dan sesuai template.'
            );
        }
    }


    // 1. Unduh Template CSV (Otomatis rapi saat dibuka di Microsoft Excel)
    // Unduh Template Soal TPA Excel XLSX
    public function soalTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setTitle('Template Soal TPA');

        // Header kolom
        $headers = [
            'Mapel',
            'Pertanyaan',
            'Option A',
            'Option B',
            'Option C',
            'Option D',
            'Option E',
            'Kunci Jawaban',
        ];

        $sheet->fromArray($headers, null, 'A1');

        // Contoh data
        $examples = [
            [
                'Matematika',
                'Berapa hasil dari 5 + 5?',
                '5',
                '8',
                '10',
                '15',
                '20',
                'C',
            ],
            [
                'Bahasa Indonesia',
                'Lawan kata dari Panjang adalah...',
                'Besar',
                'Pendek',
                'Kecil',
                'Tinggi',
                '',
                'B',
            ],
            [
                'Bahasa Inggris',
                'What is the meaning of Beautiful?',
                'Jelek',
                'Cantik',
                'Kecil',
                'Besar',
                'Panjang',
                'B',
            ],
        ];

        $sheet->fromArray($examples, null, 'A2');

        // Format header
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2563EB'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Border tabel
        $sheet->getStyle('A1:H4')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB'],
                ],
            ],
        ]);

        // Lebar kolom
        $widths = [
            'A' => 22,
            'B' => 45,
            'C' => 25,
            'D' => 25,
            'E' => 25,
            'F' => 25,
            'G' => 25,
            'H' => 20,
        ];

        foreach ($widths as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        // Pengaturan tampilan
        $sheet->getStyle('A1:H4')->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle('A2:H4')->getAlignment()
            ->setWrapText(true);

        $sheet->getRowDimension(1)->setRowHeight(25);

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:H4');

        $filename = 'Template_Soal_TPA.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');

            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function soalStore(Request $request)
    {
        $request->validate([
            'mapel' => 'required',
            'question' => 'required',
            'option_a' => 'required',
            'option_b' => 'required',
            'correct_answer' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $data = $request->except(['_token', 'image']);

        $data['option_c'] = $request->input('option_c') ?? '';
        $data['option_d'] = $request->input('option_d') ?? '';
        $data['option_e'] = $request->input('option_e') ?? '';

        // FIX: Beri nilai default kosong agar database tidak error
        $data['image'] = null;

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('tpa_images', 'public');
        }

        TpaQuestion::create($data);
        return back()->with('success', 'Soal berhasil ditambahkan.');
    }

    public function soalUpdate(Request $request, $id)
    {
        $request->validate([
            'mapel' => 'required',
            'question' => 'required',
            'option_a' => 'required',
            'option_b' => 'required',
            'correct_answer' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $question = TpaQuestion::findOrFail($id);
        $data = $request->except(['_token', '_method', 'image']);

        $data['option_c'] = $request->input('option_c') ?? '';
        $data['option_d'] = $request->input('option_d') ?? '';
        $data['option_e'] = $request->input('option_e') ?? '';

        if ($request->hasFile('image')) {
            if ($question->image && \Illuminate\Support\Facades\Storage::disk('public')->exists($question->image)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($question->image);
            }
            $data['image'] = $request->file('image')->store('tpa_images', 'public');
        }

        $question->update($data);
        return back()->with('success', 'Soal berhasil diperbarui.');
    }

    public function soalDestroy($id)
    {
        TpaQuestion::destroy($id);
        return back()->with('success', 'Soal berhasil dihapus.');
    }

    public function soalBulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:tpa_questions,id'
        ]);

        // Hapus gambar yang berelasi sebelum menghapus soal
        $questions = TpaQuestion::whereIn('id', $request->ids)->get();
        foreach ($questions as $q) {
            if ($q->image && \Illuminate\Support\Facades\Storage::disk('public')->exists($q->image)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($q->image);
            }
        }

        TpaQuestion::destroy($request->ids);
        return back()->with('success', 'Soal yang dipilih berhasil dihapus.');
    }

    // --- HASIL TPA SISWA ---
    public function hasilIndex(Request $request)
    {
        $search = $request->search;
        $results = TpaResult::with(['registration.user', 'registration.student'])
            ->when($search, function ($q) use ($search) {
                $q->whereHas('registration.user', function ($u) use ($search) {
                    $u->where('name', 'like', "%{$search}%");
                });
            })
            ->orderBy('total_score', 'desc')
            ->paginate(15);

        return view('admin.observasi.hasil', compact('results', 'search'));
    }

    public function hasilExport(Request $request)
    {
        $search = $request->search;

        // Ambil seluruh hasil ujian, termasuk data di halaman pagination lain
        $results = TpaResult::with(['registration.user', 'registration.student'])
            ->when($search, function ($q) use ($search) {
                $q->whereHas('registration.user', function ($u) use ($search) {
                    $u->where('name', 'like', "%{$search}%");
                })->orWhereHas('registration', function ($r) use ($search) {
                    $r->where('spmb_id', 'like', "%{$search}%");
                });
            })
            ->orderBy('total_score', 'desc')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setTitle('Hasil CBT TPA');

        // Judul laporan
        $sheet->setCellValue('A1', 'REKAPITULASI HASIL TES POTENSI AKADEMIK');
        $sheet->mergeCells('A1:G1');

        // Header tabel
        $headers = [
            'No',
            'ID SPMB',
            'Nama Peserta',
            'Matematika',
            'Bahasa Indonesia',
            'Bahasa Inggris',
            'Total Skor',
            'Status',
            'Tanggal Mengerjakan'
        ];

        // Header dimulai dari baris 3
        $sheet->fromArray($headers, null, 'A3');

        $row = 4;
        $no = 1;

        foreach ($results as $res) {
            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $res->registration->spmb_id ?? '-');
            $sheet->setCellValue('C' . $row, $res->registration->user->name ?? '-');
            $sheet->setCellValue('D' . $row, (float) $res->score_matematika);
            $sheet->setCellValue('E' . $row, (float) $res->score_b_indonesia);
            $sheet->setCellValue('F' . $row, (float) $res->score_b_inggris);
            $sheet->setCellValue('G' . $row, (float) $res->total_score);
            $sheet->setCellValue('H' . $row, $res->status_kelulusan_tpa ?? '-');

            $tanggal = $res->submitted_at
                ? \Carbon\Carbon::parse($res->submitted_at)->format('d-m-Y H:i')
                : '-';

            $sheet->setCellValue('I' . $row, $tanggal);

            $row++;
        }

        // Format judul
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Format header tabel
        $sheet->getStyle('A3:I3')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2563EB'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Border tabel
        $lastRow = max(3, $row - 1);

        $sheet->getStyle("A3:I{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB'],
                ],
            ],
        ]);

        // Format angka nilai
        if ($lastRow >= 4) {
            $sheet->getStyle("D4:G{$lastRow}")
                ->getNumberFormat()
                ->setFormatCode('0.00');
        }

        // Lebar kolom otomatis
        foreach (range('A', 'I') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $sheet->freezePane('A4');
        $sheet->setAutoFilter("A3:I{$lastRow}");

        $filename = 'Hasil_CBT_TPA_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // Hapus satu hasil ujian
    public function hasilDestroy($id)
    {
        $result = TpaResult::findOrFail($id);
        $result->delete();

        return back()->with('success', 'Hasil ujian berhasil dihapus.');
    }

    // Hapus beberapa hasil ujian sekaligus
    public function hasilBulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:tpa_results,id',
        ], [
            'ids.required' => 'Pilih minimal satu hasil ujian.',
            'ids.min' => 'Pilih minimal satu hasil ujian.',
            'ids.*.exists' => 'Data hasil ujian tidak ditemukan.',
        ]);

        $jumlah = TpaResult::whereIn('id', $request->ids)->delete();

        return back()->with(
            'success',
            "{$jumlah} hasil ujian berhasil dihapus."
        );
    }
}
