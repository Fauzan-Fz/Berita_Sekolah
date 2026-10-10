<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;

class PublicController extends Controller
{
    public function landing()
    {
        $latest = Post::with(['author', 'category', 'tags'])
            ->where('status', 'approved')
            ->orderBy('published_at', 'desc')
            ->take(8)
            ->get();

        $featured = $latest->shift();

        $trending = Post::with(['author', 'category', 'tags'])
            ->where('status', 'approved')
            ->where('published_at', '>=', now()->subDays(7))
            ->orderBy('views_count', 'desc')
            ->take(5)
            ->get();

        if ($trending->count() < 5) {
            $existingIds = $trending->pluck('id');
            $needed = 5 - $trending->count();
            $fallback = Post::with(['author', 'category', 'tags'])
                ->where('status', 'approved')
                ->whereNotIn('id', $existingIds)
                ->orderBy('views_count', 'desc')
                ->take($needed)
                ->get();
            $trending = $trending->concat($fallback);
        }

        return view('public.landing', compact('latest', 'trending', 'featured'));
    }

    public function newsShow(Post $post)
    {
        abort_if($post->status !== 'approved', 404);

        $post->increment('views_count');

        $related = Post::with(['author', 'category', 'tags'])
            ->where('status', 'approved')
            ->where('category_id', $post->category_id)
            ->where('id', '!=', $post->id)
            ->orderBy('published_at', 'desc')
            ->take(3)
            ->get();

        return view('public.news-show', compact('post', 'related'));
    }

    public function category(Category $category)
    {
        $posts = Post::with(['author', 'category', 'tags'])
            ->where('status', 'approved')
            ->where('category_id', $category->id)
            ->orderBy('published_at', 'desc')
            ->paginate(9);

        return view('public.category', compact('category', 'posts'));
    }

    public function tag(Tag $tag)
    {
        $posts = Post::with(['author', 'category', 'tags'])
            ->where('status', 'approved')
            ->whereHas('tags', fn (Builder $q) => $q->where('tags.id', $tag->id))
            ->orderBy('published_at', 'desc')
            ->paginate(9);

        return view('public.tag', compact('tag', 'posts'));
    }

    public function login()
    {
        return view('auth.login');
    }

    public function register()
    {
        return view('auth.register');
    }
}
