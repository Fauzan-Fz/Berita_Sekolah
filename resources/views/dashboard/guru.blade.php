<x-layouts.dashboard title="Dashboard">
    @php
        $user = auth()->user();
        $totalPending = $pending->count();
        $totalApproved = \App\Models\Post::where('status', 'approved')->count();
        $totalRejected = \App\Models\Post::where('status', 'rejected')->count();
        $totalReads = \App\Models\Post::where('status', 'approved')->sum('views_count');
    @endphp

    <div class="space-y-8">
        {{-- Welcome Banner --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-xl border border-line bg-canvas p-6 shadow-xs">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-ink sm:text-2xl">
                    Welcome, {{ $user->name }}
                </h1>
                <p class="mt-1 text-[13px] text-ink-faint">
                    Teacher Editorial &amp; Moderation Center. Review student stories and curate verified publications.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard.guru.queue') }}" class="inline-flex items-center gap-2 rounded-md bg-accent px-4 py-2 text-[13px] font-semibold text-white transition-colors hover:bg-accent-hover shadow-xs">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Open Moderation Queue</span>
                    @if($totalPending > 0)
                        <span class="rounded bg-white/20 px-1.5 py-0.2 text-[10px] font-bold text-white">{{ $totalPending }}</span>
                    @endif
                </a>
            </div>
        </div>

        {{-- Session Status Alerts --}}
        @if(session('status'))
            <div class="rounded-md border border-success/30 bg-emerald-50 px-4 py-3 text-[13px] font-medium text-success">
                {{ session('status') }}
            </div>
        @endif
        @if($errors->any())
            <div class="rounded-md border border-danger/30 bg-danger-soft px-4 py-3 text-[13px] font-medium text-danger">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Metric Cards --}}
        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg border border-line bg-canvas p-5 shadow-xs">
                <div class="flex items-center justify-between text-ink-faint text-[12px] font-semibold uppercase tracking-wider">
                    <span>Pending Queue</span>
                    <span class="flex h-7 w-7 items-center justify-center rounded-md bg-amber-50 text-amber-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                </div>
                <div class="mt-2 text-2xl font-black {{ $totalPending > 0 ? 'text-amber-700' : 'text-ink' }}">{{ $totalPending }}</div>
                <div class="mt-1 text-[11px] text-ink-faint">Student submissions awaiting review</div>
            </div>

            <div class="rounded-lg border border-line bg-canvas p-5 shadow-xs">
                <div class="flex items-center justify-between text-ink-faint text-[12px] font-semibold uppercase tracking-wider">
                    <span>Approved Stories</span>
                    <span class="flex h-7 w-7 items-center justify-center rounded-md bg-emerald-50 text-emerald-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                </div>
                <div class="mt-2 text-2xl font-black text-ink">{{ $totalApproved }}</div>
                <div class="mt-1 text-[11px] text-ink-faint">Live on the school portal</div>
            </div>

            <div class="rounded-lg border border-line bg-canvas p-5 shadow-xs">
                <div class="flex items-center justify-between text-ink-faint text-[12px] font-semibold uppercase tracking-wider">
                    <span>Returned for Edit</span>
                    <span class="flex h-7 w-7 items-center justify-center rounded-md bg-red-50 text-danger">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </span>
                </div>
                <div class="mt-2 text-2xl font-black text-ink">{{ $totalRejected }}</div>
                <div class="mt-1 text-[11px] text-ink-faint">Drafts requiring student revision</div>
            </div>

            <div class="rounded-lg border border-line bg-canvas p-5 shadow-xs">
                <div class="flex items-center justify-between text-ink-faint text-[12px] font-semibold uppercase tracking-wider">
                    <span>School Reads</span>
                    <span class="flex h-7 w-7 items-center justify-center rounded-md bg-slate-100 text-slate-800">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </span>
                </div>
                <div class="mt-2 text-2xl font-black text-ink">{{ number_format($totalReads) }}</div>
                <div class="mt-1 text-[11px] text-ink-faint">Cumulative article readership</div>
            </div>
        </section>

        {{-- Pending Submissions Callout --}}
        @if($totalPending > 0)
            <div class="rounded-xl border border-amber-200 bg-amber-50/70 p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold text-amber-900 flex items-center gap-2">
                        <span class="flex h-2 w-2 rounded-full bg-amber-500"></span>
                        <span>{{ $totalPending }} student drafts waiting for moderation</span>
                    </h2>
                    <p class="mt-1 text-[13px] text-amber-800">
                        Help student writers get their stories published on time by reviewing pending articles.
                    </p>
                </div>
                <a href="{{ route('dashboard.guru.queue') }}" class="rounded-md bg-amber-800 px-4 py-2 text-[13px] font-semibold text-white hover:bg-amber-900 transition-colors shrink-0">
                    Review Queue Now &rarr;
                </a>
            </div>
        @endif

        {{-- Main 2-Column: Recently Approved & Moderation Standards --}}
        <div class="grid gap-8 lg:grid-cols-12">
            
            {{-- Left Column: Recently Approved Stories --}}
            <section class="lg:col-span-8 space-y-4">
                <div class="flex items-center justify-between border-b border-line pb-3">
                    <h2 class="text-base font-bold tracking-tight text-ink">Recently Published Stories</h2>
                    <a href="{{ route('dashboard.guru.history') }}" class="text-[12px] font-semibold text-accent hover:underline">
                        View All History ({{ $totalApproved }}) &rarr;
                    </a>
                </div>

                <div class="divide-y divide-line rounded-xl border border-line bg-canvas">
                    @forelse($recentlyApproved as $post)
                        <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 text-[11px]">
                                    <span class="font-bold uppercase tracking-wider text-accent">{{ $post->category->name }}</span>
                                    <span class="text-ink-muted">&middot;</span>
                                    <span class="font-medium text-ink-soft">By {{ $post->author->name }}</span>
                                    <span class="text-ink-faint">&middot; {{ $post->published_at?->translatedFormat('d M Y') }}</span>
                                </div>

                                <h3 class="mt-1 text-[15px] font-bold text-ink truncate">
                                    <a href="{{ route('news.show', $post->slug) }}" class="hover:text-accent transition-colors">{{ $post->title }}</a>
                                </h3>

                                <div class="mt-1.5 text-[11px] text-ink-faint font-medium">
                                    <span>{{ number_format($post->views_count) }} readers</span>
                                </div>
                            </div>

                            <a href="{{ route('news.show', $post->slug) }}" class="rounded border border-line px-3 py-1.5 text-[12px] font-medium text-ink-soft hover:bg-canvas-subtle shrink-0">
                                View Live
                            </a>
                        </div>
                    @empty
                        <div class="p-8 text-center text-[13px] text-ink-faint">
                            No approved stories yet.
                        </div>
                    @endforelse
                </div>
            </section>

            {{-- Right Column: Editorial Standards --}}
            <aside class="lg:col-span-4 space-y-6">
                <div class="rounded-xl border border-line bg-canvas p-5 shadow-xs">
                    <h3 class="text-sm font-bold text-ink border-b border-line pb-2.5">
                        Moderation Criteria
                    </h3>
                    <ul class="mt-3.5 space-y-3 text-[12px] text-ink-soft">
                        <li class="flex items-start gap-2">
                            <span class="text-emerald-700 font-bold">&check;</span>
                            <span><strong>Factual &amp; Educational:</strong> Content is accurate, constructive, and represents school values.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-emerald-700 font-bold">&check;</span>
                            <span><strong>Respectful Language:</strong> Proper grammar, free from harassment or inappropriate expressions.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-emerald-700 font-bold">&check;</span>
                            <span><strong>Constructive Feedback:</strong> If rejecting, provide actionable notes to encourage the student writer.</span>
                        </li>
                    </ul>
                </div>

                <div class="rounded-xl border border-line bg-canvas p-5 shadow-xs">
                    <h3 class="text-sm font-bold text-ink border-b border-line pb-2.5">
                        Quick Links
                    </h3>
                    <div class="mt-3 space-y-2 text-[12px]">
                        <a href="{{ route('dashboard.guru.queue') }}" class="block font-semibold text-accent hover:underline">
                            &rarr; Moderation Queue ({{ $totalPending }})
                        </a>
                        <a href="{{ route('dashboard.guru.history') }}" class="block font-semibold text-accent hover:underline">
                            &rarr; Published History ({{ $totalApproved }})
                        </a>
                        <a href="{{ route('home') }}" class="block font-semibold text-ink-soft hover:underline">
                            &rarr; Public School News Home
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</x-layouts.dashboard>
