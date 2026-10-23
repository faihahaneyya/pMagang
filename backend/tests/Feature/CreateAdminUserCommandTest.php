<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminUserCommandTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Uji 1: make:admin berhasil membuat user dan meng-hash password dengan aman.
     */
    public function test_make_admin_command_creates_user_successfully(): void
    {
        $testEmail = 'tester.admin.temporary@example.com';

        $this->artisan('make:admin')
            ->expectsQuestion('Masukkan Nama Lengkap Administrator', 'Testing Administrator')
            ->expectsQuestion('Masukkan Alamat Email Administrator', "  {$testEmail}  ") // Uji normalisasi spasi
            ->expectsQuestion('Masukkan Kata Sandi (Password, minimal 8 karakter)', 'SecretP@ssword123')
            ->expectsQuestion('Ulangi Kata Sandi (Konfirmasi Password)', 'SecretP@ssword123')
            ->expectsConfirmation('Lanjutkan pembuatan akun administrator ini?', 'yes')
            ->expectsOutputToContain("Sukses! Akun administrator untuk email '{$testEmail}' berhasil dibuat.")
            ->assertSuccessful();

        // Verifikasi di database dalam transaksi
        $user = User::where('email', $testEmail)->first();
        $this->assertNotNull($user);
        $this->assertEquals('Testing Administrator', $user->name);
        $this->assertEquals($testEmail, $user->email);
        $this->assertNull($user->email_verified_at, 'email_verified_at harus null karena tidak disyaratkan oleh alur sistem.');

        // Verifikasi password TIDAK disimpan dalam bentuk teks biasa dan cocok dengan Hash
        $this->assertNotEquals('SecretP@ssword123', $user->password);
        $this->assertTrue(Hash::check('SecretP@ssword123', $user->password));
    }

    /**
     * Uji 2: make:admin gagal jika format email tidak valid.
     */
    public function test_make_admin_fails_when_email_is_invalid(): void
    {
        $this->artisan('make:admin')
            ->expectsQuestion('Masukkan Nama Lengkap Administrator', 'Testing Administrator')
            ->expectsQuestion('Masukkan Alamat Email Administrator', 'invalid-email-format')
            ->expectsQuestion('Masukkan Kata Sandi (Password, minimal 8 karakter)', 'SecretP@ssword123')
            ->expectsQuestion('Ulangi Kata Sandi (Konfirmasi Password)', 'SecretP@ssword123')
            ->expectsOutputToContain('Format alamat email tidak valid.')
            ->assertFailed();
    }

    /**
     * Uji 3: make:admin gagal jika konfirmasi password tidak cocok.
     */
    public function test_make_admin_fails_when_password_confirmation_mismatches(): void
    {
        $this->artisan('make:admin')
            ->expectsQuestion('Masukkan Nama Lengkap Administrator', 'Testing Administrator')
            ->expectsQuestion('Masukkan Alamat Email Administrator', 'tester.mismatch@example.com')
            ->expectsQuestion('Masukkan Kata Sandi (Password, minimal 8 karakter)', 'SecretP@ssword123')
            ->expectsQuestion('Ulangi Kata Sandi (Konfirmasi Password)', 'DifferentPassword456')
            ->expectsOutputToContain('Konfirmasi kata sandi tidak cocok')
            ->assertFailed();
    }

    /**
     * Uji 4: make:admin gagal jika password kurang dari 8 karakter.
     */
    public function test_make_admin_fails_when_password_is_too_short(): void
    {
        $this->artisan('make:admin')
            ->expectsQuestion('Masukkan Nama Lengkap Administrator', 'Testing Administrator')
            ->expectsQuestion('Masukkan Alamat Email Administrator', 'tester.short@example.com')
            ->expectsQuestion('Masukkan Kata Sandi (Password, minimal 8 karakter)', '12345')
            ->expectsQuestion('Ulangi Kata Sandi (Konfirmasi Password)', '12345')
            ->expectsOutputToContain('Kata sandi minimal harus 8 karakter.')
            ->assertFailed();
    }

    /**
     * Uji 5: make:admin dibatalkan jika pengguna memilih 'no' pada konfirmasi akhir.
     */
    public function test_make_admin_can_be_cancelled(): void
    {
        $testEmail = 'tester.cancelled@example.com';

        $this->artisan('make:admin')
            ->expectsQuestion('Masukkan Nama Lengkap Administrator', 'Testing Administrator')
            ->expectsQuestion('Masukkan Alamat Email Administrator', $testEmail)
            ->expectsQuestion('Masukkan Kata Sandi (Password, minimal 8 karakter)', 'SecretP@ssword123')
            ->expectsQuestion('Ulangi Kata Sandi (Konfirmasi Password)', 'SecretP@ssword123')
            ->expectsConfirmation('Lanjutkan pembuatan akun administrator ini?', 'no')
            ->expectsOutputToContain('Pembuatan akun dibatalkan.')
            ->assertSuccessful();

        $this->assertNull(User::where('email', $testEmail)->first());
    }
}
