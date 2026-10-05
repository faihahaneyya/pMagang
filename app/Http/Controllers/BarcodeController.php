<?php

namespace App\Http\Controllers;

use App\Models\Inventaris;
use Illuminate\Http\Request;

class BarcodeController extends Controller
{
    public function index(Request $request)
    {
        $query = Inventaris::with('kontrak');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('kode_inventaris', 'like', "%{$search}%")
                  ->orWhere('nama_barang', 'like', "%{$search}%");
            });
        }

        $inventarisList = $query->latest()->paginate(12)->withQueryString();

        return view('barcode.index', compact('inventarisList'));
    }

    /**
     * Download barcode image for a specific inventaris.
     * Returns a PNG image of the QR code.
     */
    public function download(string $kode)
    {
        $inventaris = Inventaris::where('kode_inventaris', $kode)->firstOrFail();
        $url = url('/inventaris/' . $inventaris->kode_inventaris);

        // Generate QR Code using built-in PHP GD (no extra library needed)
        // We use a Google Charts API compatible URL for QR generation
        // Since we need a downloadable file, we'll redirect to the QR URL
        // For proper download, stream the image from Google Charts (fallback)

        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($url);

        $imageData = @file_get_contents($qrUrl);

        if ($imageData === false) {
            return response()->json(['error' => 'Gagal generate barcode'], 500);
        }

        return response($imageData, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="barcode-' . $kode . '.png"',
        ]);
    }

    /**
     * Print view for a barcode.
     */
    public function print(string $kode)
    {
        $inventaris = Inventaris::with('kontrak')
            ->where('kode_inventaris', $kode)
            ->firstOrFail();

        $url = url('/inventaris/' . $inventaris->kode_inventaris);
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($url);

        return view('barcode.print', compact('inventaris', 'url', 'qrUrl'));
    }

    /**
     * Print multiple barcodes at once.
     */
    public function printAll(Request $request)
    {
        $kodes = $request->get('kodes', []);
        $inventarisList = Inventaris::whereIn('kode_inventaris', $kodes)->get();

        $items = $inventarisList->map(function ($inv) {
            return [
                'inventaris' => $inv,
                'url' => url('/inventaris/' . $inv->kode_inventaris),
                'qrUrl' => 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode(url('/inventaris/' . $inv->kode_inventaris)),
            ];
        });

        return view('barcode.print-all', compact('items'));
    }
}
