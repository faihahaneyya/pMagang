<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAdminUser extends Command
{
    /**
     * Nama dan signature perintah konsol.
     * Tidak menerima password sebagai argumen/opsi CLI demi mencegah kebocoran pada shell history.
     *
     * @var string
     */
    protected $signature = 'make:admin';

    /**
     * Deskripsi perintah konsol.
     *
     * @var string
     */
    protected $description = 'Buat akun administrator baru secara interaktif dan aman.';

    /**
     * Eksekusi perintah konsol.
     */
    public function handle(): int
    {
        $this->info('====================================================');
        $this->info('  PT BANYAN KOALINDO LESTARI - PEMBUATAN AKUN ADMIN  ');
        $this->info('====================================================');
        $this->newLine();

        // 1. Input Nama Lengkap
        $name = trim((string) $this->ask('Masukkan Nama Lengkap Administrator'));
        if (empty($name)) {
            $this->error('Nama lengkap tidak boleh kosong.');
            return self::FAILURE;
        }

        // 2. Input dan Normalisasi Email
        $rawEmail = (string) $this->ask('Masukkan Alamat Email Administrator');
        $email = strtolower(trim($rawEmail));

        // 3. Input Password Tersembunyi (Masked via $this->secret)
        $password = (string) $this->secret('Masukkan Kata Sandi (Password, minimal 8 karakter)');
        $passwordConfirmation = (string) $this->secret('Ulangi Kata Sandi (Konfirmasi Password)');

        // 4. Validasi Kredensial
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'same:password_confirmation'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.max' => 'Nama lengkap tidak boleh melebihi 255 karakter.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.max' => 'Alamat email tidak boleh melebihi 255 karakter.',
            'email.unique' => 'Alamat email ini sudah terdaftar di sistem.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal harus 8 karakter.',
            'password.same' => 'Konfirmasi kata sandi tidak cocok dengan kata sandi yang dimasukkan.',
        ]);

        if ($validator->fails()) {
            $this->newLine();
            $this->error('Validasi Gagal:');
            foreach ($validator->errors()->all() as $error) {
                $this->line(" - {$error}");
            }
            return self::FAILURE;
        }

        // 5. Konfirmasi Sebelum Menyimpan
        $this->newLine();
        $this->line('Rincian Akun yang Akan Dibuat:');
        $this->line(" - Nama  : {$name}");
        $this->line(" - Email : {$email}");
        $this->line(" - Sandi : [TERSEMBUNYI / AKAN DI-HASH]");
        $this->newLine();

        if (! $this->confirm('Lanjutkan pembuatan akun administrator ini?', true)) {
            $this->warn('Pembuatan akun dibatalkan.');
            return self::SUCCESS;
        }

        // 6. Simpan Akun Menggunakan Model User & Hashing Bawaan Laravel
        try {
            // Casting 'password' => 'hashed' pada App\Models\User otomatis mengenkripsi password dengan Bcrypt.
            // Kolom email_verified_at dibiarkan null karena tidak disyaratkan oleh alur sistem saat ini.
            User::create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ]);

            $this->newLine();
            $this->info("Sukses! Akun administrator untuk email '{$email}' berhasil dibuat.");
            $this->line('Silakan gunakan kredensial tersebut untuk masuk melalui form login di http://localhost:5173/');
            $this->newLine();

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->newLine();
            $this->error('Terjadi kesalahan saat menyimpan ke basis data: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
