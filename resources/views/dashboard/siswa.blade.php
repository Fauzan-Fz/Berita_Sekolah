<x-layouts.dashboard title="Dashboard">
    @php
        $user = auth()->user();
        $totalPublished = $myPosts->where('status', 'approved')->count();
        $totalPending = $myPosts->where('status', 'pending')->count();
        $totalRejected = $myPosts->where('status', 'rejected')->count();
        $totalViews = $myPosts->where('status', 'approved')->sum('views_count');
        $recentPosts = $myPosts->take(5);
    @endphp

    <div class="space-y-8">
        {{-- Welcome Header & Quick Action --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-xl border border-line bg-canvas p-6 shadow-xs">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-ink sm:text-2xl">
                    Welcome back, {{ $user->name }}!
                </h1>
                <p class="mt-1 text-[13px] text-ink-faint">
                    Here is a quick overview of your school articles, publication status, and readership performance.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard.siswa.create') }}" class="inline-flex items-center gap-2 rounded-md bg-accent px-4 py-2 text-[13px] font-semibold text-white transition-colors hover:bg-accent-hover shadow-xs">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Create Post</span>
                </a>
            </div>
        </div>

        {{-- Alerts --}}
        @if(session('status'))
            <div class="rounded-md border border-success/30 bg-emerald-50 px-4 py-3 text-[13px] font-medium text-success">
                {{ session('status') }}
            </div>
        @endif

        {{-- Metric Cards (Statistik Penulis) --}}
        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg border border-line bg-canvas p-5 shadow-xs">
                <div class="flex items-center justify-between text-ink-faint text-[12px] font-semibold uppercase tracking-wider">
                    <span>Published Articles</span>
                    <span class="flex h-7 w-7 items-center justify-center rounded-md bg-emerald-50 text-emerald-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                </div>
                <div class="mt-2 text-2xl font-black text-ink">{{ $totalPublished }}</div>
                <div class="mt-1 text-[11px] text-ink-faint">Live on the public portal</div>
            </div>

            <div class="rounded-lg border border-line bg-canvas p-5 shadow-xs">
                <div class="flex items-center justify-between text-ink-faint text-[12px] font-semibold uppercase tracking-wider">
                    <span>Pending Review</span>
                    <span class="flex h-7 w-7 items-center justify-center rounded-md bg-amber-50 text-amber-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                </div>
                <div class="mt-2 text-2xl font-black text-ink">{{ $totalPending }}</div>
                <div class="mt-1 text-[11px] text-ink-faint">Awaiting teacher moderation</div>
            </div>

            <div class="rounded-lg border border-line bg-canvas p-5 shadow-xs">
                <div class="flex items-center justify-between text-ink-faint text-[12px] font-semibold uppercase tracking-wider">
                    <span>Needs Revision</span>
                    <span class="flex h-7 w-7 items-center justify-center rounded-md bg-red-50 text-danger">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </span>
                </div>
                <div class="mt-2 text-2xl font-black {{ $totalRejected > 0 ? 'text-danger' : 'text-ink' }}">{{ $totalRejected }}</div>
                <div class="mt-1 text-[11px] text-ink-faint">Requires student edits</div>
            </div>

            <div class="rounded-lg border border-line bg-canvas p-5 shadow-xs">
                <div class="flex items-center justify-between text-ink-faint text-[12px] font-semibold uppercase tracking-wider">
                    <span>Total Reads</span>
                    <span class="flex h-7 w-7 items-center justify-center rounded-md bg-slate-100 text-slate-800">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </span>
                </div>
                <div class="mt-2 text-2xl font-black text-ink">{{ number_format($totalViews) }}</div>
                <div class="mt-1 text-[11px] text-ink-faint">Combined readership volume</div>
            </div>
        </section>

        {{-- Main 2-Column Content: Recent Activity & Contributor Guidelines --}}
        <div class="grid gap-8 lg:grid-cols-12">
            
            {{-- Left Column: Recent Articles (8 cols) --}}
            <section class="lg:col-span-8 space-y-4">
                <div class="flex items-center justify-between border-b border-line pb-3">
                    <h2 class="text-base font-bold tracking-tight text-ink">Recent Articles</h2>
                    <a href="{{ route('dashboard.siswa.posts') }}" class="text-[12px] font-semibold text-accent hover:underline">
                        View All ({{ $myPosts->count() }}) &rarr;
                    </a>
                </div>

                <div class="divide-y divide-line rounded-xl border border-line bg-canvas">
                    @forelse($recentPosts as $post)
                        <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 text-[11px]">
                                    <span class="font-bold uppercase tracking-wider text-accent">{{ $post->category->name }}</span>
                                    <span class="text-ink-muted">&middot;</span>
                                    @if($post->status === 'approved')
                                        <span class="font-semibold text-emerald-700">Published</span>
                                    @elseif($post->status === 'pending')
                                        <span class="font-semibold text-amber-700">Pending Review</span>
                                    @else
                                        <span class="font-semibold text-red-700">Needs Revision</span>
                                    @endif
                                    <span class="text-ink-faint">&middot; {{ $post->created_at->translatedFormat('d M Y') }}</span>
                                </div>

                                <h3 class="mt-1 text-[15px] font-bold text-ink truncate">
                                    <a href="{{ $post->status === 'approved' ? route('news.show', $post->slug) : route('dashboard.posts.preview', $post) }}" class="hover:text-accent transition-colors">{{ $post->title }}</a>
                                </h3>

                                @if($post->status === 'rejected' && $post->rejection_reason)
                                    <p class="mt-1.5 text-[12px] text-red-700 line-clamp-1">
                                        <span class="font-semibold">Teacher Note:</span> {{ $post->rejection_reason }}
                                    </p>
                                @endif
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <a href="{{ $post->status === 'approved' ? route('news.show', $post->slug) : route('dashboard.posts.preview', $post) }}" class="rounded border border-line px-2.5 py-1 text-[11px] font-medium text-ink-soft hover:bg-canvas-subtle">
                                    {{ $post->status === 'approved' ? 'View Live' : 'Preview' }}
                                </a>
                                <a href="{{ route('dashboard.siswa.edit', $post) }}" class="rounded border border-line px-2.5 py-1 text-[11px] font-semibold text-accent hover:bg-accent-soft">
                                    {{ $post->status === 'rejected' ? 'Revise' : 'Edit' }}
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-[13px] text-ink-faint">
                            You have not written any articles yet.
                            <div class="mt-3">
                                <a href="{{ route('dashboard.siswa.create') }}" class="inline-block rounded-md bg-accent px-3 py-1.5 text-[12px] font-semibold text-white hover:bg-accent-hover">
                                    Write Your First Post
                                </a>
                            </div>
                        </div>
                    @endforelse
                </div>
            </section>

            {{-- Right Column: Editorial Workflow & Tips (4 cols) --}}
            <aside class="lg:col-span-4 space-y-6">
                <div class="rounded-xl border border-line bg-canvas p-5 shadow-xs">
                    <h3 class="text-sm font-bold text-ink border-b border-line pb-2.5">
                        Publication Workflow
                    </h3>
                    <ol class="mt-3.5 space-y-3 text-[12px] text-ink-soft">
                        <li class="flex gap-2.5">
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-slate-100 font-bold text-[10px]">1</span>
                            <span><strong>Draft:</strong> Submit your article with accurate category and photo.</span>
                        </li>
                        <li class="flex gap-2.5">
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-800 font-bold text-[10px]">2</span>
                            <span><strong>Moderation:</strong> A teacher reviews the content for accuracy and tone.</span>
                        </li>
                        <li class="flex gap-2.5">
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-800 font-bold text-[10px]">3</span>
                            <span><strong>Published:</strong> Once approved, your story goes live to all readers.</span>
                        </li>
                    </ol>
                </div>

                <div class="rounded-xl border border-line bg-canvas p-5 shadow-xs">
                    <h3 class="text-sm font-bold text-ink border-b border-line pb-2.5">
                        Writing Guidelines
                    </h3>
                    <ul class="mt-3 space-y-2 text-[12px] text-ink-faint list-disc pl-4">
                        <li>Ensure photos are clear and related to school activities.</li>
                        <li>Include quotes from teachers or students when reporting events.</li>
                        <li>Check spelling and structure before final submission.</li>
                    </ul>
                </div>
            </aside>
        </div>
    </div>
</x-layouts.dashboard>
