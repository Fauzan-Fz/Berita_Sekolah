<x-layouts.dashboard title="Moderation Queue">
    <div class="space-y-6">
        <nav class="flex items-center gap-2 text-[12px] text-ink-faint">
            <a href="{{ route('dashboard.guru') }}" class="hover:text-accent">Dashboard</a>
            <span>/</span>
            <span class="text-ink">Moderation Queue</span>
        </nav>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-line pb-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-ink">Moderation Queue</h1>
                <p class="mt-1 text-[13px] text-ink-faint">
                    Review and verify student article submissions before they are published to the public portal.
                </p>
            </div>
            @if($pending->isNotEmpty())
                <span class="rounded bg-amber-100 px-3 py-1 text-[12px] font-bold text-amber-800 self-start sm:self-auto">
                    {{ $pending->count() }} drafts awaiting review
                </span>
            @endif
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

        <div class="space-y-4">
            @forelse($pending as $post)
                <div class="rounded-xl border border-line bg-canvas shadow-xs overflow-hidden">
                    <div class="p-5 sm:p-6 bg-canvas border-b border-line">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2 text-[12px]">
                                    <span class="font-bold uppercase tracking-wider text-accent text-[11px]">{{ $post->category->name }}</span>
                                    <span class="text-ink-muted">&middot;</span>
                                    <span class="font-semibold text-ink-soft">By {{ $post->author->name }}</span>
                                    <span class="text-ink-faint">&middot; Submitted {{ $post->created_at->translatedFormat('d M Y') }}</span>
                                </div>
                                <h2 class="mt-1.5 text-lg font-bold tracking-tight text-ink">
                                    {{ $post->title }}
                                </h2>
                            </div>

                            @if($post->tags->isNotEmpty())
                                <div class="flex flex-wrap gap-1 text-[11px]">
                                    @foreach($post->tags as $tag)
                                        <span class="rounded bg-canvas-subtle px-2 py-0.5 text-ink-faint">#{{ $tag->name }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="p-5 sm:p-6 bg-canvas-alt/20 space-y-4">
                        @if($post->cover_image)
                            @php
                                $coverUrl = Str::startsWith($post->cover_image, ['http://', 'https://'])
                                    ? $post->cover_image
                                    : Storage::url($post->cover_image);
                            @endphp
                            <div class="max-w-md">
                                <span class="block text-[11px] font-semibold text-ink-faint mb-1">Attached Cover Image:</span>
                                <img src="{{ $coverUrl }}" alt="{{ $post->title }}" class="h-40 w-auto rounded-md object-cover border border-line">
                            </div>
                        @endif

                        <div>
                            <span class="block text-[11px] font-semibold text-ink-faint mb-1.5">Article Content Preview:</span>
                            <div class="rounded-lg border border-line bg-canvas p-5 max-h-80 overflow-y-auto prose-news text-[14px]">
                                {!! $post->content !!}
                            </div>
                        </div>

                        <div class="pt-2 flex flex-wrap items-center gap-3">
                            <form method="POST" action="{{ route('dashboard.posts.approve', $post) }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1.5 rounded-md bg-accent px-4 py-2 text-[13px] font-semibold text-white hover:bg-accent-hover transition-colors shadow-xs">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    <span>Approve &amp; Publish Story</span>
                                </button>
                            </form>

                            <button type="button" onclick="document.getElementById('reject-box-{{ $post->id }}').classList.toggle('hidden')" class="rounded-md border border-line bg-canvas px-4 py-2 text-[13px] font-semibold text-danger hover:bg-red-50 hover:border-red-200 transition-colors">
                                Return with Correction Note...
                            </button>

                            <a href="{{ route('dashboard.posts.preview', $post) }}" target="_blank" class="rounded-md border border-line px-3 py-2 text-[13px] font-medium text-ink-soft hover:bg-canvas-subtle">
                                Preview on Site &rarr;
                            </a>
                        </div>

                        <div id="reject-box-{{ $post->id }}" class="hidden pt-3 border-t border-line">
                            <form method="POST" action="{{ route('dashboard.posts.reject', $post) }}" class="rounded-lg border border-red-200 bg-red-50/60 p-4 space-y-3">
                                @csrf
                                <div>
                                    <label for="reason-{{ $post->id }}" class="block text-[13px] font-bold text-red-900">
                                        Correction Feedback for {{ $post->author->name }}:
                                    </label>
                                    <p class="text-[11px] text-red-700 mt-0.5">
                                        Specify the exact sections needing revision. The student writer will see this note in their dashboard.
                                    </p>
                                    <textarea id="reason-{{ $post->id }}" name="rejection_reason" rows="3" required placeholder="e.g. Please clarify paragraph 2 regarding the event date, and ensure proper punctuation..."
                                              class="mt-2 w-full rounded-md border border-red-300 bg-white p-3 text-[13px] text-ink focus:border-danger focus:outline-none focus:ring-1 focus:ring-danger"></textarea>
                                </div>
                                <div class="flex justify-end gap-2">
                                    <button type="button" onclick="document.getElementById('reject-box-{{ $post->id }}').classList.add('hidden')" class="rounded border border-line bg-white px-3 py-1.5 text-[12px] font-medium text-ink-soft hover:bg-canvas-subtle">
                                        Cancel
                                    </button>
                                    <button type="submit" class="rounded bg-danger px-4 py-1.5 text-[12px] font-semibold text-white hover:bg-red-700 transition-colors">
                                        Send Rejection Feedback
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-line bg-canvas p-16 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 mb-3">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <h2 class="text-base font-bold text-ink">Moderation Queue is Clear!</h2>
                    <p class="mt-1 text-[13px] text-ink-faint">
                        All student article submissions have been reviewed and processed.
                    </p>
                    <div class="mt-4">
                        <a href="{{ route('dashboard.guru.history') }}" class="text-[12px] font-semibold text-accent hover:underline">
                            View Published History &rarr;
                        </a>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</x-layouts.dashboard>
