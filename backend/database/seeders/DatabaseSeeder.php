<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Akun IT Administrator
        User::firstOrCreate(
            ['email' => 'it@banyankoalindo.co.id'],
            [
                'name'     => 'IT Administrator',
                'role'     => 'IT',
                'password' => bcrypt(env('INITIAL_IT_PASSWORD', 'default_it_password_change_me')),
            ]
        );

        // Akun Keuangan (gunakan role KEUANGAN — bukan Finance)
        User::firstOrCreate(
            ['email' => 'keuangan@banyankoalindo.co.id'],
            [
                'name'     => 'Keuangan Administrator',
                'role'     => 'KEUANGAN',
                'password' => bcrypt(env('INITIAL_KEUANGAN_PASSWORD', 'default_keuangan_password_change_me')),
            ]
        );

        // Akun lama Finance — update role ke KEUANGAN jika masih ada
        User::where('email', 'finance@banyankoalindo.co.id')
            ->whereIn('role', ['Finance', 'finance', 'FINANCE'])
            ->update(['role' => 'KEUANGAN']);

        // Akun USER sample (pengguna umum / karyawan)
        User::firstOrCreate(
            ['email' => 'user@banyankoalindo.co.id'],
            [
                'name'     => 'Pengguna Umum',
                'role'     => 'USER',
                'password' => bcrypt(env('INITIAL_USER_PASSWORD', 'default_user_password_change_me')),
            ]
        );

        $this->call([
            InventarisSeeder::class,
        ]);
    }
}
