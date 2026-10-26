<?php

namespace App\Http\Controllers;

use App\Models\Inventaris;
use Illuminate\Http\Request;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Picqer\Barcode\BarcodeGeneratorSVG;

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
     * Download QR Code (2D) image for a specific inventaris.
     * Returns a PNG image of QR Code generated locally via BaconQrCode + PHP GD.
     */
    public function download(string $kode)
    {
        $inventaris = Inventaris::where('kode_inventaris', $kode)->firstOrFail();

        // Generate QR Code PNG binary murni via BaconQrCode + PHP GD lokal
        $imageData = $inventaris->getQrCodePng(300);

        return response($imageData, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="QR-' . $kode . '.png"',
        ]);
    }

    /**
     * Print view for a single QR Code label.
     */
    public function print(string $kode)
    {
        $inventaris = Inventaris::with('kontrak')
            ->where('kode_inventaris', $kode)
            ->firstOrFail();

        $qrCodeSvg = $inventaris->qr_code_svg;
        $barcodeSvg = $qrCodeSvg; // alias kompatibilitas

        return view('barcode.print', compact('inventaris', 'qrCodeSvg', 'barcodeSvg'));
    }

    /**
     * Print multiple QR Code labels at once.
     */
    public function printAll(Request $request)
    {
        $kodes = $request->get('kodes', []);
        if (is_string($kodes)) {
            $kodes = array_filter(array_map('trim', explode(',', $kodes)));
        }

        $query = Inventaris::with('kontrak');
        if (!empty($kodes)) {
            $query->whereIn('kode_inventaris', $kodes);
        }

        $inventarisList = $query->orderBy('kode_inventaris')->get();

        $items = $inventarisList->map(function ($inv) {
            return [
                'inventaris' => $inv,
                'qrCodeSvg' => $inv->qr_code_svg,
                'barcodeSvg' => $inv->qr_code_svg,
            ];
        });

        return view('barcode.print-all', compact('items'));
    }
}

