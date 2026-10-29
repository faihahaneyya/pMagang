<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tambahkan kolom vendor_id ke tabel kontrak sebagai FK ke tabel vendors.
     * Kolom vendor string lama TETAP DIPERTAHANKAN untuk kompatibilitas mundur.
     * Otomatis mengisi vendor_id berdasarkan vendor string yang sudah ada.
     */
    public function up(): void
    {
        Schema::table('kontrak', function (Blueprint $table) {
            if (!Schema::hasColumn('kontrak', 'vendor_id')) {
                $table->unsignedBigInteger('vendor_id')->nullable()->after('kode_kontrak');
                $table->foreign('vendor_id')->references('id')->on('vendors')->nullOnDelete();
            }
        });

        // Isi vendor_id berdasarkan nama vendor yang sudah ada
        $kontraks = DB::table('kontrak')->whereNotNull('vendor')->where('vendor', '!=', '')->get();
        foreach ($kontraks as $kontrak) {
            $vendor = DB::table('vendors')->where('name', trim($kontrak->vendor))->first();
            if ($vendor) {
                DB::table('kontrak')->where('id', $kontrak->id)->update(['vendor_id' => $vendor->id]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('kontrak', function (Blueprint $table) {
            if (Schema::hasColumn('kontrak', 'vendor_id')) {
                $table->dropForeign(['vendor_id']);
                $table->dropColumn('vendor_id');
            }
        });
    }
};
