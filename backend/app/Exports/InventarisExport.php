<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use Illuminate\Support\Collection;

class InventarisExport implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    public function __construct(
        protected Collection $inventaris,
        protected string $title = 'Data Inventaris'
    ) {}

    public function collection(): Collection
    {
        return $this->inventaris->map(function ($inv, $index) {
            return [
                'No' => $index + 1,
                'Kode Inventaris' => $inv->kode_inventaris,
                'Nama Barang' => $inv->nama_barang,
                'Kategori' => $inv->kategori ?? '-',
                'Vendor/Supplier' => $inv->vendor ?? '-',
                'Lokasi' => $inv->lokasi ?? '-',
                'Kondisi' => $inv->kondisi ?? '-',
                'Status Aset' => $inv->status_aset,
                'Kode Kontrak' => $inv->kode_kontrak,
                'Status Kontrak' => $inv->status_kontrak,
                'Tanggal Mulai' => $inv->kontrak ? $inv->kontrak->tanggal_mulai->format('d/m/Y') : '-',
                'Tanggal Berakhir' => $inv->kontrak ? $inv->kontrak->tanggal_berakhir->format('d/m/Y') : '-',
                'Durasi' => $inv->kontrak ? $inv->kontrak->durasi : '-',
                'Biaya Sewa (Rp)' => $inv->kontrak ? number_format($inv->kontrak->biaya_sewa, 0, ',', '.') : '-',
                'Keterangan' => $inv->keterangan ?? '-',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'No',
            'Kode Inventaris',
            'Nama Barang',
            'Kategori',
            'Vendor/Supplier',
            'Lokasi',
            'Kondisi',
            'Status Aset',
            'Kode Kontrak',
            'Status Kontrak',
            'Tanggal Mulai',
            'Tanggal Berakhir',
            'Durasi',
            'Biaya Sewa (Rp)',
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
        return $this->title;
    }
}
