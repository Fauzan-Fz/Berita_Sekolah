<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    // ── dashboard.index routing per role ──────────────────────────

    public function test_siswa_index_menampilkan_view_siswa(): void
    {
        $user = User::factory()->create(['role' => 'siswa']);
        $this->actingAs($user)->get(route('dashboard.index'))
            ->assertOk()->assertViewIs('dashboard.siswa');
    }

    public function test_guru_index_menampilkan_view_guru(): void
    {
        $user = User::factory()->create(['role' => 'guru']);
        $this->actingAs($user)->get(route('dashboard.index'))
            ->assertOk()->assertViewIs('dashboard.guru');
    }

    public function test_admin_index_menampilkan_view_admin(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user)->get(route('dashboard.index'))
            ->assertOk()->assertViewIs('dashboard.admin');
    }

    public function test_guest_redirect_ke_login_saat_akses_dashboard(): void
    {
        $this->get(route('dashboard.index'))->assertRedirect(route('login'));
    }

    // ── Hak akses per endpoint ────────────────────────────────────

    public function test_siswa_bisa_akses_siswa_tidak_bisa_akses_guru_dan_admin(): void
    {
        $siswa = User::factory()->create(['role' => 'siswa']);
        $this->actingAs($siswa)->get(route('dashboard.siswa'))->assertOk();
        $this->actingAs($siswa)->get(route('dashboard.guru'))->assertForbidden();
        $this->actingAs($siswa)->get(route('dashboard.admin'))->assertForbidden();
        $this->actingAs($siswa)->get(route('dashboard.admin.users'))->assertForbidden();
        $this->actingAs($siswa)->get(route('dashboard.admin.categories'))->assertForbidden();
        $this->actingAs($siswa)->get(route('dashboard.admin.posts'))->assertForbidden();
    }

    public function test_guru_bisa_akses_guru_tidak_bisa_akses_siswa_dan_admin(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $this->actingAs($guru)->get(route('dashboard.guru'))->assertOk();
        $this->actingAs($guru)->get(route('dashboard.siswa'))->assertForbidden();
        $this->actingAs($guru)->get(route('dashboard.admin'))->assertForbidden();
    }

    public function test_admin_bisa_akses_semua_dasbor(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('dashboard.siswa'))->assertOk();
        $this->actingAs($admin)->get(route('dashboard.guru'))->assertOk();
        $this->actingAs($admin)->get(route('dashboard.admin'))->assertOk();
        $this->actingAs($admin)->get(route('dashboard.admin.users'))->assertOk();
        $this->actingAs($admin)->get(route('dashboard.admin.categories'))->assertOk();
        $this->actingAs($admin)->get(route('dashboard.admin.posts'))->assertOk();
    }

    // ── Konten dasbor ─────────────────────────────────────────────

    public function test_siswa_hanya_melihat_post_miliknya(): void
    {
        $cat = Category::create(['name' => 'Umum', 'slug' => 'umum']);
        $siswaA = User::factory()->create(['role' => 'siswa']);
        $siswaB = User::factory()->create(['role' => 'siswa']);

        Post::create(['user_id' => $siswaA->id, 'category_id' => $cat->id, 'title' => 'Milik A', 'slug' => 'milik-a-'.uniqid(), 'content' => '<p>a</p>', 'status' => 'pending']);
        Post::create(['user_id' => $siswaB->id, 'category_id' => $cat->id, 'title' => 'Milik B', 'slug' => 'milik-b-'.uniqid(), 'content' => '<p>b</p>', 'status' => 'pending']);

        $response = $this->actingAs($siswaA)->get(route('dashboard.siswa'));
        $response->assertOk();
        $myPosts = $response->viewData('myPosts');
        $this->assertCount(1, $myPosts);
        $this->assertSame('Milik A', $myPosts->first()->title);
    }

    public function test_guru_melihat_daftar_pending(): void
    {
        $cat = Category::create(['name' => 'Umum2', 'slug' => 'umum2']);
        $siswa = User::factory()->create(['role' => 'siswa']);
        Post::create(['user_id' => $siswa->id, 'category_id' => $cat->id, 'title' => 'Pending Post', 'slug' => 'pending-'.uniqid(), 'content' => '<p>p</p>', 'status' => 'pending']);
        Post::create(['user_id' => $siswa->id, 'category_id' => $cat->id, 'title' => 'Approved Post', 'slug' => 'approved-'.uniqid(), 'content' => '<p>a</p>', 'status' => 'approved', 'published_at' => now()]);

        $guru = User::factory()->create(['role' => 'guru']);
        $response = $this->actingAs($guru)->get(route('dashboard.guru'));
        $response->assertOk();
        $pending = $response->viewData('pending');
        $this->assertCount(1, $pending);
        $this->assertSame('Pending Post', $pending->first()->title);
    }

    public function test_admin_melihat_statistik(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $response = $this->actingAs($admin)->get(route('dashboard.admin'));
        $response->assertOk();
        $stats = $response->viewData('stats');
        $this->assertArrayHasKey('users', $stats);
        $this->assertArrayHasKey('posts', $stats);
        $this->assertArrayHasKey('pending', $stats);
        $this->assertArrayHasKey('published', $stats);
    }

    public function test_profile_update_butuh_autentikasi(): void
    {
        $this->put(route('dashboard.profile.update'), ['name' => 'X'])->assertRedirect(route('login'));
    }
}
