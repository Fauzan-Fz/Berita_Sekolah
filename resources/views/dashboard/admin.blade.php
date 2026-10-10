<x-layouts.dashboard title="Dashboard">
    <div class="space-y-8">
        @if(session('status'))
            <div class="rounded-md border border-success/30 bg-emerald-50 px-4 py-3 text-[13px] font-medium text-success">
                {{ session('status') }}
            </div>
        @endif

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-xl border border-line bg-canvas p-6 shadow-xs">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-ink sm:text-2xl">
                    Admin Overview
                </h1>
                <p class="mt-1 text-[13px] text-ink-faint">
                    System operations, user roles, category taxonomies, and school publication audits.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard.siswa.create') }}" class="inline-flex items-center gap-2 rounded-md bg-accent px-4 py-2 text-[13px] font-semibold text-white transition-colors hover:bg-accent-hover shadow-xs">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Create Official Post</span>
                </a>
            </div>
        </div>

        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg border border-line bg-canvas p-5 shadow-xs">
                <div class="flex items-center justify-between text-ink-faint text-[12px] font-semibold uppercase tracking-wider">
                    <span>Registered Users</span>
                    <span class="flex h-7 w-7 items-center justify-center rounded-md bg-slate-100 text-slate-800">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </span>
                </div>
                <div class="mt-2 text-2xl font-black text-ink">{{ $stats['users'] }}</div>
                <div class="mt-1 text-[11px] text-ink-faint">{{ $stats['siswa'] }} Students &middot; {{ $stats['guru'] }} Teachers</div>
            </div>

            <div class="rounded-lg border border-line bg-canvas p-5 shadow-xs">
                <div class="flex items-center justify-between text-ink-faint text-[12px] font-semibold uppercase tracking-wider">
                    <span>Total Articles</span>
                    <span class="flex h-7 w-7 items-center justify-center rounded-md bg-slate-100 text-slate-800">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                    </span>
                </div>
                <div class="mt-2 text-2xl font-black text-ink">{{ $stats['posts'] }}</div>
                <div class="mt-1 text-[11px] text-ink-faint">All drafts &amp; published stories</div>
            </div>

            <div class="rounded-lg border border-line bg-canvas p-5 shadow-xs">
                <div class="flex items-center justify-between text-ink-faint text-[12px] font-semibold uppercase tracking-wider">
                    <span>Published Live</span>
                    <span class="flex h-7 w-7 items-center justify-center rounded-md bg-emerald-50 text-emerald-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                </div>
                <div class="mt-2 text-2xl font-black text-emerald-700">{{ $stats['published'] }}</div>
                <div class="mt-1 text-[11px] text-ink-faint">Visible on public web portal</div>
            </div>

            <div class="rounded-lg border border-line bg-canvas p-5 shadow-xs">
                <div class="flex items-center justify-between text-ink-faint text-[12px] font-semibold uppercase tracking-wider">
                    <span>Pending Moderation</span>
                    <span class="flex h-7 w-7 items-center justify-center rounded-md bg-amber-50 text-amber-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                </div>
                <div class="mt-2 text-2xl font-black {{ $stats['pending'] > 0 ? 'text-amber-700' : 'text-ink' }}">{{ $stats['pending'] }}</div>
                <div class="mt-1 text-[11px] text-ink-faint">Awaiting review from teachers</div>
            </div>
        </section>

        <section>
            <h2 class="text-base font-bold tracking-tight text-ink">Administrative Modules</h2>
            <div class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <a href="{{ route('dashboard.admin.posts') }}" class="group rounded-xl border border-line bg-canvas p-5 shadow-xs transition-colors hover:border-accent">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-canvas-subtle text-ink-soft group-hover:bg-accent group-hover:text-white transition-colors">
                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                    </div>
                    <div class="mt-3 text-[14px] font-bold text-ink group-hover:text-accent">All Posts</div>
                    <div class="mt-1 text-[12px] text-ink-faint">Audit, publish, or remove any story on the portal</div>
                </a>

                <a href="{{ route('dashboard.admin.categories') }}" class="group rounded-xl border border-line bg-canvas p-5 shadow-xs transition-colors hover:border-accent">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-canvas-subtle text-ink-soft group-hover:bg-accent group-hover:text-white transition-colors">
                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                    </div>
                    <div class="mt-3 text-[14px] font-bold text-ink group-hover:text-accent">Categories</div>
                    <div class="mt-1 text-[12px] text-ink-faint">Create, rename, or manage news categories</div>
                </a>

                <a href="{{ route('dashboard.admin.users') }}" class="group rounded-xl border border-line bg-canvas p-5 shadow-xs transition-colors hover:border-accent">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-canvas-subtle text-ink-soft group-hover:bg-accent group-hover:text-white transition-colors">
                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                    <div class="mt-3 text-[14px] font-bold text-ink group-hover:text-accent">User Management</div>
                    <div class="mt-1 text-[12px] text-ink-faint">Promote roles, suspend, or delete accounts</div>
                </a>

                <a href="{{ route('dashboard.siswa.create') }}" class="group rounded-xl border border-line bg-canvas p-5 shadow-xs transition-colors hover:border-accent">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-canvas-subtle text-ink-soft group-hover:bg-accent group-hover:text-white transition-colors">
                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    </div>
                    <div class="mt-3 text-[14px] font-bold text-ink group-hover:text-accent">Create Direct Post</div>
                    <div class="mt-1 text-[12px] text-ink-faint">Publish official announcements without moderation</div>
                </a>
            </div>
        </section>

        <section class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-xl border border-line bg-canvas p-5 shadow-xs">
                <div class="flex items-center justify-between border-b border-line pb-3">
                    <h3 class="text-sm font-bold text-ink">Recent Articles</h3>
                    <a href="{{ route('dashboard.admin.posts') }}" class="text-[12px] font-semibold text-accent hover:underline">All Posts &rarr;</a>
                </div>
                <div class="mt-3 divide-y divide-line">
                    @forelse($recentPosts as $post)
                        <div class="py-3 flex items-center justify-between gap-3 text-[13px]">
                            <div class="min-w-0">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-accent">{{ $post->category->name }}</span>
                                <h4 class="font-bold text-ink truncate mt-0.5">
                                    <a href="{{ $post->status === 'approved' ? route('news.show', $post->slug) : route('dashboard.posts.preview', $post) }}" class="hover:text-accent">{{ $post->title }}</a>
                                </h4>
                                <span class="text-[11px] text-ink-faint">By {{ $post->author->name }} &middot; {{ $post->created_at->diffForHumans() }}</span>
                            </div>
                            <span class="text-[11px] font-semibold shrink-0 {{ $post->status === 'approved' ? 'text-emerald-700' : ($post->status === 'pending' ? 'text-amber-700' : 'text-danger') }}">
                                {{ ucfirst($post->status) }}
                            </span>
                        </div>
                    @empty
                        <p class="py-6 text-center text-[13px] text-ink-faint">No articles created yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl border border-line bg-canvas p-5 shadow-xs">
                <div class="flex items-center justify-between border-b border-line pb-3">
                    <h3 class="text-sm font-bold text-ink">New Registered Users</h3>
                    <a href="{{ route('dashboard.admin.users') }}" class="text-[12px] font-semibold text-accent hover:underline">All Users &rarr;</a>
                </div>
                <div class="mt-3 divide-y divide-line">
                    @forelse($recentUsers as $u)
                        <div class="py-3 flex items-center justify-between gap-3 text-[13px]">
                            <div class="min-w-0">
                                <h4 class="font-bold text-ink truncate">{{ $u->name }}</h4>
                                <span class="text-[11px] text-ink-faint">{{ $u->email }}</span>
                            </div>
                            <span class="rounded bg-canvas-subtle px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-ink-faint shrink-0">
                                {{ $u->role }}
                            </span>
                        </div>
                    @empty
                        <p class="py-6 text-center text-[13px] text-ink-faint">No users registered.</p>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
</x-layouts.dashboard>
