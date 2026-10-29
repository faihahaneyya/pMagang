<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Buat tabel vendors dan otomatis migrasi data vendor string
     * dari tabel kontrak ke tabel baru ini.
     */
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->text('address')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 255)->nullable();
            $table->timestamps();
        });

        // Migrasi data: ambil semua vendor unik dari tabel kontrak (string) → tabel vendors
        $existingVendors = DB::table('kontrak')
            ->whereNotNull('vendor')
            ->where('vendor', '!=', '')
            ->distinct()
            ->pluck('vendor');

        foreach ($existingVendors as $vendorName) {
            DB::table('vendors')->insertOrIgnore([
                'name' => trim($vendorName),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
