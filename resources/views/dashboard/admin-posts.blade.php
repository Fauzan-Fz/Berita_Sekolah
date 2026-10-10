<x-layouts.dashboard title="All Posts">
    <div class="space-y-6">
        <nav class="flex items-center gap-2 text-[12px] text-ink-faint">
            <a href="{{ route('dashboard.admin') }}" class="hover:text-accent">Dashboard</a>
            <span>/</span>
            <span class="text-ink">All Posts</span>
        </nav>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-line pb-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-ink">All Posts</h1>
                <p class="mt-1 text-[13px] text-ink-faint">
                    Audit, curate status, or manage every article published or drafted by students and teachers.
                </p>
            </div>
            <a href="{{ route('dashboard.siswa.create') }}" class="inline-flex items-center gap-2 rounded-md bg-accent px-4 py-2 text-[13px] font-semibold text-white hover:bg-accent-hover shadow-xs transition-colors shrink-0">
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
        @if($errors->any())
            <div class="rounded-md border border-danger/30 bg-danger-soft px-4 py-3 text-[13px] font-medium text-danger">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="overflow-hidden rounded-xl border border-line bg-canvas shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="border-b border-line bg-canvas-alt text-[11px] font-bold uppercase tracking-wider text-ink-faint">
                        <tr>
                            <th class="px-5 py-3.5">Story Title &amp; Details</th>
                            <th class="hidden px-5 py-3.5 sm:table-cell">Author</th>
                            <th class="hidden px-5 py-3.5 md:table-cell">Category</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse($posts as $post)
                            <tr class="transition-colors hover:bg-canvas-alt/40">
                                <td class="px-5 py-3.5">
                                    <a href="{{ $post->status === 'approved' ? route('news.show', $post->slug) : route('dashboard.posts.preview', $post) }}" class="text-[13px] font-bold text-ink hover:text-accent">
                                        {{ $post->title }}
                                    </a>
                                    <div class="mt-0.5 text-[11px] text-ink-faint">
                                        {{ $post->created_at->translatedFormat('d M Y') }} &middot; {{ number_format($post->views_count) }} reads
                                    </div>
                                </td>
                                <td class="hidden px-5 py-3.5 text-[13px] font-medium text-ink-soft sm:table-cell">
                                    {{ $post->author->name }}
                                </td>
                                <td class="hidden px-5 py-3.5 text-[12px] md:table-cell">
                                    <span class="font-semibold text-accent">{{ $post->category->name }}</span>
                                </td>
                                <td class="px-5 py-3.5 text-[12px]">
                                    @if($post->status === 'pending')
                                        <span class="inline-flex items-center gap-1 font-semibold text-amber-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                            Pending
                                        </span>
                                    @elseif($post->status === 'approved')
                                        <span class="inline-flex items-center gap-1 font-semibold text-emerald-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                                            Published
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 font-semibold text-danger">
                                            <span class="h-1.5 w-1.5 rounded-full bg-danger"></span>
                                            Rejected
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        @if($post->status !== 'approved')
                                            <form method="POST" action="{{ route('dashboard.posts.approve', $post) }}">
                                                @csrf
                                                <button type="submit" class="rounded border border-line bg-canvas px-2.5 py-1 text-[11px] font-semibold text-accent hover:bg-canvas-subtle">
                                                    Publish
                                                </button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('dashboard.posts.destroy', $post) }}" onsubmit="return confirm('Are you sure you want to permanently delete this story?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded border border-line px-2.5 py-1 text-[11px] font-medium text-ink-faint hover:bg-red-50 hover:text-danger hover:border-red-200">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-12 text-center text-[13px] text-ink-faint">
                                    No articles found on the portal.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.dashboard>
