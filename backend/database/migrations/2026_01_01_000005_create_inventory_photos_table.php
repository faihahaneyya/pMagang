<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventaris_id')
                ->constrained('inventaris')
                ->cascadeOnDelete();
            $table->string('foto_path', 255);
            $table->string('keterangan_foto', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_photos');
    }
};
