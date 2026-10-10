<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    // ── updateRole ─────────────────────────────────────────────────

    public function test_admin_bisa_ubah_role_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $siswa = User::factory()->create(['role' => 'siswa']);

        $this->actingAs($admin)->post(route('dashboard.users.role', $siswa), [
            'role' => 'guru',
        ])->assertRedirect();

        $this->assertSame('guru', $siswa->fresh()->role);
    }

    public function test_admin_tidak_bisa_turunkan_role_sendiri(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('dashboard.users.role', $admin), [
            'role' => 'siswa',
        ])->assertSessionHasErrors('role');

        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_admin_bisa_set_role_admin_untuk_user_lain(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($admin)->post(route('dashboard.users.role', $guru), [
            'role' => 'admin',
        ])->assertRedirect();

        $this->assertSame('admin', $guru->fresh()->role);
    }

    public function test_role_tidak_valid_ditolak(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $siswa = User::factory()->create(['role' => 'siswa']);

        $this->actingAs($admin)->post(route('dashboard.users.role', $siswa), [
            'role' => 'superadmin',
        ])->assertSessionHasErrors('role');
    }

    public function test_siswa_tidak_bisa_ubah_role(): void
    {
        $siswa = User::factory()->create(['role' => 'siswa']);
        $target = User::factory()->create(['role' => 'siswa']);

        $this->actingAs($siswa)->post(route('dashboard.users.role', $target), [
            'role' => 'admin',
        ])->assertForbidden();
    }

    public function test_guru_tidak_bisa_ubah_role(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $target = User::factory()->create(['role' => 'siswa']);

        $this->actingAs($guru)->post(route('dashboard.users.role', $target), [
            'role' => 'admin',
        ])->assertForbidden();
    }

    public function test_guest_tidak_bisa_ubah_role(): void
    {
        $target = User::factory()->create(['role' => 'siswa']);
        $this->post(route('dashboard.users.role', $target), ['role' => 'guru'])
            ->assertRedirect(route('login'));
    }

    // ── toggleActive ───────────────────────────────────────────────

    public function test_admin_bisa_toggle_status_aktif_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $siswa = User::factory()->create(['role' => 'siswa', 'is_active' => true]);

        $this->actingAs($admin)->post(route('dashboard.users.toggle', $siswa))->assertRedirect();
        $this->assertFalse($siswa->fresh()->is_active);

        $this->actingAs($admin)->post(route('dashboard.users.toggle', $siswa))->assertRedirect();
        $this->assertTrue($siswa->fresh()->is_active);
    }

    public function test_admin_tidak_bisa_toggle_akun_sendiri(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)->post(route('dashboard.users.toggle', $admin))
            ->assertSessionHasErrors('user');

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_siswa_tidak_bisa_toggle_status(): void
    {
        $siswa = User::factory()->create(['role' => 'siswa']);
        $target = User::factory()->create(['role' => 'siswa']);

        $this->actingAs($siswa)->post(route('dashboard.users.toggle', $target))->assertForbidden();
    }

    public function test_guest_tidak_bisa_toggle(): void
    {
        $target = User::factory()->create(['role' => 'siswa']);
        $this->post(route('dashboard.users.toggle', $target))->assertRedirect(route('login'));
    }

    // ── destroy ────────────────────────────────────────────────────

    public function test_admin_bisa_hapus_user_lain(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $siswa = User::factory()->create(['role' => 'siswa']);

        $this->actingAs($admin)->delete(route('dashboard.users.destroy', $siswa))->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $siswa->id]);
    }

    public function test_admin_tidak_bisa_hapus_akun_sendiri(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->delete(route('dashboard.users.destroy', $admin))
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_siswa_tidak_bisa_hapus_user(): void
    {
        $siswa = User::factory()->create(['role' => 'siswa']);
        $target = User::factory()->create(['role' => 'siswa']);

        $this->actingAs($siswa)->delete(route('dashboard.users.destroy', $target))->assertForbidden();
    }

    public function test_guru_tidak_bisa_hapus_user(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $target = User::factory()->create(['role' => 'siswa']);

        $this->actingAs($guru)->delete(route('dashboard.users.destroy', $target))->assertForbidden();
    }

    public function test_guest_tidak_bisa_hapus_user(): void
    {
        $target = User::factory()->create(['role' => 'siswa']);
        $this->delete(route('dashboard.users.destroy', $target))->assertRedirect(route('login'));
    }

    // ── Gambar / akses dashboard admin guard ───────────────────────

    public function test_hanya_admin_bisa_akses_admin_users(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $this->actingAs($guru)->get(route('dashboard.admin.users'))->assertForbidden();
    }

    public function test_guest_tidak_bisa_akses_admin_users_page(): void
    {
        $this->get(route('dashboard.admin.users'))->assertRedirect(route('login'));
    }
}
