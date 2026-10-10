<x-layouts.dashboard title="Published History">
    <div class="space-y-6">
        <nav class="flex items-center gap-2 text-[12px] text-ink-faint">
            <a href="{{ route('dashboard.guru') }}" class="hover:text-accent">Dashboard</a>
            <span>/</span>
            <span class="text-ink">Published History</span>
        </nav>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-line pb-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-ink">Published History</h1>
                <p class="mt-1 text-[13px] text-ink-faint">
                    Audit of all student and official articles published on the school portal.
                </p>
            </div>
            <span class="rounded bg-canvas-subtle px-3 py-1 text-[12px] font-semibold text-ink-faint self-start sm:self-auto">
                {{ $approvedPosts->total() }} published stories
            </span>
        </div>

        <div class="overflow-hidden rounded-xl border border-line bg-canvas shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="border-b border-line bg-canvas-alt text-[11px] font-bold uppercase tracking-wider text-ink-faint">
                        <tr>
                            <th class="px-5 py-3.5">Story Title</th>
                            <th class="px-5 py-3.5">Author</th>
                            <th class="px-5 py-3.5">Category</th>
                            <th class="px-5 py-3.5">Published Date</th>
                            <th class="px-5 py-3.5">Readership</th>
                            <th class="px-5 py-3.5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse($approvedPosts as $post)
                            <tr class="transition-colors hover:bg-canvas-alt/40">
                                <td class="px-5 py-3.5">
                                    <a href="{{ route('news.show', $post->slug) }}" class="text-[13px] font-bold text-ink hover:text-accent">
                                        {{ $post->title }}
                                    </a>
                                </td>
                                <td class="px-5 py-3.5 text-[13px] font-medium text-ink-soft">
                                    {{ $post->author->name }}
                                </td>
                                <td class="px-5 py-3.5 text-[12px]">
                                    <span class="font-semibold text-accent">{{ $post->category->name }}</span>
                                </td>
                                <td class="px-5 py-3.5 text-[12px] text-ink-faint">
                                    {{ $post->published_at?->translatedFormat('d M Y') }}
                                </td>
                                <td class="px-5 py-3.5 text-[12px] font-semibold text-ink-soft">
                                    {{ number_format($post->views_count) }} reads
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <a href="{{ route('news.show', $post->slug) }}" class="rounded border border-line px-2.5 py-1 text-[11px] font-medium text-ink-soft hover:bg-canvas-subtle">
                                        View Live
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-12 text-center text-[13px] text-ink-faint">
                                    No published articles found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $approvedPosts->links() }}
        </div>
    </div>
</x-layouts.dashboard>
