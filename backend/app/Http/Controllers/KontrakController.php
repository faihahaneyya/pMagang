<?php

namespace App\Http\Controllers;

use App\Models\Kontrak;
use App\Models\Inventaris;
use Illuminate\Http\Request;
use Carbon\Carbon;

class KontrakController extends Controller
{
    public function index(Request $request)
    {
        $kontrakList = Kontrak::withCount('inventaris')->latest()->get();

        // Filter by status
        if ($status = $request->get('status')) {
            $kontrakList = $kontrakList->filter(function ($k) use ($status) {
                if ($status === 'Sedang Berjalan' || $status === 'AKTIF') {
                    return in_array($k->status_kontrak, ['AKTIF', 'Sedang Berjalan']);
                }
                if ($status === 'Akan Berakhir' || $status === 'AKAN BERAKHIR') {
                    return in_array($k->status_kontrak, ['AKAN BERAKHIR', 'Akan Berakhir']);
                }
                if ($status === 'Habis' || $status === 'BERAKHIR') {
                    return in_array($k->status_kontrak, ['BERAKHIR', 'Habis']);
                }
                return $k->status_kontrak === $status;
            })->values();
        }

        if ($search = $request->get('search')) {
            $kontrakList = $kontrakList->filter(function ($k) use ($search) {
                return str_contains(strtolower($k->kode_kontrak), strtolower($search))
                    || str_contains(strtolower($k->vendor), strtolower($search));
            })->values();
        }

        // Stats
        $totalKontrak = Kontrak::count();
        $berjalan = Kontrak::get()->filter(fn($k) => in_array($k->status_kontrak, ['AKTIF', 'Sedang Berjalan']))->count();
        $akanBerakhir = Kontrak::get()->filter(fn($k) => in_array($k->status_kontrak, ['AKAN BERAKHIR', 'Akan Berakhir']))->count();
        $habis = Kontrak::get()->filter(fn($k) => in_array($k->status_kontrak, ['BERAKHIR', 'Habis']))->count();

        return view('kontrak.index', compact('kontrakList', 'totalKontrak', 'berjalan', 'akanBerakhir', 'habis'));
    }

    public function create()
    {
        return view('kontrak.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode_kontrak' => 'required|string|max:50|unique:kontrak,kode_kontrak',
            'vendor' => 'required|string|max:255',
            'tanggal_mulai' => 'required|date',
            'tanggal_berakhir' => 'required|date|after_or_equal:tanggal_mulai',
            'biaya_sewa' => 'nullable|numeric|min:0',
            'keterangan' => 'nullable|string',
        ]);

        $kontrak = Kontrak::create([
            'kode_kontrak' => strtoupper($request->kode_kontrak),
            'vendor' => $request->vendor,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_berakhir' => $request->tanggal_berakhir,
            'durasi' => $request->durasi,
            'biaya_sewa' => $request->biaya_sewa ?? 0,
            'keterangan' => $request->keterangan,
        ]);

        return redirect()->route('kontrak.show', $kontrak->id)
            ->with('success', 'Kontrak berhasil ditambahkan!');
    }

    public function show(Kontrak $kontrak)
    {
        $kontrak->load('inventaris');
        return view('kontrak.show', compact('kontrak'));
    }

    public function edit(Kontrak $kontrak)
    {
        return view('kontrak.edit', compact('kontrak'));
    }

    public function update(Request $request, Kontrak $kontrak)
    {
        $request->validate([
            'kode_kontrak' => 'required|string|max:50|unique:kontrak,kode_kontrak,' . $kontrak->id,
            'vendor' => 'required|string|max:255',
            'tanggal_mulai' => 'required|date',
            'tanggal_berakhir' => 'required|date|after_or_equal:tanggal_mulai',
            'biaya_sewa' => 'nullable|numeric|min:0',
            'keterangan' => 'nullable|string',
        ]);

        $kontrak->update([
            'kode_kontrak' => strtoupper($request->kode_kontrak),
            'vendor' => $request->vendor,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_berakhir' => $request->tanggal_berakhir,
            'durasi' => $request->durasi,
            'biaya_sewa' => $request->biaya_sewa ?? 0,
            'keterangan' => $request->keterangan,
        ]);

        return redirect()->route('kontrak.show', $kontrak->id)
            ->with('success', 'Kontrak berhasil diperbarui!');
    }

    public function destroy(Kontrak $kontrak)
    {
        // Detach inventaris (set kontrak_id to null)
        $kontrak->inventaris()->update(['kontrak_id' => null]);
        $kontrak->delete();

        return redirect()->route('kontrak.index')
            ->with('success', 'Kontrak berhasil dihapus!');
    }

    public function exportPdf(Request $request)
    {
        $kontrakList = Kontrak::withCount('inventaris')->get();

        if ($status = $request->get('status')) {
            $kontrakList = $kontrakList->filter(fn($k) => $k->status_kontrak === $status)->values();
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.kontrak-pdf', [
            'kontrakList' => $kontrakList,
            'title' => 'Laporan Kontrak',
            'tanggal' => Carbon::now()->isoFormat('D MMMM Y'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('kontrak-' . date('Ymd') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $kontrakList = Kontrak::withCount('inventaris')->get();

        if ($status = $request->get('status')) {
            $kontrakList = $kontrakList->filter(fn($k) => $k->status_kontrak === $status)->values();
        }

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\KontrakExport($kontrakList),
            'kontrak-' . date('Ymd') . '.xlsx'
        );
    }
}
