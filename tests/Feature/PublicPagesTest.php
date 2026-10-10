<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    private function makePublishedPost(array $overrides = []): Post
    {
        $user = $overrides['user'] ?? User::factory()->create(['role' => 'admin']);
        $category = $overrides['category'] ?? Category::create(['name' => 'Berita Umum '.uniqid(), 'slug' => 'berita-umum-'.uniqid()]);
        unset($overrides['user'], $overrides['category']);

        return Post::create(array_merge([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Judul Berita '.uniqid(),
            'slug' => 'judul-berita-'.uniqid().'-'.Str::random(5),
            'content' => '<p>Isi berita lengkap untuk pengujian.</p>',
            'status' => 'approved',
            'published_at' => now(),
        ], $overrides));
    }

    public function test_landing_menampilkan_halaman_200(): void
    {
        $this->makePublishedPost();
        $this->get(route('home'))->assertOk()->assertViewIs('public.landing');
    }

    public function test_landing_hanya_menampilkan_berita_approved(): void
    {
        $cat = Category::create(['name' => 'Kategori X', 'slug' => 'kategori-x']);
        $user = User::factory()->create(['role' => 'admin']);
        $approved = $this->makePublishedPost(['user' => $user, 'category' => $cat, 'title' => 'Approved Title ABC']);
        $pending = Post::create([
            'user_id' => $user->id, 'category_id' => $cat->id,
            'title' => 'Pending Title XYZ', 'slug' => 'pending-'.uniqid(),
            'content' => '<p>pending</p>', 'status' => 'pending', 'published_at' => null,
        ]);

        $response = $this->get(route('home'));
        $response->assertOk();
        // pending tidak muncul sebagai objek post di landing (hanya approved yang di-query)
        $response->assertDontSee('Pending Title XYZ');
    }

    public function test_news_show_menampilkan_berita_approved(): void
    {
        $post = $this->makePublishedPost();
        $this->get(route('news.show', $post->slug))->assertOk()->assertViewIs('public.news-show');
    }

    public function test_news_show_menaikkan_views_count(): void
    {
        $post = $this->makePublishedPost(['views_count' => 10]);
        $this->get(route('news.show', $post->slug))->assertOk();
        $this->assertSame(11, $post->fresh()->views_count);
    }

    public function test_news_show_404_untuk_pending_dan_rejected(): void
    {
        $cat = Category::create(['name' => 'Kat Pending', 'slug' => 'kat-pending']);
        $user = User::factory()->create(['role' => 'siswa']);

        $pending = Post::create([
            'user_id' => $user->id, 'category_id' => $cat->id,
            'title' => 'Draft Pending', 'slug' => 'draft-pending-'.uniqid(),
            'content' => '<p>draft</p>', 'status' => 'pending', 'published_at' => null,
        ]);
        $rejected = Post::create([
            'user_id' => $user->id, 'category_id' => $cat->id,
            'title' => 'Ditolak', 'slug' => 'ditolak-'.uniqid(),
            'content' => '<p>ditolak</p>', 'status' => 'rejected', 'published_at' => null,
        ]);

        $this->get(route('news.show', $pending->slug))->assertNotFound();
        $this->get(route('news.show', $rejected->slug))->assertNotFound();
    }

    public function test_news_show_menampilkan_berita_terkait_dalam_kategori_sama(): void
    {
        $cat = Category::create(['name' => 'Prestasi', 'slug' => 'prestasi']);
        $user = User::factory()->create(['role' => 'admin']);
        $main = $this->makePublishedPost(['user' => $user, 'category' => $cat]);
        $related = $this->makePublishedPost(['user' => $user, 'category' => $cat]);
        // berita di kategori lain tidak dianggap related
        $otherCat = Category::create(['name' => 'Lain', 'slug' => 'lain']);
        $unrelated = $this->makePublishedPost(['user' => $user, 'category' => $otherCat]);

        $response = $this->get(route('news.show', $main->slug));
        $response->assertOk();
        // related disuntik ke view
        $relatedFromView = $response->viewData('related');
        $this->assertNotNull($relatedFromView);
        $this->assertTrue($relatedFromView->contains(fn ($p) => $p->id === $related->id));
        $this->assertFalse($relatedFromView->contains(fn ($p) => $p->id === $unrelated->id));
    }

    public function test_halaman_kategori_menampilkan_berita_kategori_tersebut(): void
    {
        $cat = Category::create(['name' => 'Pengumuman', 'slug' => 'pengumuman']);
        $other = Category::create(['name' => 'Prestasi Lain', 'slug' => 'prestasi-lain']);
        $user = User::factory()->create(['role' => 'admin']);
        $inCat = $this->makePublishedPost(['user' => $user, 'category' => $cat, 'title' => 'Kabar Pengumuman Khusus']);
        $outside = $this->makePublishedPost(['user' => $user, 'category' => $other, 'title' => 'Kabar Lain Khusus']);

        $response = $this->get(route('category', $cat->slug));
        $response->assertOk()->assertViewIs('public.category');
        $response->assertSee('Kabar Pengumuman Khusus');
        $response->assertDontSee('Kabar Lain Khusus');
    }

    public function test_halaman_tag_menampilkan_berita_dengan_tag_tersebut(): void
    {
        $cat = Category::create(['name' => 'Kegiatan', 'slug' => 'kegiatan']);
        $user = User::factory()->create(['role' => 'admin']);
        $tagA = Tag::create(['name' => 'futsal', 'slug' => 'futsal']);
        $tagB = Tag::create(['name' => 'seni', 'slug' => 'seni']);

        $withTag = $this->makePublishedPost(['user' => $user, 'category' => $cat, 'title' => 'Berita Futsal Hebat']);
        $withTag->tags()->attach($tagA->id);
        $withoutTag = $this->makePublishedPost(['user' => $user, 'category' => $cat, 'title' => 'Berita Seni Hebat']);
        $withoutTag->tags()->attach($tagB->id);

        $response = $this->get(route('tag', $tagA->slug));
        $response->assertOk()->assertViewIs('public.tag');
        $response->assertSee('Berita Futsal Hebat');
        $response->assertDontSee('Berita Seni Hebat');
    }

    public function test_halaman_login_dan_register_dapat_diakses_guest(): void
    {
        $this->get(route('login'))->assertOk()->assertViewIs('auth.login');
        $this->get(route('register'))->assertOk()->assertViewIs('auth.register');
    }

    public function test_guest_tidak_bisa_akses_dashboard(): void
    {
        $this->get(route('dashboard.index'))->assertRedirect(route('login'));
    }

    public function test_landing_tanpa_berita_tetap_200(): void
    {
        $this->get(route('home'))->assertOk();
    }
}
