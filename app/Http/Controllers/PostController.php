<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Tag;
use App\Support\ImageStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PostController extends Controller
{
    /**
     * Pratinjau artikel (termasuk pending & rejected) khusus untuk penulis, guru, atau admin.
     */
    public function preview(Post $post)
    {
        $user = auth()->user();
        abort_unless($user->isAdmin() || $user->isGuru() || $user->id === $post->user_id, 403);

        $related = Post::with(['author', 'category', 'tags'])
            ->where('status', 'approved')
            ->where('category_id', $post->category_id)
            ->where('id', '!=', $post->id)
            ->orderBy('published_at', 'desc')
            ->take(3)
            ->get();

        return view('public.news-show', compact('post', 'related'));
    }

    /**
     * Siswa membuat draf (pending) atau Admin menerbitkan langsung (approved).
     */
    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $isAdmin = $request->user()->isAdmin();
        $status = $isAdmin ? 'approved' : 'pending';

        $post = Post::create([
            'user_id' => $request->user()->id,
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'content' => $data['content'],
            'cover_image' => ImageStore::store($request->file('cover_image'), 'cover'),
            'status' => $status,
            'published_at' => $status === 'approved' ? now() : null,
        ]);

        $this->syncTags($post, $data['tags'] ?? '');

        return redirect()->route('dashboard.siswa.posts')->with('status', $isAdmin
            ? 'Berita berhasil diterbitkan.'
            : 'Berita telah dikirim dan menunggu persetujuan guru.');
    }

    public function update(Request $request, Post $post)
    {
        $this->authorizeOwner($post);

        $data = $this->validateData($request);

        if ($request->hasFile('cover_image')) {
            $cover = ImageStore::store($request->file('cover_image'), 'cover');
            if ($cover) {
                ImageStore::delete($post->cover_image);
                $data['cover_image'] = $cover;
            }
        }

        if ($post->status === 'rejected' && ! $request->user()->isAdmin()) {
            $data['status'] = 'pending';
            $data['rejection_reason'] = null;
        }

        $post->update($data);
        $this->syncTags($post, $data['tags'] ?? '');

        return redirect()->route('dashboard.siswa.posts')->with('status', 'Berita berhasil diperbarui.');
    }

    public function destroy(Request $request, Post $post)
    {
        $this->authorizeOwner($post);

        ImageStore::delete($post->cover_image);
        $title = $post->title;
        $id = $post->id;
        $post->delete();

        Log::info("Audit [Post Deleted]: Post {$id} ('{$title}') deleted by {$request->user()->role} {$request->user()->id}");

        return redirect()->route('dashboard.siswa.posts')->with('status', 'Berita dihapus.');
    }

    /**
     * Guru/Admin menyetujui draf sehingga langsung tayang.
     */
    public function approve(Request $request, Post $post)
    {
        abort_unless($request->user()->isGuru() || $request->user()->isAdmin(), 403);

        $post->update([
            'status' => 'approved',
            'rejection_reason' => null,
            'published_at' => now(),
        ]);

        Log::info("Audit [Post Approved]: Post {$post->id} ('{$post->title}') approved by {$request->user()->role} {$request->user()->id}");

        return redirect()->route('dashboard.siswa.posts')->with('status', 'Berita disetujui dan diterbitkan.');
    }

    /**
     * Guru/Admin menolak draf beserta alasan.
     */
    public function reject(Request $request, Post $post)
    {
        abort_unless($request->user()->isGuru() || $request->user()->isAdmin(), 403);

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $post->update([
            'status' => 'rejected',
            'rejection_reason' => $data['rejection_reason'],
            'published_at' => null,
        ]);

        Log::warning("Audit [Post Rejected]: Post {$post->id} ('{$post->title}') rejected by {$request->user()->role} {$request->user()->id} with reason: {$data['rejection_reason']}");

        return redirect()->route('dashboard.siswa.posts')->with('status', 'Berita ditolak. Pengirim melihat alasan di dasbornya.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'content' => ['required', 'string'],
            'tags' => ['nullable', 'string', 'max:500'],
            'cover_image' => ['nullable', 'image', 'max:4096'],
        ]);
    }

    /**
     * Sinkronkan tag dari string "sekolah, lomba"   buat bila belum ada.
     */
    private function syncTags(Post $post, string $tags): void
    {
        $names = collect(explode(',', $tags))
            ->map(fn ($t) => trim($t))
            ->filter()
            ->unique();

        $ids = $names->map(function (string $name) {
            $tag = Tag::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name],
            );

            return $tag->id;
        });

        $post->tags()->sync($ids);
    }

    /**
     * Pemilik draf atau Admin boleh mengubah/menghapus.
     */
    private function authorizeOwner(Post $post): void
    {
        $user = request()->user();

        abort_unless($user->isAdmin() || $post->user_id === $user->id, 403);
    }
}
