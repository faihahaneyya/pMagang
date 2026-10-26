<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'it@banyankoalindo.co.id'],
            [
                'name' => 'IT Administrator',
                'role' => 'IT',
                'password' => bcrypt(env('INITIAL_IT_PASSWORD', 'default_it_password_change_me')),
            ]
        );

        User::firstOrCreate(
            ['email' => 'finance@banyankoalindo.co.id'],
            [
                'name' => 'Finance Administrator',
                'role' => 'Finance',
                'password' => bcrypt(env('INITIAL_FINANCE_PASSWORD', 'default_finance_password_change_me')),
            ]
        );

        $this->call([
            InventarisSeeder::class,
        ]);
    }
}
