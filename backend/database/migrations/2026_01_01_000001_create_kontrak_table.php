<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kontrak', function (Blueprint $table) {
            $table->id();
            $table->string('kode_kontrak', 50)->unique();
            $table->string('vendor', 255);
            $table->date('tanggal_mulai');
            $table->date('tanggal_berakhir');
            $table->string('durasi', 100)->nullable(); // e.g. "12 Bulan" or auto-computed
            $table->decimal('biaya_sewa', 15, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kontrak');
    }
};
