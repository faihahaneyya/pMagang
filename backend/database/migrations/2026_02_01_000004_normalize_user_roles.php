<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Sesuaikan role pengguna:
     * - 'Finance' → 'KEUANGAN' (normalisasi)
     * - Default role untuk akun baru = 'USER' (bukan 'IT')
     * - Tambah field 'username' (alias untuk 'name') secara logis via kolom baru
     */
    public function up(): void
    {
        // 1. Normalisasi nilai role: Finance → KEUANGAN
        DB::table('users')->where('role', 'Finance')->update(['role' => 'KEUANGAN']);
        DB::table('users')->where('role', 'finance')->update(['role' => 'KEUANGAN']);

        // 2. Normalisasi nilai role: 'it' → 'IT' (case sensitive)
        DB::table('users')->where('role', 'it')->update(['role' => 'IT']);
        DB::table('users')->where('role', 'It')->update(['role' => 'IT']);

        // 3. Ubah default kolom role dari 'IT' menjadi 'USER'
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 50)->default('USER')->change();
        });
    }

    public function down(): void
    {
        // Kembalikan default ke IT
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 50)->default('IT')->change();
        });
        // Kembalikan KEUANGAN → Finance
        DB::table('users')->where('role', 'KEUANGAN')->update(['role' => 'Finance']);
    }
};
