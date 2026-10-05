<?php

namespace App\Http\Controllers;

use App\Models\Inventaris;
use App\Models\Kontrak;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Load all inventaris with kontrak relationship
        $allInventaris = Inventaris::with('kontrak')->get();

        // Card stats
        $totalInventaris = $allInventaris->count();
        $asetAda = $allInventaris->where('status_aset', 'Ada')->count();
        $asetHilang = $allInventaris->where('status_aset', 'Hilang')->count();
        $asetRusak = $allInventaris->where('status_aset', 'Rusak')->count();
        $asetTidakDitemukan = $allInventaris->where('status_aset', 'Tidak Ditemukan')->count();

        $kontrakSedangBerjalan = $allInventaris->filter(function ($inv) {
            return $inv->status_kontrak === 'Sedang Berjalan';
        })->count();

        $kontrakAkanBerakhir = $allInventaris->filter(function ($inv) {
            return $inv->status_kontrak === 'Akan Berakhir';
        })->count();

        $kontrakHabis = $allInventaris->filter(function ($inv) {
            return $inv->status_kontrak === 'Habis';
        })->count();

        $totalKontrak = Kontrak::count();
        $totalBiayaSewa = Kontrak::sum('biaya_sewa');

        // Filter for list view based on card clicked
        $filter = $request->get('filter', 'terbaru');

        $query = Inventaris::with('kontrak')->latest();

        switch ($filter) {
            case 'ada':
                $listInventaris = Inventaris::with('kontrak')
                    ->where('status_aset', 'Ada')->latest()->get();
                $listTitle = 'Daftar Aset Ada';
                break;
            case 'hilang':
                $listInventaris = Inventaris::with('kontrak')
                    ->where('status_aset', 'Hilang')->latest()->get();
                $listTitle = 'Daftar Aset Hilang';
                break;
            case 'rusak':
                $listInventaris = Inventaris::with('kontrak')
                    ->where('status_aset', 'Rusak')->latest()->get();
                $listTitle = 'Daftar Aset Rusak';
                break;
            case 'kontrak_berjalan':
                $listInventaris = Inventaris::with('kontrak')->latest()->get()->filter(function ($inv) {
                    return $inv->status_kontrak === 'Sedang Berjalan';
                })->values();
                $listTitle = 'Daftar Kontrak Sedang Berjalan';
                break;
            case 'kontrak_akan_berakhir':
                $listInventaris = Inventaris::with('kontrak')->latest()->get()->filter(function ($inv) {
                    return $inv->status_kontrak === 'Akan Berakhir';
                })->values();
                $listTitle = 'Daftar Kontrak Akan Berakhir';
                break;
            case 'kontrak_habis':
                $listInventaris = Inventaris::with('kontrak')->latest()->get()->filter(function ($inv) {
                    return $inv->status_kontrak === 'Habis';
                })->values();
                $listTitle = 'Daftar Kontrak Habis';
                break;
            default:
                $listInventaris = Inventaris::with('kontrak')->latest()->limit(10)->get();
                $listTitle = 'Daftar Inventaris Terbaru';
                break;
        }

        return view('dashboard', compact(
            'totalInventaris',
            'asetAda',
            'asetHilang',
            'asetRusak',
            'asetTidakDitemukan',
            'kontrakSedangBerjalan',
            'kontrakAkanBerakhir',
            'kontrakHabis',
            'totalKontrak',
            'totalBiayaSewa',
            'listInventaris',
            'listTitle',
            'filter'
        ));
    }

    public function exportPdf(Request $request)
    {
        $filter = $request->get('filter', 'terbaru');
        [$listInventaris, $listTitle] = $this->getFilteredList($filter);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.dashboard-pdf', [
            'inventaris' => $listInventaris,
            'title' => $listTitle,
            'filter' => $filter,
            'tanggal' => Carbon::now()->isoFormat('D MMMM Y'),
        ]);

        return $pdf->download('dashboard-inventaris.pdf');
    }

    public function exportExcel(Request $request)
    {
        $filter = $request->get('filter', 'terbaru');
        [$listInventaris, $listTitle] = $this->getFilteredList($filter);

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\InventarisExport($listInventaris, $listTitle),
            'dashboard-inventaris.xlsx'
        );
    }

    private function getFilteredList(string $filter): array
    {
        switch ($filter) {
            case 'ada':
                return [Inventaris::with('kontrak')->where('status_aset', 'Ada')->get(), 'Aset Ada'];
            case 'hilang':
                return [Inventaris::with('kontrak')->where('status_aset', 'Hilang')->get(), 'Aset Hilang'];
            case 'kontrak_berjalan':
                return [
                    Inventaris::with('kontrak')->get()->filter(fn($i) => $i->status_kontrak === 'Sedang Berjalan')->values(),
                    'Kontrak Sedang Berjalan',
                ];
            case 'kontrak_akan_berakhir':
                return [
                    Inventaris::with('kontrak')->get()->filter(fn($i) => $i->status_kontrak === 'Akan Berakhir')->values(),
                    'Kontrak Akan Berakhir',
                ];
            case 'kontrak_habis':
                return [
                    Inventaris::with('kontrak')->get()->filter(fn($i) => $i->status_kontrak === 'Habis')->values(),
                    'Kontrak Habis',
                ];
            default:
                return [Inventaris::with('kontrak')->latest()->limit(10)->get(), 'Inventaris Terbaru'];
        }
    }
}
