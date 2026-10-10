<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SafetyNetTest extends TestCase
{
    use RefreshDatabase;

    private function makeCategory(?string $name = null): Category
    {
        $slug = 'cat-'.uniqid();

        return Category::create(['name' => $name ?? 'Kategori '.uniqid(), 'slug' => $slug]);
    }

    private function makeApprovedPost(array $overrides = []): Post
    {
        $user = $overrides['user'] ?? User::factory()->create(['role' => 'admin']);
        $cat = $overrides['category'] ?? $this->makeCategory();
        unset($overrides['user'], $overrides['category']);

        return Post::create(array_merge([
            'user_id' => $user->id,
            'category_id' => $cat->id,
            'title' => 'Berita Approved '.uniqid(),
            'slug' => 'berita-'.uniqid().'-'.Str::random(5),
            'content' => '<p>Konten approved</p>',
            'status' => 'approved',
            'published_at' => now(),
            'views_count' => rand(10, 1000),
        ], $overrides));
    }

    private function makePendingPost(array $overrides = []): Post
    {
        $user = $overrides['user'] ?? User::factory()->create(['role' => 'siswa']);
        $cat = $overrides['category'] ?? $this->makeCategory();
        unset($overrides['user'], $overrides['category']);

        return Post::create(array_merge([
            'user_id' => $user->id,
            'category_id' => $cat->id,
            'title' => 'Draft Pending '.uniqid(),
            'slug' => 'draft-'.uniqid().'-'.Str::random(5),
            'content' => '<p>Draft</p>',
            'status' => 'pending',
            'published_at' => null,
        ], $overrides));
    }

    private function makeJpeg(string $name = 'cover.jpg'): UploadedFile
    {
        $img = imagecreatetruecolor(40, 40);
        imagefill($img, 0, 0, imagecolorallocate($img, 200, 200, 210));
        $path = sys_get_temp_dir().'/safety-'.bin2hex(random_bytes(4)).'.jpg';
        imagejpeg($img, $path, 80);
        imagedestroy($img);

        return new UploadedFile($path, $name, 'image/jpeg', null, true);
    }

    // ——— Trending fallback (PublicController::landing) ———

    public function test_trending_fallback_mengisi_hingga_5_jika_kurang_dari_5_dalam_7_hari(): void
    {
        $cat = $this->makeCategory();
        $user = User::factory()->create(['role' => 'admin']);

        // 2 post dalam 7 hari terakhir
        $this->makeApprovedPost(['user' => $user, 'category' => $cat, 'published_at' => now()->subDay(), 'views_count' => 100]);
        $this->makeApprovedPost(['user' => $user, 'category' => $cat, 'published_at' => now()->subDays(2), 'views_count' => 90]);
        // 3 post lebih dari 7 hari lalu (fallback pool)
        $this->makeApprovedPost(['user' => $user, 'category' => $cat, 'published_at' => now()->subDays(10), 'views_count' => 500]);
        $this->makeApprovedPost(['user' => $user, 'category' => $cat, 'published_at' => now()->subDays(11), 'views_count' => 400]);
        $this->makeApprovedPost(['user' => $user, 'category' => $cat, 'published_at' => now()->subDays(12), 'views_count' => 300]);

        $response = $this->get(route('home'));
        $response->assertOk();
        $trending = $response->viewData('trending');
        $this->assertCount(5, $trending, 'Trending harus fallback hingga 5 jika pool mencukupi');
    }

    public function test_trending_tidak_melebihi_jumlah_approved_yang_ada(): void
    {
        $cat = $this->makeCategory();
        $user = User::factory()->create(['role' => 'admin']);
        // hanya 2 approved total
        $this->makeApprovedPost(['user' => $user, 'category' => $cat, 'published_at' => now()->subDay()]);
        $this->makeApprovedPost(['user' => $user, 'category' => $cat, 'published_at' => now()->subDays(2)]);

        $response = $this->get(route('home'));
        $response->assertOk();
        $trending = $response->viewData('trending');
        // trending tidak boleh melebihi 2 karena hanya 2 approved tersedia
        // (1 featured di-shift dari latest, tapi trending dihitung terpisah)
        $this->assertLessThanOrEqual(2, $trending->count());
    }

    public function test_landing_tetap_200_dengan_satu_post_featured_tidak_error(): void
    {
        $this->makeApprovedPost();
        $response = $this->get(route('home'));
        $response->assertOk();
        $this->assertNotNull($response->viewData('featured'));
    }

    public function test_landing_tanpa_post_featured_null_tidak_error(): void
    {
        $response = $this->get(route('home'));
        $response->assertOk();
        $this->assertNull($response->viewData('featured'));
        $trending = $response->viewData('trending');
        $this->assertCount(0, $trending);
    }

    public function test_trending_tidak_duplikat_dengan_fallback(): void
    {
        $cat = $this->makeCategory();
        $user = User::factory()->create(['role' => 'admin']);
        for ($i = 0; $i < 6; $i++) {
            $this->makeApprovedPost(['user' => $user, 'category' => $cat, 'published_at' => now()->subDays($i), 'views_count' => 100 - $i]);
        }

        $response = $this->get(route('home'));
        $trending = $response->viewData('trending');
        $this->assertCount(5, $trending);
        $ids = $trending->pluck('id')->toArray();
        $this->assertCount(5, array_unique($ids), 'Trending tidak boleh ada duplikat ID');
    }

    // ——— Cover fallback (post tanpa cover_image) ———

    public function test_landing_tetap_render_jika_post_tanpa_cover(): void
    {
        $this->makeApprovedPost(['cover_image' => null]);
        $this->get(route('home'))->assertOk();
    }

    public function test_news_show_tetap_render_jika_post_tanpa_cover(): void
    {
        $post = $this->makeApprovedPost(['cover_image' => null]);
        $this->get(route('news.show', $post->slug))->assertOk();
    }

    // ——— Related safety-net ———

    public function test_related_hanya_approved_tidak_termasuk_pending_atau_rejected(): void
    {
        $cat = $this->makeCategory();
        $user = User::factory()->create(['role' => 'admin']);
        $main = $this->makeApprovedPost(['user' => $user, 'category' => $cat]);
        $approvedPeer = $this->makeApprovedPost(['user' => $user, 'category' => $cat]);
        $pendingPeer = $this->makePendingPost(['user' => $user, 'category' => $cat]);
        $rejectedPeer = Post::create([
            'user_id' => $user->id, 'category_id' => $cat->id,
            'title' => 'Rejected '.uniqid(), 'slug' => 'rejected-'.uniqid(),
            'content' => '<p>x</p>', 'status' => 'rejected', 'rejection_reason' => 'Alasan',
        ]);

        $response = $this->get(route('news.show', $main->slug));
        $related = $response->viewData('related');
        $this->assertTrue($related->contains(fn ($p) => $p->id === $approvedPeer->id));
        $this->assertFalse($related->contains(fn ($p) => $p->id === $pendingPeer->id));
        $this->assertFalse($related->contains(fn ($p) => $p->id === $rejectedPeer->id));
        $this->assertFalse($related->contains(fn ($p) => $p->id === $main->id));
    }

    public function test_related_maksimal_3(): void
    {
        $cat = $this->makeCategory();
        $user = User::factory()->create(['role' => 'admin']);
        $main = $this->makeApprovedPost(['user' => $user, 'category' => $cat]);
        for ($i = 0; $i < 5; $i++) {
            $this->makeApprovedPost(['user' => $user, 'category' => $cat]);
        }

        $related = $this->get(route('news.show', $main->slug))->viewData('related');
        $this->assertLessThanOrEqual(3, $related->count());
    }

    public function test_related_kosong_jika_tidak_ada_sekategori(): void
    {
        $catA = $this->makeCategory('Cat A');
        $catB = $this->makeCategory('Cat B');
        $user = User::factory()->create(['role' => 'admin']);
        $main = $this->makeApprovedPost(['user' => $user, 'category' => $catA]);
        $this->makeApprovedPost(['user' => $user, 'category' => $catB]);

        $related = $this->get(route('news.show', $main->slug))->viewData('related');
        $this->assertCount(0, $related);
    }

    // ——— Slug 404 safety-net ———

    public function test_berita_slug_tidak_ada_404(): void
    {
        $this->get(route('news.show', 'slug-tidak-ada-'.uniqid()))->assertNotFound();
    }

    public function test_kategori_slug_tidak_ada_404(): void
    {
        $this->get(route('category', 'kategori-tidak-ada-'.uniqid()))->assertNotFound();
    }

    public function test_tag_slug_tidak_ada_404(): void
    {
        $this->get(route('tag', 'tag-tidak-ada-'.uniqid()))->assertNotFound();
    }

    // ——— Empty category/tag ———

    public function test_kategori_valid_tanpa_berita_tetap_200_kosong(): void
    {
        $cat = $this->makeCategory('Kosong');
        $this->get(route('category', $cat->slug))->assertOk()->assertViewIs('public.category');
    }

    public function test_tag_valid_tanpa_berita_tetap_200_kosong(): void
    {
        $tag = Tag::create(['name' => 'tagkosong'.uniqid(), 'slug' => 'tagkosong-'.uniqid()]);
        $this->get(route('tag', $tag->slug))->assertOk()->assertViewIs('public.tag');
    }

    // ——— Role middleware safety-net ———

    public function test_guest_ke_route_terproteksi_redirect_login_bukan_403(): void
    {
        $cat = $this->makeCategory();
        $post = $this->makePendingPost(['category' => $cat]);

        // guest ke route role-protected harus redirect login (via auth), bukan 403
        $this->post(route('dashboard.posts.store'), [
            'title' => 'x', 'category_id' => $cat->id, 'content' => '<p>x</p>',
        ])->assertRedirect(route('login'));

        $this->post(route('dashboard.posts.approve', $post))->assertRedirect(route('login'));
        $this->post(route('dashboard.categories.store'), ['name' => 'x'])->assertRedirect(route('login'));
    }

    public function test_authenticated_wrong_role_dapat_403_bukan_redirect(): void
    {
        $siswa = User::factory()->create(['role' => 'siswa']);
        $cat = $this->makeCategory();
        $post = $this->makePendingPost(['category' => $cat]);

        // siswa ke route guru/admin harus 403
        $this->actingAs($siswa)->post(route('dashboard.posts.approve', $post))->assertForbidden();
        $this->actingAs($siswa)->post(route('dashboard.categories.store'), ['name' => 'x'])->assertForbidden();
        $this->actingAs($siswa)->post(route('dashboard.users.role', $siswa), ['role' => 'guru'])->assertForbidden();
    }

    // ——— is_active safety-net ———

    public function test_akun_dinonaktifkan_tidak_bisa_login_walaupun_password_benar(): void
    {
        User::factory()->create(['email' => 'nonaktif2@sekolah.sch.id', 'is_active' => false]);
        $this->post(route('login.attempt'), [
            'email' => 'nonaktif2@sekolah.sch.id',
            'password' => 'password',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    // ——— ImageStore / cover safety-net ———

    public function test_hapus_post_tanpa_cover_tidak_error(): void
    {
        Storage::fake('public');
        $siswa = User::factory()->create(['role' => 'siswa']);
        $cat = $this->makeCategory();
        $post = Post::create([
            'user_id' => $siswa->id, 'category_id' => $cat->id,
            'title' => 'Tanpa Cover', 'slug' => 'tanpa-cover-'.uniqid(),
            'content' => '<p>x</p>', 'status' => 'pending', 'cover_image' => null,
        ]);

        $this->actingAs($siswa)->delete(route('dashboard.posts.destroy', $post))->assertRedirect();
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_update_tanpa_cover_baru_tidak_menghapus_cover_lama(): void
    {
        Storage::fake('public');
        $siswa = User::factory()->create(['role' => 'siswa']);
        $cat = $this->makeCategory();
        Storage::disk('public')->put('cover/lama-aman.webp', 'fake');
        $post = Post::create([
            'user_id' => $siswa->id, 'category_id' => $cat->id,
            'title' => 'Cover Aman', 'slug' => 'cover-aman-'.uniqid(),
            'content' => '<p>x</p>', 'status' => 'pending', 'cover_image' => 'cover/lama-aman.webp',
        ]);

        $this->actingAs($siswa)->put(route('dashboard.posts.update', $post), [
            'title' => 'Cover Aman Updated',
            'category_id' => $cat->id,
            'content' => '<p>y</p>',
        ])->assertRedirect();

        $post->refresh();
        $this->assertSame('cover/lama-aman.webp', $post->cover_image);
        Storage::disk('public')->assertExists('cover/lama-aman.webp');
    }

    public function test_validasi_cover_harus_image(): void
    {
        Storage::fake('public');
        $siswa = User::factory()->create(['role' => 'siswa']);
        $cat = $this->makeCategory();
        $txt = UploadedFile::fake()->create('dokumen.txt', 10, 'text/plain');

        $this->actingAs($siswa)->post(route('dashboard.posts.store'), [
            'title' => 'Cover Bukan Gambar',
            'category_id' => $cat->id,
            'content' => '<p>x</p>',
            'cover_image' => $txt,
        ])->assertSessionHasErrors('cover_image');
    }

    public function test_validasi_tags_melebihi_500_karakter_ditolak(): void
    {
        $siswa = User::factory()->create(['role' => 'siswa']);
        $cat = $this->makeCategory();

        $this->actingAs($siswa)->post(route('dashboard.posts.store'), [
            'title' => 'Tags Panjang',
            'category_id' => $cat->id,
            'content' => '<p>x</p>',
            'tags' => str_repeat('a', 501),
        ])->assertSessionHasErrors('tags');
    }

    // ——— Slug uniqueness safety-net ———

    public function test_dua_post_judul_sama_slug_tetap_unik(): void
    {
        $siswa = User::factory()->create(['role' => 'siswa']);
        $cat = $this->makeCategory();
        $title = 'Judul Sama Persis '.uniqid();

        $this->actingAs($siswa)->post(route('dashboard.posts.store'), [
            'title' => $title, 'category_id' => $cat->id, 'content' => '<p>a</p>',
        ])->assertRedirect();

        $this->actingAs($siswa)->post(route('dashboard.posts.store'), [
            'title' => $title, 'category_id' => $cat->id, 'content' => '<p>b</p>',
        ])->assertRedirect();

        $slugs = Post::where('title', $title)->pluck('slug')->toArray();
        $this->assertCount(2, $slugs);
        $this->assertNotSame($slugs[0], $slugs[1]);
    }

    // ——— Views increment safety-net ———

    public function test_views_bertambah_setiap_kunjungan_approved(): void
    {
        $post = $this->makeApprovedPost(['views_count' => 5]);
        $this->get(route('news.show', $post->slug))->assertOk();
        $this->assertSame(6, $post->fresh()->views_count);
        $this->get(route('news.show', $post->slug))->assertOk();
        $this->assertSame(7, $post->fresh()->views_count);
    }

    // ——— Dashboard empty safety-net ———

    public function test_siswa_tanpa_post_myposts_kosong_tetap_200(): void
    {
        $siswa = User::factory()->create(['role' => 'siswa']);
        $response = $this->actingAs($siswa)->get(route('dashboard.siswa'));
        $response->assertOk();
        $this->assertCount(0, $response->viewData('myPosts'));
    }

    public function test_guru_tanpa_pending_tetap_200(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $response = $this->actingAs($guru)->get(route('dashboard.guru'));
        $response->assertOk();
        $this->assertCount(0, $response->viewData('pending'));
    }

    public function test_admin_stats_konsisten_dengan_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cat = $this->makeCategory();
        $siswa = User::factory()->create(['role' => 'siswa']);
        $this->makeApprovedPost(['user' => $siswa, 'category' => $cat]);
        $this->makePendingPost(['user' => $siswa, 'category' => $cat]);

        $stats = $this->actingAs($admin)->get(route('dashboard.admin'))->viewData('stats');
        $this->assertSame(User::count(), $stats['users']);
        $this->assertSame(Post::where('status', 'pending')->count(), $stats['pending']);
        $this->assertSame(Post::where('status', 'approved')->count(), $stats['published']);
    }

    // ——— Category restrict safety-net ———

    public function test_hapus_user_mengcascade_postnya(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $siswa = User::factory()->create(['role' => 'siswa']);
        $cat = $this->makeCategory();
        $post = Post::create([
            'user_id' => $siswa->id, 'category_id' => $cat->id,
            'title' => 'Cascade '.uniqid(), 'slug' => 'cascade-'.uniqid(),
            'content' => '<p>x</p>', 'status' => 'pending',
        ]);

        $this->actingAs($admin)->delete(route('dashboard.users.destroy', $siswa))->assertRedirect();
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        $this->assertDatabaseMissing('users', ['id' => $siswa->id]);
    }

    // ——— Pagination safety-net ———

    public function test_kategori_pagination_tetap_200_dengan_banyak_post(): void
    {
        $cat = $this->makeCategory('Paginate');
        $user = User::factory()->create(['role' => 'admin']);
        for ($i = 0; $i < 12; $i++) {
            $this->makeApprovedPost(['user' => $user, 'category' => $cat]);
        }

        $this->get(route('category', $cat->slug))->assertOk();
        $this->get(route('category', $cat->slug).'?page=2')->assertOk();
    }
}
