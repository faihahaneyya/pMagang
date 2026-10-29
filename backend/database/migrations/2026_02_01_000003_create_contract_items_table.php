<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Buat tabel contract_items sebagai tabel penghubung many-to-many
     * antara kontrak dan inventaris sesuai ERD.
     * Otomatis migrasi data dari kolom kontrak_id di tabel inventaris.
     */
    public function up(): void
    {
        Schema::create('contract_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('inventaris_id');
            $table->timestamps();

            $table->foreign('contract_id')->references('id')->on('kontrak')->cascadeOnDelete();
            $table->foreign('inventaris_id')->references('id')->on('inventaris')->cascadeOnDelete();
            $table->unique(['contract_id', 'inventaris_id']);
        });

        // Migrasi data: salin relasi existing dari inventaris.kontrak_id → contract_items
        $inventarisWithKontrak = DB::table('inventaris')
            ->whereNotNull('kontrak_id')
            ->select('id', 'kontrak_id')
            ->get();

        foreach ($inventarisWithKontrak as $inv) {
            DB::table('contract_items')->insertOrIgnore([
                'contract_id'   => $inv->kontrak_id,
                'inventaris_id' => $inv->id,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_items');
    }
};
