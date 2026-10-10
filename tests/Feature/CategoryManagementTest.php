<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    // ── Store ──────────────────────────────────────────────────────

    public function test_admin_bisa_membuat_kategori(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('dashboard.categories.store'), [
            'name' => 'Teknologi Baru',
        ])->assertRedirect();

        $this->assertDatabaseHas('categories', ['name' => 'Teknologi Baru', 'slug' => 'teknologi-baru']);
    }

    public function test_kategori_slug_dibuat_otomatis(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('dashboard.categories.store'), ['name' => 'Karya Siswa Hebat']);
        $cat = Category::firstWhere('name', 'Karya Siswa Hebat');
        $this->assertSame('karya-siswa-hebat', $cat->slug);
    }

    public function test_nama_kategori_unik(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Category::create(['name' => 'Olahraga', 'slug' => 'olahraga']);

        $this->actingAs($admin)->post(route('dashboard.categories.store'), [
            'name' => 'Olahraga',
        ])->assertSessionHasErrors('name');
    }

    public function test_validasi_nama_wajib(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('dashboard.categories.store'), [
            'name' => '',
        ])->assertSessionHasErrors('name');
    }

    public function test_siswa_tidak_bisa_membuat_kategori(): void
    {
        $siswa = User::factory()->create(['role' => 'siswa']);
        $this->actingAs($siswa)->post(route('dashboard.categories.store'), [
            'name' => 'Coba Kategori',
        ])->assertForbidden();
    }

    public function test_guru_tidak_bisa_membuat_kategori(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $this->actingAs($guru)->post(route('dashboard.categories.store'), [
            'name' => 'Coba Kategori',
        ])->assertForbidden();
    }

    public function test_guest_tidak_bisa_membuat_kategori(): void
    {
        $this->post(route('dashboard.categories.store'), ['name' => 'x'])->assertRedirect(route('login'));
    }

    // ── Update ─────────────────────────────────────────────────────

    public function test_admin_bisa_update_kategori(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cat = Category::create(['name' => 'Lama', 'slug' => 'lama']);

        $this->actingAs($admin)->put(route('dashboard.categories.update', $cat), [
            'name' => 'Baru Diupdate',
        ])->assertRedirect();

        $cat->refresh();
        $this->assertSame('Baru Diupdate', $cat->name);
        $this->assertSame('baru-diupdate', $cat->slug);
    }

    public function test_update_kategori_nama_unik_kecuali_milik_sendiri(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cat1 = Category::create(['name' => 'Satu', 'slug' => 'satu']);
        $cat2 = Category::create(['name' => 'Dua', 'slug' => 'dua']);

        // update cat1 dengan nama sama (milik sendiri) — harus lolos
        $this->actingAs($admin)->put(route('dashboard.categories.update', $cat1), [
            'name' => 'Satu',
        ])->assertRedirect()->assertSessionHasNoErrors();

        // update cat1 dengan nama cat2 — harus gagal unique
        $this->actingAs($admin)->put(route('dashboard.categories.update', $cat1), [
            'name' => 'Dua',
        ])->assertSessionHasErrors('name');
    }

    public function test_siswa_tidak_bisa_update_kategori(): void
    {
        $siswa = User::factory()->create(['role' => 'siswa']);
        $cat = Category::create(['name' => 'Halo', 'slug' => 'halo']);
        $this->actingAs($siswa)->put(route('dashboard.categories.update', $cat), [
            'name' => 'Hack',
        ])->assertForbidden();
    }

    // ── Destroy ────────────────────────────────────────────────────

    public function test_admin_bisa_hapus_kategori_kosong(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cat = Category::create(['name' => 'Kosong', 'slug' => 'kosong']);

        $this->actingAs($admin)->delete(route('dashboard.categories.destroy', $cat))->assertRedirect();
        $this->assertDatabaseMissing('categories', ['id' => $cat->id]);
    }

    public function test_tidak_bisa_hapus_kategori_yang_masih_punya_post(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cat = Category::create(['name' => 'Berisi', 'slug' => 'berisi']);
        Post::create([
            'user_id' => $admin->id, 'category_id' => $cat->id,
            'title' => 'Berita Dalam Kategori', 'slug' => 'berita-dalam-kategori-'.uniqid(),
            'content' => '<p>x</p>', 'status' => 'pending',
        ]);

        $this->actingAs($admin)->delete(route('dashboard.categories.destroy', $cat))
            ->assertRedirect()
            ->assertSessionHasErrors('category');

        $this->assertDatabaseHas('categories', ['id' => $cat->id]);
    }

    public function test_siswa_tidak_bisa_hapus_kategori(): void
    {
        $siswa = User::factory()->create(['role' => 'siswa']);
        $cat = Category::create(['name' => 'Jangan Hapus', 'slug' => 'jangan-hapus']);
        $this->actingAs($siswa)->delete(route('dashboard.categories.destroy', $cat))->assertForbidden();
    }

    public function test_guest_tidak_bisa_hapus_kategori(): void
    {
        $cat = Category::create(['name' => 'Guest Hapus', 'slug' => 'guest-hapus']);
        $this->delete(route('dashboard.categories.destroy', $cat))->assertRedirect(route('login'));
    }
}
