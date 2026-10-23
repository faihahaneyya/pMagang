<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Collection;

class KontrakExport implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    public function __construct(protected Collection $kontrakList) {}

    public function collection(): Collection
    {
        return $this->kontrakList->map(function ($kontrak, $index) {
            return [
                'No' => $index + 1,
                'Kode Kontrak' => $kontrak->kode_kontrak,
                'Vendor' => $kontrak->vendor,
                'Tanggal Mulai' => $kontrak->tanggal_mulai->format('d/m/Y'),
                'Tanggal Berakhir' => $kontrak->tanggal_berakhir->format('d/m/Y'),
                'Durasi' => $kontrak->durasi,
                'Jumlah Inventaris' => $kontrak->inventaris_count ?? $kontrak->inventaris()->count(),
                'Biaya Sewa/Bulan (Rp)' => number_format($kontrak->biaya_sewa, 0, ',', '.'),
                'Total Nilai Kontrak (Rp)' => number_format($kontrak->total_biaya, 0, ',', '.'),
                'Status Kontrak' => $kontrak->status_kontrak,
                'Sisa Kontrak' => $kontrak->sisa_kontrak,
                'Keterangan' => $kontrak->keterangan ?? '-',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'No',
            'Kode Kontrak',
            'Vendor',
            'Tanggal Mulai',
            'Tanggal Berakhir',
            'Durasi',
            'Jumlah Inventaris',
            'Biaya Sewa/Bulan (Rp)',
            'Total Nilai Kontrak (Rp)',
            'Status Kontrak',
            'Sisa Kontrak',
            'Keterangan',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1E3A5F'],
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Data Kontrak';
    }
}
