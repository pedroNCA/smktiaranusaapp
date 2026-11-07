<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TemplateSoalExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    public function headings(): array
    {
        return [
            'Mapel', 
            'Pertanyaan', 
            'Option A', 
            'Option B', 
            'Option C', 
            'Option D', 
            'Option E', 
            'Kunci Jawaban'
        ];
    }

    public function array(): array
    {
        return [
            ['Matematika', 'Berapa hasil dari 5 + 5?', '5', '8', '10', '15', '20', 'C'],
            ['Bahasa Indonesia', 'Lawan kata dari Panjang adalah...', 'Besar', 'Pendek', 'Kecil', 'Tinggi', '', 'B'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Baris ke-1 (Header) dibuat cetak tebal (Bold)
            1 => ['font' => ['bold' => true]],
        ];
    }
}