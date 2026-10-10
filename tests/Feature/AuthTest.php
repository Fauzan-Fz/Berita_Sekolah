<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // ── Register ─────────────────────────────────────────────────────

    public function test_register_siswa_berhasil_dan_redirect_ke_siswa(): void
    {
        $response = $this->post(route('register.attempt'), [
            'name' => 'Siswa Baru',
            'email' => 'siswa-baru@sekolah.sch.id',
            'role' => 'siswa',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('dashboard.siswa'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'siswa-baru@sekolah.sch.id', 'role' => 'siswa']);
    }

    public function test_register_guru_berhasil_dan_redirect_ke_guru(): void
    {
        $response = $this->post(route('register.attempt'), [
            'name' => 'Guru Baru',
            'email' => 'guru-baru@sekolah.sch.id',
            'role' => 'guru',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('dashboard.guru'));
        $this->assertAuthenticated();
    }

    public function test_register_tidak_boleh_role_admin(): void
    {
        $response = $this->post(route('register.attempt'), [
            'name' => 'Calon Admin',
            'email' => 'admin-baru@sekolah.sch.id',
            'role' => 'admin',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertGuest();
    }

    public function test_register_gagal_jika_email_duplikat(): void
    {
        User::factory()->create(['email' => 'duplikat@sekolah.sch.id']);

        $this->post(route('register.attempt'), [
            'name' => 'Duplikat',
            'email' => 'duplikat@sekolah.sch.id',
            'role' => 'siswa',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('email');
    }

    public function test_register_gagal_jika_password_tidak_konfirmasi(): void
    {
        $this->post(route('register.attempt'), [
            'name' => 'Tanpa Konfirmasi',
            'email' => 'noconfirm@sekolah.sch.id',
            'role' => 'siswa',
            'password' => 'password',
            'password_confirmation' => 'berbeda',
        ])->assertSessionHasErrors('password');
    }

    public function test_guest_tidak_bisa_akses_register_jika_sudah_login(): void
    {
        $user = User::factory()->create(['role' => 'siswa']);
        $this->actingAs($user)->get(route('register'))->assertRedirect();
    }

    // ── Login ────────────────────────────────────────────────────────

    public function test_login_berhasil_siswa_redirect_siswa(): void
    {
        $user = User::factory()->create(['email' => 'login-siswa@sekolah.sch.id', 'role' => 'siswa']);

        $this->post(route('login.attempt'), [
            'email' => 'login-siswa@sekolah.sch.id',
            'password' => 'password',
        ])->assertRedirect(route('dashboard.siswa'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_berhasil_guru_redirect_guru(): void
    {
        User::factory()->create(['email' => 'login-guru@sekolah.sch.id', 'role' => 'guru']);

        $this->post(route('login.attempt'), [
            'email' => 'login-guru@sekolah.sch.id',
            'password' => 'password',
        ])->assertRedirect(route('dashboard.guru'));
    }

    public function test_login_berhasil_admin_redirect_admin(): void
    {
        User::factory()->create(['email' => 'login-admin@sekolah.sch.id', 'role' => 'admin']);

        $this->post(route('login.attempt'), [
            'email' => 'login-admin@sekolah.sch.id',
            'password' => 'password',
        ])->assertRedirect(route('dashboard.admin'));
    }

    public function test_login_gagal_password_salah(): void
    {
        User::factory()->create(['email' => 'salah-pass@sekolah.sch.id']);

        $this->post(route('login.attempt'), [
            'email' => 'salah-pass@sekolah.sch.id',
            'password' => 'salahsekali',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_gagal_akun_nonaktif(): void
    {
        User::factory()->create(['email' => 'nonaktif@sekolah.sch.id', 'is_active' => false]);

        $this->post(route('login.attempt'), [
            'email' => 'nonaktif@sekolah.sch.id',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_dengan_remember_me(): void
    {
        User::factory()->create(['email' => 'remember@sekolah.sch.id']);

        $this->post(route('login.attempt'), [
            'email' => 'remember@sekolah.sch.id',
            'password' => 'password',
            'remember' => true,
        ])->assertRedirect();

        $this->assertAuthenticated();
    }

    // ── Logout ───────────────────────────────────────────────────────

    public function test_logout_mengakhiri_sesi_dan_redirect_home(): void
    {
        $user = User::factory()->create(['role' => 'siswa']);
        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_guest_tidak_bisa_logout(): void
    {
        $this->post(route('logout'))->assertRedirect(route('login'));
    }

    public function test_guest_tidak_bisa_akses_login_jika_sudah_login(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user)->get(route('login'))->assertRedirect();
    }
}
