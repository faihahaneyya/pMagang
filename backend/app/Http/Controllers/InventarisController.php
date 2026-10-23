<?php

namespace App\Http\Controllers;

use App\Models\Inventaris;
use App\Models\Kontrak;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class InventarisController extends Controller
{
    public function index(Request $request)
    {
        // Search + filter memakai query yang sama dengan export (getFilteredQuery)
        $query = $this->getFilteredQuery($request);

        // Filter by status_kontrak (computed, must do in PHP)
        $statusKontrakFilter = $request->get('status_kontrak');
        if ($statusKontrakFilter) {
            $allFiltered = $query->get()
                ->filter(function($inv) use ($statusKontrakFilter) {
                    if ($statusKontrakFilter === 'Sedang Berjalan' || $statusKontrakFilter === 'AKTIF') {
                        return in_array($inv->status_kontrak, ['AKTIF', 'Sedang Berjalan']);
                    }
                    if ($statusKontrakFilter === 'Akan Berakhir' || $statusKontrakFilter === 'AKAN BERAKHIR') {
                        return in_array($inv->status_kontrak, ['AKAN BERAKHIR', 'Akan Berakhir']);
                    }
                    if ($statusKontrakFilter === 'Habis' || $statusKontrakFilter === 'BERAKHIR') {
                        return in_array($inv->status_kontrak, ['BERAKHIR', 'Habis']);
                    }
                    return $inv->status_kontrak === $statusKontrakFilter;
                })
                ->values();

            // Paginate manual dari collection yang sudah difilter
            $page = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
            $inventarisList = new \Illuminate\Pagination\LengthAwarePaginator(
                $allFiltered->forPage($page, 10)->values(),
                $allFiltered->count(),
                10,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            $inventarisList = $query->paginate(10)->withQueryString();
        }

        // Live search (AJAX): kirim potongan HTML tabel saja
        if ($request->ajax()) {
            return response()
                ->view('inventaris.partials.table', compact('inventarisList'))
                ->header('Cache-Control', 'no-store');
        }

        // Get distinct values for filters
        $kategoris = Inventaris::distinct()->pluck('kategori')->filter()->sort()->values();
        $vendors = Inventaris::distinct()->pluck('vendor')->filter()->sort()->values();
        $lokasis = Inventaris::distinct()->pluck('lokasi')->filter()->sort()->values();
        $kontrakList = Kontrak::orderBy('kode_kontrak')->get();

        return view('inventaris.index', compact(
            'inventarisList',
            'kategoris',
            'vendors',
            'lokasis',
            'kontrakList'
        ));
    }

    public function create()
    {
        $kontrakList = Kontrak::orderBy('kode_kontrak')->get();
        $kategoris = Inventaris::distinct()->pluck('kategori')->filter()->sort()->values();
        return view('inventaris.create', compact('kontrakList', 'kategoris'));
    }

    public function store(Request $request)
    {
        $rules = [
            'kode_inventaris' => 'required|string|max:50|unique:inventaris,kode_inventaris',
            'nama_barang' => 'required|string|max:255',
            'kategori' => 'nullable|string|max:100',
            'merek' => 'nullable|string|max:100',
            'spesifikasi' => 'nullable|string',
            'vendor' => 'nullable|string|max:255',
            'lokasi' => 'nullable|string|max:255',
            'kondisi' => 'nullable|string|max:100',
            'status_aset' => 'required|in:Ada,Hilang,Rusak,Tidak Ditemukan',
            'keterangan' => 'nullable|string',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'memiliki_kontrak' => 'required|in:ya,tidak',
        ];

        if ($request->memiliki_kontrak === 'ya') {
            $rules['kontrak_id'] = 'nullable|exists:kontrak,id';
            $rules['kode_kontrak_baru'] = 'nullable|string|max:50';
        }

        $validated = $request->validate($rules);

        $data = [
            'kode_inventaris' => strtoupper($request->kode_inventaris),
            'nama_barang' => $request->nama_barang,
            'kategori' => $request->kategori,
            'merek' => $request->merek,
            'spesifikasi' => $request->spesifikasi,
            'vendor' => $request->vendor,
            'lokasi' => $request->lokasi,
            'kondisi' => $request->kondisi,
            'status_aset' => $request->status_aset,
            'keterangan' => $request->keterangan,
            'kontrak_id' => null,
        ];

        // Handle foto upload
        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('inventaris', 'public');
        }

        // Handle kontrak
        if ($request->memiliki_kontrak === 'ya') {
            if ($request->filled('kontrak_id')) {
                $data['kontrak_id'] = $request->kontrak_id;
            } elseif ($request->filled('kode_kontrak_baru')) {
                // Create new kontrak
                $kontrak = Kontrak::create([
                    'kode_kontrak' => strtoupper($request->kode_kontrak_baru),
                    'vendor' => $request->vendor ?? $request->vendor_kontrak,
                    'tanggal_mulai' => $request->tanggal_mulai,
                    'tanggal_berakhir' => $request->tanggal_berakhir,
                    'durasi' => $request->durasi_kontrak,
                    'biaya_sewa' => $request->biaya_sewa ?? 0,
                    'keterangan' => $request->keterangan_kontrak,
                ]);
                $data['kontrak_id'] = $kontrak->id;
            }
        }

        Inventaris::create($data);

        return redirect()->route('inventaris.index')
            ->with('success', 'Inventaris berhasil ditambahkan!');
    }

    public function show(string $kode)
    {
        $inventaris = Inventaris::with('kontrak')
            ->where('kode_inventaris', $kode)
            ->firstOrFail();

        return view('inventaris.show', compact('inventaris'));
    }

    public function scan(Request $request, string $kode)
    {
        $cleanKode = trim($kode);

        // Opsi fallback untuk merender tampilan Blade cadangan bila diminta secara eksplisit
        if ($request->boolean('fallback')) {
            $inventaris = Inventaris::with('kontrak')
                ->where('kode_inventaris', $cleanKode)
                ->first();

            return view('inventaris.scan', [
                'inventaris' => $inventaris,
                'kode' => $cleanKode,
            ]);
        }

        $frontendUrl = rtrim((string) config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')), '/');
        return redirect()->away("{$frontendUrl}/aset/{$cleanKode}");
    }

    public function edit(string $kode)
    {
        $inventaris = Inventaris::with('kontrak')
            ->where('kode_inventaris', $kode)
            ->firstOrFail();
        $kontrakList = Kontrak::orderBy('kode_kontrak')->get();
        $kategoris = Inventaris::distinct()->pluck('kategori')->filter()->sort()->values();
        return view('inventaris.edit', compact('inventaris', 'kontrakList', 'kategoris'));
    }

    public function update(Request $request, string $kode)
    {
        $inventaris = Inventaris::where('kode_inventaris', $kode)->firstOrFail();

        $rules = [
            'kode_inventaris' => 'required|string|max:50|unique:inventaris,kode_inventaris,' . $inventaris->id,
            'nama_barang' => 'required|string|max:255',
            'kategori' => 'nullable|string|max:100',
            'merek' => 'nullable|string|max:100',
            'spesifikasi' => 'nullable|string',
            'vendor' => 'nullable|string|max:255',
            'lokasi' => 'nullable|string|max:255',
            'kondisi' => 'nullable|string|max:100',
            'status_aset' => 'required|in:Ada,Hilang,Rusak,Tidak Ditemukan',
            'keterangan' => 'nullable|string',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'memiliki_kontrak' => 'required|in:ya,tidak',
            'kontrak_id' => 'nullable|exists:kontrak,id',
        ];

        $request->validate($rules);

        $data = [
            'kode_inventaris' => strtoupper($request->kode_inventaris),
            'nama_barang' => $request->nama_barang,
            'kategori' => $request->kategori,
            'merek' => $request->merek,
            'spesifikasi' => $request->spesifikasi,
            'vendor' => $request->vendor,
            'lokasi' => $request->lokasi,
            'kondisi' => $request->kondisi,
            'status_aset' => $request->status_aset,
            'keterangan' => $request->keterangan,
            'kontrak_id' => $request->memiliki_kontrak === 'ya' ? $request->kontrak_id : null,
        ];

        if ($request->hasFile('foto')) {
            if ($inventaris->foto) {
                Storage::disk('public')->delete($inventaris->foto);
            }
            $data['foto'] = $request->file('foto')->store('inventaris', 'public');
        }

        $inventaris->update($data);

        $inventaris->update($data);

        return redirect()->route('inventaris.index')
            ->with('success', 'Inventaris berhasil diperbarui!');
    }

    public function destroy(string $kode)
    {
        $inventaris = Inventaris::where('kode_inventaris', $kode)->firstOrFail();
        if ($inventaris->foto) {
            Storage::disk('public')->delete($inventaris->foto);
        }
        $inventaris->delete();

        return redirect()->route('inventaris.index')
            ->with('success', 'Inventaris berhasil dihapus!');
    }

    public function exportPdf(Request $request)
    {
        $inventarisList = $this->getFilteredQuery($request)->get();

        // Apply status_kontrak filter (sama seperti export Excel)
        if ($sk = $request->get('status_kontrak')) {
            $inventarisList = $inventarisList->filter(function($i) use ($sk) {
                if ($sk === 'Sedang Berjalan' || $sk === 'AKTIF') return in_array($i->status_kontrak, ['AKTIF', 'Sedang Berjalan']);
                if ($sk === 'Akan Berakhir' || $sk === 'AKAN BERAKHIR') return in_array($i->status_kontrak, ['AKAN BERAKHIR', 'Akan Berakhir']);
                if ($sk === 'Habis' || $sk === 'BERAKHIR') return in_array($i->status_kontrak, ['BERAKHIR', 'Habis']);
                return $i->status_kontrak === $sk;
            })->values();
        }

        $title = 'Laporan Inventaris';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.inventaris-pdf', [
            'inventaris' => $inventarisList,
            'title' => $title,
            'tanggal' => Carbon::now()->isoFormat('D MMMM Y'),
            'filters' => $request->only(['search', 'status_aset', 'status_kontrak', 'kategori', 'vendor', 'lokasi']),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('inventaris-' . date('Ymd') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $inventarisList = $this->getFilteredQuery($request)->get();

        // Apply status_kontrak filter
        if ($sk = $request->get('status_kontrak')) {
            $inventarisList = $inventarisList->filter(function($i) use ($sk) {
                if ($sk === 'Sedang Berjalan' || $sk === 'AKTIF') return in_array($i->status_kontrak, ['AKTIF', 'Sedang Berjalan']);
                if ($sk === 'Akan Berakhir' || $sk === 'AKAN BERAKHIR') return in_array($i->status_kontrak, ['AKAN BERAKHIR', 'Akan Berakhir']);
                if ($sk === 'Habis' || $sk === 'BERAKHIR') return in_array($i->status_kontrak, ['BERAKHIR', 'Habis']);
                return $i->status_kontrak === $sk;
            })->values();
        }

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\InventarisExport($inventarisList, 'Data Inventaris'),
            'inventaris-' . date('Ymd') . '.xlsx'
        );
    }

    private function getFilteredQuery(Request $request)
    {
        $query = Inventaris::with('kontrak');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('kode_inventaris', 'like', "%{$search}%")
                  ->orWhere('nama_barang', 'like', "%{$search}%")
                  ->orWhere('vendor', 'like', "%{$search}%")
                  ->orWhere('lokasi', 'like', "%{$search}%")
                  ->orWhereHas('kontrak', fn($kq) => $kq->where('kode_kontrak', 'like', "%{$search}%"));
            });
        }

        if ($sa = $request->get('status_aset')) {
            $query->where('status_aset', $sa);
        }

        if ($kat = $request->get('kategori')) {
            $query->where('kategori', $kat);
        }

        if ($v = $request->get('vendor')) {
            $query->where('vendor', $v);
        }

        if ($l = $request->get('lokasi')) {
            $query->where('lokasi', $l);
        }

        return $query->latest();
    }
}
