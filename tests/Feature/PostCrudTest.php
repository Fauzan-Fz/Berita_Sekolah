<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostCrudTest extends TestCase
{
    use RefreshDatabase;

    private function makeCategory(): Category
    {
        return Category::create(['name' => 'Umum '.uniqid(), 'slug' => 'umum-'.uniqid()]);
    }

    private function makeJpeg(string $name = 'cover.jpg'): UploadedFile
    {
        $img = imagecreatetruecolor(40, 40);
        imagefill($img, 0, 0, imagecolorallocate($img, 200, 200, 210));
        $path = sys_get_temp_dir().'/jpeg-'.bin2hex(random_bytes(4)).'.jpg';
        imagejpeg($img, $path, 80);
        imagedestroy($img);

        return new UploadedFile($path, $name, 'image/jpeg', null, true);
    }

    // ── Store ──────────────────────────────────────────────────────

    public function test_siswa_membuat_post_status_pending(): void
    {
        Storage::fake('public');
        $siswa = User::factory()->create(['role' => 'siswa']);
        $cat = $this->makeCategory();

        $this->actingAs($siswa)->post(route('dashboard.posts.store'), [
            'title' => 'Berita Siswa Baru',
            'category_id' => $cat->id,
            'content' => '<p>Isi berita siswa.</p>',
            'tags' => 'sekolah, lomba',
        ])->assertRedirect();

        $post = Post::firstWhere('title', 'Berita Siswa Baru');
        $this->assertNotNull($post);
        $this->assertSame('pending', $post->status);
        $this->assertNull($post->published_at);
        $this->assertSame($siswa->id, $post->user_id);
        $this->assertCount(2, $post->tags);
    }

    public function test_admin_membuat_post_langsung_approved(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $cat = $this->makeCategory();

        $this->actingAs($admin)->post(route('dashboard.posts.store'), [
            'title' => 'Berita Admin Langsung Tayang',
            'category_id' => $cat->id,
            'content' => '<p>Isi admin.</p>',
        ])->assertRedirect();

        $post = Post::firstWhere('title', 'Berita Admin Langsung Tayang');
        $this->assertSame('approved', $post->status);
        $this->assertNotNull($post->published_at);
    }

    public function test_store_dengan_cover_image_tersimpan_sebagai_webp(): void
    {
        Storage::fake('public');
        $siswa = User::factory()->create(['role' => 'siswa']);
        $cat = $this->makeCategory();

        $this->actingAs($siswa)->post(route('dashboard.posts.store'), [
            'title' => 'Berita Dengan Cover',
            'category_id' => $cat->id,
            'content' => '<p>cover test</p>',
            'cover_image' => $this->makeJpeg(),
        ])->assertRedirect();

        $post = Post::firstWhere('title', 'Berita Dengan Cover');
        $this->assertNotNull($post->cover_image);
        $this->assertStringStartsWith('cover/', $post->cover_image);
        $this->assertStringEndsWith('.webp', $post->cover_image);
        Storage::disk('public')->assertExists($post->cover_image);
    }

    public function test_guru_tidak_bisa_membuat_post(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $cat = $this->makeCategory();

        $this->actingAs($guru)->post(route('dashboard.posts.store'), [
            'title' => 'Guru Coba Post',
            'category_id' => $cat->id,
            'content' => '<p>x</p>',
        ])->assertForbidden();
    }

    public function test_guest_tidak_bisa_membuat_post(): void
    {
        $cat = $this->makeCategory();
        $this->post(route('dashboard.posts.store'), [
            'title' => 'Guest Post',
            'category_id' => $cat->id,
            'content' => '<p>x</p>',
        ])->assertRedirect(route('login'));
    }

    public function test_validasi_store_wajib_title_category_content(): void
    {
        $siswa = User::factory()->create(['role' => 'siswa']);

        $this->actingAs($siswa)->post(route('dashboard.posts.store'), [
            'title' => '',
            'category_id' => 9999,
            'content' => '',
        ])->assertSessionHasErrors(['title', 'category_id', 'content']);
    }

    public function test_tags_kosong_tidak_error(): void
    {
        $siswa = User::factory()->create(['role' => 'siswa']);
        $cat = $this->makeCategory();

        $this->actingAs($siswa)->post(route('dashboard.posts.store'), [
            'title' => 'Tanpa Tag',
            'category_id' => $cat->id,
            'content' => '<p>tanpa tag</p>',
            'tags' => '',
        ])->assertRedirect();

        $post = Post::firstWhere('title', 'Tanpa Tag');
        $this->assertCount(0, $post->tags);
    }

    public function test_tags_duplikat_dan_spasi_ditangani(): void
    {
        $siswa = User::factory()->create(['role' => 'siswa']);
        $cat = $this->makeCategory();

        $this->actingAs($siswa)->post(route('dashboard.posts.store'), [
            'title' => 'Tag Duplikat',
            'category_id' => $cat->id,
            'content' => '<p>tag test</p>',
            'tags' => '  sekolah , sekolah , lomba  ,  ',
        ])->assertRedirect();

        $post = Post::firstWhere('title', 'Tag Duplikat');
        $this->assertCount(2, $post->tags);
    }

    // ── Update ─────────────────────────────────────────────────────

    public function test_pemilik_bisa_update_post_miliknya(): void
    {
        $siswa = User::factory()->create(['role' => 'siswa']);
        $cat = $this->makeCategory();
        $cat2 = $this->makeCategory();
        $post = Post::create([
            'user_id' => $siswa->id, 'category_id' => $cat->id,
            'title' => 'Judul Lama', 'slug' => 'judul-lama-'.uniqid(),
            'content' => '<p>lama</p>', 'status' => 'pending',
        ]);

        $this->actingAs($siswa)->put(route('dashboard.posts.update', $post), [
            'title' => 'Judul Baru',
            'category_id' => $cat2->id,
            'content' => '<p>baru</p>',
        ])->assertRedirect();

        $post->refresh();
        $this->assertSame('Judul Baru', $post->title);
        $this->assertSame($cat2->id, $post->category_id);
    }

    public function test_admin_bisa_update_post_orang_lain(): void
    {
        $siswa = User::factory()->create(['role' => 'siswa']);
        $admin = User::factory()->create(['role' => 'admin']);
        $cat = $this->makeCategory();
        $post = Post::create([
            'user_id' => $siswa->id, 'category_id' => $cat->id,
            'title' => 'Milik Siswa', 'slug' => 'milik-siswa-'.uniqid(),
            'content' => '<p>x</p>', 'status' => 'pending',
        ]);

        $this->actingAs($admin)->put(route('dashboard.posts.update', $post), [
            'title' => 'Diupdate Admin',
            'category_id' => $cat->id,
            'content' => '<p>admin edit</p>',
        ])->assertRedirect();

        $this->assertSame('Diupdate Admin', $post->fresh()->title);
    }

    public function test_siswa_tidak_bisa_update_post_orang_lain(): void
    {
        $siswaA = User::factory()->create(['role' => 'siswa']);
        $siswaB = User::factory()->create(['role' => 'siswa']);
        $cat = $this->makeCategory();
        $post = Post::create([
            'user_id' => $siswaA->id, 'category_id' => $cat->id,
            'title' => 'Milik A', 'slug' => 'milik-a-'.uniqid(),
            'content' => '<p>a</p>', 'status' => 'pending',
        ]);

        $this->actingAs($siswaB)->put(route('dashboard.posts.update', $post), [
            'title' => 'Coba Hack',
            'category_id' => $cat->id,
            'content' => '<p>hack</p>',
        ])->assertForbidden();
    }

    public function test_update_dengan_cover_baru_mengganti_file_lama(): void
    {
        Storage::fake('public');
        $siswa = User::factory()->create(['role' => 'siswa']);
        $cat = $this->makeCategory();
        $post = Post::create([
            'user_id' => $siswa->id, 'category_id' => $cat->id,
            'title' => 'Cover Lama', 'slug' => 'cover-lama-'.uniqid(),
            'content' => '<p>x</p>', 'status' => 'pending',
            'cover_image' => 'cover/lama.webp',
        ]);
        Storage::disk('public')->put('cover/lama.webp', 'fake-content');

        $this->actingAs($siswa)->put(route('dashboard.posts.update', $post), [
            'title' => 'Cover Lama',
            'category_id' => $cat->id,
            'content' => '<p>x</p>',
            'cover_image' => $this->makeJpeg('baru.jpg'),
        ])->assertRedirect();

        $post->refresh();
        $this->assertNotSame('cover/lama.webp', $post->cover_image);
        Storage::disk('public')->assertMissing('cover/lama.webp');
        Storage::disk('public')->assertExists($post->cover_image);
    }

    // ── Destroy ────────────────────────────────────────────────────

    public function test_pemilik_bisa_hapus_post_miliknya(): void
    {
        Storage::fake('public');
        $siswa = User::factory()->create(['role' => 'siswa']);
        $cat = $this->makeCategory();
        $post = Post::create([
            'user_id' => $siswa->id, 'category_id' => $cat->id,
            'title' => 'Hapus Saya', 'slug' => 'hapus-saya-'.uniqid(),
            'content' => '<p>x</p>', 'status' => 'pending',
            'cover_image' => 'cover/hapus.webp',
        ]);
        Storage::disk('public')->put('cover/hapus.webp', 'x');

        $this->actingAs($siswa)->delete(route('dashboard.posts.destroy', $post))->assertRedirect();
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        Storage::disk('public')->assertMissing('cover/hapus.webp');
    }

    public function test_admin_bisa_hapus_post_orang_lain(): void
    {
        $siswa = User::factory()->create(['role' => 'siswa']);
        $admin = User::factory()->create(['role' => 'admin']);
        $cat = $this->makeCategory();
        $post = Post::create([
            'user_id' => $siswa->id, 'category_id' => $cat->id,
            'title' => 'Admin Hapus', 'slug' => 'admin-hapus-'.uniqid(),
            'content' => '<p>x</p>', 'status' => 'pending',
        ]);

        $this->actingAs($admin)->delete(route('dashboard.posts.destroy', $post))->assertRedirect();
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_siswa_tidak_bisa_hapus_post_orang_lain(): void
    {
        $siswaA = User::factory()->create(['role' => 'siswa']);
        $siswaB = User::factory()->create(['role' => 'siswa']);
        $cat = $this->makeCategory();
        $post = Post::create([
            'user_id' => $siswaA->id, 'category_id' => $cat->id,
            'title' => 'Milik A Hapus', 'slug' => 'milik-a-hapus-'.uniqid(),
            'content' => '<p>a</p>', 'status' => 'pending',
        ]);

        $this->actingAs($siswaB)->delete(route('dashboard.posts.destroy', $post))->assertForbidden();
        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    public function test_guru_tidak_bisa_hapus_post_lewat_route_siswa(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $siswa = User::factory()->create(['role' => 'siswa']);
        $cat = $this->makeCategory();
        $post = Post::create([
            'user_id' => $siswa->id, 'category_id' => $cat->id,
            'title' => 'Guru Hapus?', 'slug' => 'guru-hapus-'.uniqid(),
            'content' => '<p>x</p>', 'status' => 'pending',
        ]);

        $this->actingAs($guru)->delete(route('dashboard.posts.destroy', $post))->assertForbidden();
    }
}
