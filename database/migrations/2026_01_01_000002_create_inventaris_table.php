<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventaris', function (Blueprint $table) {
            $table->id();
            $table->string('kode_inventaris', 50)->unique();
            $table->string('nama_barang', 255);
            $table->string('kategori', 100)->nullable();
            $table->string('vendor', 255)->nullable();
            $table->string('lokasi', 255)->nullable();
            $table->string('kondisi', 100)->nullable(); // Baik / Rusak Ringan / Rusak Berat
            $table->enum('status_aset', ['Ada', 'Hilang', 'Rusak', 'Tidak Ditemukan'])->default('Ada');
            $table->text('keterangan')->nullable();
            $table->string('foto', 255)->nullable();
            $table->foreignId('kontrak_id')->nullable()->constrained('kontrak')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventaris');
    }
};
