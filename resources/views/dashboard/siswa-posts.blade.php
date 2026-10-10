<x-layouts.dashboard title="My Articles">
    @php
        $totalPublished = $myPosts->where('status', 'approved')->count();
        $totalPending = $myPosts->where('status', 'pending')->count();
        $totalRejected = $myPosts->where('status', 'rejected')->count();
    @endphp

    <div class="space-y-6">
        <nav class="flex items-center gap-2 text-[12px] text-ink-faint">
            <a href="{{ route('dashboard.siswa') }}" class="hover:text-accent">Dashboard</a>
            <span>/</span>
            <span class="text-ink">My Articles</span>
        </nav>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-line pb-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-ink">My Articles</h1>
                <p class="mt-1 text-[13px] text-ink-faint">
                    Manage and monitor all your drafted, pending, and published stories.
                </p>
            </div>
            <a href="{{ route('dashboard.siswa.create') }}" class="inline-flex items-center gap-2 rounded-md bg-accent px-4 py-2 text-[13px] font-semibold text-white hover:bg-accent-hover shadow-xs transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                <span>Create Post</span>
            </a>
        </div>

        @if(session('status'))
            <div class="rounded-md border border-success/30 bg-emerald-50 px-4 py-3 text-[13px] font-medium text-success">
                {{ session('status') }}
            </div>
        @endif

        <div class="flex flex-wrap gap-1.5 border-b border-line pb-3 text-[13px]">
            <button type="button" onclick="filterPosts('all')" data-tab="all" class="filter-btn rounded-md px-3 py-1 font-semibold transition-colors bg-slate-900 text-white">
                All ({{ $myPosts->count() }})
            </button>
            <button type="button" onclick="filterPosts('approved')" data-tab="approved" class="filter-btn rounded-md px-3 py-1 font-medium text-ink-faint hover:text-ink transition-colors">
                Published ({{ $totalPublished }})
            </button>
            <button type="button" onclick="filterPosts('pending')" data-tab="pending" class="filter-btn rounded-md px-3 py-1 font-medium text-ink-faint hover:text-ink transition-colors">
                Pending Review ({{ $totalPending }})
            </button>
            @if($totalRejected > 0)
                <button type="button" onclick="filterPosts('rejected')" data-tab="rejected" class="filter-btn rounded-md px-3 py-1 font-medium text-danger hover:bg-red-50 transition-colors">
                    Needs Revision ({{ $totalRejected }})
                </button>
            @endif
        </div>

        <div class="divide-y divide-line rounded-xl border border-line bg-canvas">
            @forelse($myPosts as $post)
                <div class="post-row p-5 transition-colors hover:bg-canvas-alt/30" data-status="{{ $post->status }}">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div class="flex items-start gap-4 min-w-0 flex-1">
                            <div class="hidden sm:block h-20 w-32 shrink-0 overflow-hidden rounded-md border border-line bg-canvas-subtle">
                                <x-post-cover :post="$post" ratio="16/10" size="24" />
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2 text-[12px]">
                                    <span class="font-bold uppercase tracking-wider text-accent text-[11px]">{{ $post->category->name }}</span>
                                    <span class="text-ink-muted">&middot;</span>
                                    
                                    @if($post->status === 'approved')
                                        <span class="inline-flex items-center gap-1 font-semibold text-emerald-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                                            Published
                                        </span>
                                    @elseif($post->status === 'pending')
                                        <span class="inline-flex items-center gap-1 font-semibold text-amber-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                            Pending Review
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 font-semibold text-danger">
                                            <span class="h-1.5 w-1.5 rounded-full bg-danger"></span>
                                            Needs Revision
                                        </span>
                                    @endif

                                    <span class="text-ink-faint">&middot; Submitted {{ $post->created_at->translatedFormat('d M Y') }}</span>
                                </div>

                                <h3 class="mt-1.5 text-[16px] font-bold text-ink">
                                    <a href="{{ $post->status === 'approved' ? route('news.show', $post->slug) : route('dashboard.posts.preview', $post) }}" class="hover:text-accent transition-colors">{{ $post->title }}</a>
                                </h3>

                                <p class="mt-1 text-[13px] text-ink-faint line-clamp-1">
                                    {{ Str::limit(strip_tags($post->content), 130) }}
                                </p>

                                @if($post->status === 'approved')
                                    <div class="mt-2 text-[11px] text-ink-faint">
                                        <span>{{ number_format($post->views_count) }} total readers</span>
                                    </div>
                                @endif

                                @if($post->status === 'rejected' && $post->rejection_reason)
                                    <div class="mt-3 rounded-md border border-red-200 bg-red-50 p-3 text-[13px] text-red-800">
                                        <p class="font-bold text-[12px]">Teacher Correction Note:</p>
                                        <p class="mt-0.5 text-[12px] leading-relaxed">{{ $post->rejection_reason }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0 self-end sm:self-start">
                            <a href="{{ $post->status === 'approved' ? route('news.show', $post->slug) : route('dashboard.posts.preview', $post) }}" class="rounded-md border border-line px-3 py-1.5 text-[12px] font-medium text-ink-soft hover:bg-canvas-subtle">
                                {{ $post->status === 'approved' ? 'View Live' : 'Preview Draft' }}
                            </a>

                            <a href="{{ route('dashboard.siswa.edit', $post) }}" class="rounded-md border border-line px-3 py-1.5 text-[12px] font-semibold text-accent hover:bg-accent-soft">
                                {{ $post->status === 'rejected' ? 'Revise Article' : 'Edit' }}
                            </a>

                            <form method="POST" action="{{ route('dashboard.posts.destroy', $post) }}" onsubmit="return confirm('Are you sure you want to delete this article?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-md border border-line px-2.5 py-1.5 text-[12px] font-medium text-ink-faint hover:bg-red-50 hover:text-danger hover:border-red-200">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center text-ink-faint text-[13px]">
                    No articles found in your account.
                </div>
            @endforelse
        </div>
    </div>

    <script>
        function filterPosts(status) {
            document.querySelectorAll('.filter-btn').forEach(btn => {
                if (btn.getAttribute('data-tab') === status) {
                    btn.className = 'filter-btn rounded-md px-3 py-1 font-semibold transition-colors bg-slate-900 text-white';
                } else {
                    btn.className = 'filter-btn rounded-md px-3 py-1 font-medium text-ink-faint hover:text-ink transition-colors';
                }
            });

            document.querySelectorAll('.post-row').forEach(row => {
                if (status === 'all' || row.getAttribute('data-status') === status) {
                    row.style.display = 'block';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</x-layouts.dashboard>
