<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventaris', function (Blueprint $table) {
            if (!Schema::hasColumn('inventaris', 'tipe_model')) {
                $table->string('tipe_model', 150)->nullable()->after('merek');
            }
            if (!Schema::hasColumn('inventaris', 'nomor_seri')) {
                $table->string('nomor_seri', 100)->nullable()->after('spesifikasi');
            }
            if (!Schema::hasColumn('inventaris', 'pic')) {
                $table->string('pic', 150)->nullable()->after('lokasi');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventaris', function (Blueprint $table) {
            if (Schema::hasColumn('inventaris', 'tipe_model')) {
                $table->dropColumn('tipe_model');
            }
            if (Schema::hasColumn('inventaris', 'nomor_seri')) {
                $table->dropColumn('nomor_seri');
            }
            if (Schema::hasColumn('inventaris', 'pic')) {
                $table->dropColumn('pic');
            }
        });
    }
};
