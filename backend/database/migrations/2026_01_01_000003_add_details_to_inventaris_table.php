<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventaris', function (Blueprint $table) {
            if (!Schema::hasColumn('inventaris', 'merek')) {
                $table->string('merek', 100)->nullable()->after('kategori');
            }
            if (!Schema::hasColumn('inventaris', 'spesifikasi')) {
                $table->text('spesifikasi')->nullable()->after('merek');
            }
            if (!Schema::hasColumn('inventaris', 'foto')) {
                $table->string('foto', 255)->nullable()->after('keterangan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventaris', function (Blueprint $table) {
            if (Schema::hasColumn('inventaris', 'merek')) {
                $table->dropColumn('merek');
            }
            if (Schema::hasColumn('inventaris', 'spesifikasi')) {
                $table->dropColumn('spesifikasi');
            }
        });
    }
};
