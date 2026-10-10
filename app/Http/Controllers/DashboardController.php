<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return $this->admin();
        }

        if ($user->isGuru()) {
            return $this->guru();
        }

        return $this->siswa();
    }

    // ── Siswa ────────────────────────────────────────────────────────
    public function siswa()
    {
        $user = auth()->user();
        $myPosts = Post::with(['category', 'tags'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
        $categories = Category::all();

        return view('dashboard.siswa', compact('myPosts', 'categories'));
    }

    public function siswaCreate()
    {
        $categories = Category::all();

        return view('dashboard.siswa-create', compact('categories'));
    }

    public function siswaPosts()
    {
        $user = auth()->user();
        $myPosts = Post::with(['category', 'tags'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
        $categories = Category::all();

        return view('dashboard.siswa-posts', compact('myPosts', 'categories'));
    }

    public function siswaEdit(Post $post)
    {
        $user = auth()->user();
        abort_unless($user->isAdmin() || $post->user_id === $user->id, 403);

        $categories = Category::all();

        return view('dashboard.siswa-edit', compact('post', 'categories'));
    }

    // ── Guru ─────────────────────────────────────────────────────────
    public function guru()
    {
        $pending = Post::with(['author', 'category', 'tags'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();

        $recentlyApproved = Post::with(['author', 'category'])
            ->where('status', 'approved')
            ->orderBy('published_at', 'desc')
            ->take(5)
            ->get();

        return view('dashboard.guru', compact('pending', 'recentlyApproved'));
    }

    public function guruQueue()
    {
        $pending = Post::with(['author', 'category', 'tags'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();

        return view('dashboard.guru-queue', compact('pending'));
    }

    public function guruHistory()
    {
        $approvedPosts = Post::with(['author', 'category', 'tags'])
            ->where('status', 'approved')
            ->orderBy('published_at', 'desc')
            ->paginate(15);

        return view('dashboard.guru-history', compact('approvedPosts'));
    }

    // ── Admin ────────────────────────────────────────────────────────
    public function admin()
    {
        $stats = [
            'users' => User::count(),
            'siswa' => User::where('role', 'siswa')->count(),
            'guru' => User::where('role', 'guru')->count(),
            'posts' => Post::count(),
            'pending' => Post::where('status', 'pending')->count(),
            'published' => Post::where('status', 'approved')->count(),
        ];

        $recentPosts = Post::with(['author', 'category'])
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get();

        $recentUsers = User::orderBy('created_at', 'desc')->take(6)->get();

        return view('dashboard.admin', compact('stats', 'recentPosts', 'recentUsers'));
    }

    public function adminUsers()
    {
        $users = User::withCount('posts')->orderBy('created_at', 'desc')->get();

        return view('dashboard.admin-users', compact('users'));
    }

    public function adminCategories()
    {
        $categories = Category::withCount('posts')->get();

        return view('dashboard.admin-categories', compact('categories'));
    }

    public function adminPosts()
    {
        $posts = Post::with(['author', 'category', 'tags'])->orderBy('created_at', 'desc')->get();

        return view('dashboard.admin-posts', compact('posts'));
    }
}
