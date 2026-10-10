<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ModerationTest extends TestCase
{
    use RefreshDatabase;

    private function makePendingPost(?User $author = null, ?Category $cat = null): Post
    {
        $author ??= User::factory()->create(['role' => 'siswa']);
        $cat ??= Category::create(['name' => 'Umum '.uniqid(), 'slug' => 'umum-'.uniqid()]);

        return Post::create([
            'user_id' => $author->id,
            'category_id' => $cat->id,
            'title' => 'Draft Menunggu Moderasi '.uniqid(),
            'slug' => 'draft-'.uniqid().'-'.Str::random(5),
            'content' => '<p>konten draft</p>',
            'status' => 'pending',
            'published_at' => null,
        ]);
    }

    // ── Approve ────────────────────────────────────────────────────

    public function test_guru_bisa_approve_post_pending(): void
    {
        $post = $this->makePendingPost();
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($guru)->post(route('dashboard.posts.approve', $post))->assertRedirect();

        $post->refresh();
        $this->assertSame('approved', $post->status);
        $this->assertNotNull($post->published_at);
        $this->assertNull($post->rejection_reason);
    }

    public function test_admin_bisa_approve_post_pending(): void
    {
        $post = $this->makePendingPost();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('dashboard.posts.approve', $post))->assertRedirect();
        $this->assertSame('approved', $post->fresh()->status);
    }

    public function test_siswa_tidak_bisa_approve(): void
    {
        $post = $this->makePendingPost();
        $siswa = User::factory()->create(['role' => 'siswa']);

        $this->actingAs($siswa)->post(route('dashboard.posts.approve', $post))->assertForbidden();
        $this->assertSame('pending', $post->fresh()->status);
    }

    public function test_guest_tidak_bisa_approve(): void
    {
        $post = $this->makePendingPost();
        $this->post(route('dashboard.posts.approve', $post))->assertRedirect(route('login'));
    }

    public function test_approve_menghapus_rejection_reason_lama(): void
    {
        $post = $this->makePendingPost();
        $post->update(['status' => 'rejected', 'rejection_reason' => 'Tidak sesuai topik.']);
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($guru)->post(route('dashboard.posts.approve', $post))->assertRedirect();
        $post->refresh();
        $this->assertSame('approved', $post->status);
        $this->assertNull($post->rejection_reason);
    }

    // ── Reject ─────────────────────────────────────────────────────

    public function test_guru_bisa_reject_dengan_alasan(): void
    {
        $post = $this->makePendingPost();
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($guru)->post(route('dashboard.posts.reject', $post), [
            'rejection_reason' => 'Topik tidak relevan untuk portal sekolah.',
        ])->assertRedirect();

        $post->refresh();
        $this->assertSame('rejected', $post->status);
        $this->assertSame('Topik tidak relevan untuk portal sekolah.', $post->rejection_reason);
        $this->assertNull($post->published_at);
    }

    public function test_admin_bisa_reject(): void
    {
        $post = $this->makePendingPost();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('dashboard.posts.reject', $post), [
            'rejection_reason' => 'Perlu revisi.',
        ])->assertRedirect();

        $this->assertSame('rejected', $post->fresh()->status);
    }

    public function test_reject_wajib_alasan(): void
    {
        $post = $this->makePendingPost();
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($guru)->post(route('dashboard.posts.reject', $post), [
            'rejection_reason' => '',
        ])->assertSessionHasErrors('rejection_reason');

        $this->assertSame('pending', $post->fresh()->status);
    }

    public function test_siswa_tidak_bisa_reject(): void
    {
        $post = $this->makePendingPost();
        $siswa = User::factory()->create(['role' => 'siswa']);

        $this->actingAs($siswa)->post(route('dashboard.posts.reject', $post), [
            'rejection_reason' => 'Coba reject',
        ])->assertForbidden();
    }

    public function test_guest_tidak_bisa_reject(): void
    {
        $post = $this->makePendingPost();
        $this->post(route('dashboard.posts.reject', $post), [
            'rejection_reason' => 'x',
        ])->assertRedirect(route('login'));
    }

    public function test_rejected_tidak_tampil_di_landing(): void
    {
        $post = $this->makePendingPost();
        $guru = User::factory()->create(['role' => 'guru']);
        $this->actingAs($guru)->post(route('dashboard.posts.reject', $post), [
            'rejection_reason' => 'Ditolak.',
        ]);
        // rejected post tidak punya slug yang tampil di landing — cek via news.show 404
        $this->get(route('news.show', $post->fresh()->slug))->assertNotFound();
    }

    public function test_views_count_tidak_nambah_untuk_pending(): void
    {
        $post = $this->makePendingPost();
        $this->get(route('news.show', $post->slug))->assertNotFound();
        $this->assertSame(0, $post->fresh()->views_count);
    }
}
