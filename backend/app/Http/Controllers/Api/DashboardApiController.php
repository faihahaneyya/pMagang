<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventaris;
use App\Models\Kontrak;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class DashboardApiController extends Controller
{
    /**
     * GET /api/dashboard/stats
     * Mengembalikan data agregat statistik dashboard dan ringkasan inventaris dalam format JSON.
     */
    public function stats(Request $request): JsonResponse
    {
        $allInventaris = Inventaris::with('kontrak')->get();

        $totalInventaris = $allInventaris->count();
        $asetAda = $allInventaris->where('status_aset', 'Ada')->count();
        $asetHilang = $allInventaris->where('status_aset', 'Hilang')->count();
        $asetRusak = $allInventaris->where('status_aset', 'Rusak')->count();
        $asetTidakDitemukan = $allInventaris->where('status_aset', 'Tidak Ditemukan')->count();

        $kontrakSedangBerjalan = $allInventaris->filter(function ($inv) {
            return in_array($inv->status_kontrak, ['AKTIF', 'Sedang Berjalan']);
        })->count();

        $kontrakAkanBerakhir = $allInventaris->filter(function ($inv) {
            return in_array($inv->status_kontrak, ['AKAN BERAKHIR', 'Akan Berakhir']);
        })->count();

        $kontrakHabis = $allInventaris->filter(function ($inv) {
            return in_array($inv->status_kontrak, ['BERAKHIR', 'Habis']);
        })->count();

        $totalKontrak = Kontrak::count();
        $totalBiayaSewa = (float) Kontrak::sum('biaya_sewa');

        // Filter list items untuk preview ringkasan
        $filter = $request->get('filter', 'terbaru');
        switch ($filter) {
            case 'ada':
                $items = Inventaris::with('kontrak')->where('status_aset', 'Ada')->latest()->limit(10)->get();
                $listTitle = 'Daftar Aset Ada';
                break;
            case 'hilang':
                $items = Inventaris::with('kontrak')->where('status_aset', 'Hilang')->latest()->limit(10)->get();
                $listTitle = 'Daftar Aset Hilang';
                break;
            case 'rusak':
                $items = Inventaris::with('kontrak')->where('status_aset', 'Rusak')->latest()->limit(10)->get();
                $listTitle = 'Daftar Aset Rusak';
                break;
            case 'kontrak_berjalan':
            case 'sewa_aktif':
                $items = Inventaris::with('kontrak')->latest()->get()->filter(function ($inv) {
                    return in_array($inv->status_kontrak, ['AKTIF', 'Sedang Berjalan']);
                })->take(10)->values();
                $listTitle = 'Daftar Inventaris Sewa Aktif';
                break;
            case 'kontrak_akan_berakhir':
            case 'akan_berakhir':
                $items = Inventaris::with('kontrak')->get()->filter(function ($inv) {
                    return in_array($inv->status_kontrak, ['AKAN BERAKHIR', 'Akan Berakhir']);
                })->sortBy(function ($inv) {
                    return $inv->kontrak ? $inv->kontrak->sisa_hari : 9999;
                })->take(10)->values();
                $listTitle = 'Daftar Inventaris Akan Berakhir (Sisa Hari Terkecil)';
                break;
            case 'kontrak_habis':
            case 'sewa_berakhir':
                $items = Inventaris::with('kontrak')->latest()->get()->filter(function ($inv) {
                    return in_array($inv->status_kontrak, ['BERAKHIR', 'Habis']);
                })->take(10)->values();
                $listTitle = 'Daftar Inventaris Sewa Berakhir';
                break;
            default:
                $items = Inventaris::with('kontrak')->latest()->limit(10)->get();
                $listTitle = 'Daftar Inventaris Terbaru';
                break;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_inventaris' => $totalInventaris,
                'aset_ada' => $asetAda,
                'aset_hilang' => $asetHilang,
                'aset_rusak' => $asetRusak,
                'aset_tidak_ditemukan' => $asetTidakDitemukan,
                'sewa_aktif' => $kontrakSedangBerjalan,
                'akan_berakhir' => $kontrakAkanBerakhir,
                'sewa_berakhir' => $kontrakHabis,
                'kontrak_sedang_berjalan' => $kontrakSedangBerjalan,
                'kontrak_akan_berakhir' => $kontrakAkanBerakhir,
                'kontrak_habis' => $kontrakHabis,
                'total_kontrak' => $totalKontrak,
                'total_biaya_sewa' => $totalBiayaSewa,
                'list_title' => $listTitle,
                'filter' => $filter,
                'recent_items' => $items,
            ],
        ]);
    }

    /**
     * GET /api/dashboard/export/pdf
     */
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

    /**
     * GET /api/dashboard/export/excel
     */
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
            case 'sewa_aktif':
                return [
                    Inventaris::with('kontrak')->get()->filter(fn($i) => in_array($i->status_kontrak, ['AKTIF', 'Sedang Berjalan']))->values(),
                    'Inventaris Sewa Aktif',
                ];
            case 'kontrak_akan_berakhir':
            case 'akan_berakhir':
                return [
                    Inventaris::with('kontrak')->get()->filter(fn($i) => in_array($i->status_kontrak, ['AKAN BERAKHIR', 'Akan Berakhir']))
                        ->sortBy(fn($i) => $i->kontrak ? $i->kontrak->sisa_hari : 9999)->values(),
                    'Inventaris Kontrak Akan Berakhir',
                ];
            case 'kontrak_habis':
            case 'sewa_berakhir':
                return [
                    Inventaris::with('kontrak')->get()->filter(fn($i) => in_array($i->status_kontrak, ['BERAKHIR', 'Habis']))->values(),
                    'Inventaris Sewa Berakhir',
                ];
            default:
                return [Inventaris::with('kontrak')->latest()->limit(10)->get(), 'Inventaris Terbaru'];
        }
    }
}
