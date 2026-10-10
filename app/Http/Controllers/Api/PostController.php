<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PostController extends Controller
{
    /**
     * List published articles for mobile/API consumption.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Post::with(['author:id,name,role', 'category:id,name,slug', 'tags:id,name,slug'])
            ->where('status', 'approved');

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->query('category')));
        }

        if ($request->filled('tag')) {
            $query->whereHas('tags', fn ($q) => $q->where('slug', $request->query('tag')));
        }

        $posts = $query->orderBy('published_at', 'desc')->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $posts->items(),
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'total' => $posts->total(),
                'per_page' => $posts->perPage(),
                'last_page' => $posts->lastPage(),
            ],
        ]);
    }

    /**
     * Get single post detail by slug or UUID.
     */
    public function show(Request $request, string $idOrSlug): JsonResponse
    {
        $post = Post::with(['author:id,name,role', 'category:id,name,slug', 'tags:id,name,slug'])
            ->where(fn ($q) => $q->where('slug', $idOrSlug)->orWhere('id', $idOrSlug))
            ->first();

        if (! $post) {
            return response()->json([
                'status' => 'error',
                'message' => 'Article not found',
            ], 404);
        }

        $user = $request->user();
        $canPreview = $user && ($user->isAdmin() || $user->isGuru() || $user->id === $post->user_id);

        if (! $canPreview && $post->status !== 'approved') {
            return response()->json([
                'status' => 'error',
                'message' => 'Article is not published',
            ], 404);
        }

        if ($post->status === 'approved') {
            $post->increment('views_count');
        }

        return response()->json([
            'status' => 'success',
            'data' => $post,
        ]);
    }

    /**
     * Submit an article draft via API (protected by JWT).
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isSiswa() && ! $user->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Forbidden: Only students and administrators can submit articles',
            ], 403);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'content' => ['required', 'string'],
            'tags' => ['nullable', 'string', 'max:500'],
        ]);

        $status = $user->isAdmin() ? 'approved' : 'pending';

        $post = Post::create([
            'user_id' => $user->id,
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'content' => $data['content'],
            'status' => $status,
            'published_at' => $status === 'approved' ? now() : null,
        ]);

        if (! empty($data['tags'])) {
            $tagNames = collect(explode(',', $data['tags']))
                ->map(fn ($t) => trim($t))
                ->filter()
                ->unique();

            $tagIds = $tagNames->map(function ($name) {
                return Tag::firstOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name]
                )->id;
            });

            $post->tags()->sync($tagIds);
        }

        return response()->json([
            'status' => 'success',
            'message' => $user->isAdmin()
                ? 'Official article published directly'
                : 'Article draft submitted successfully for teacher moderation',
            'data' => $post->load(['category', 'tags']),
        ], 201);
    }
}
