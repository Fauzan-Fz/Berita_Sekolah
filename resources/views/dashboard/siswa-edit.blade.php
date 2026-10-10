<x-layouts.dashboard title="Edit Article">
    <div class="max-w-4xl space-y-6">
        <nav class="flex items-center gap-2 text-[12px] text-ink-faint">
            <a href="{{ route('dashboard.siswa') }}" class="hover:text-accent">Dashboard</a>
            <span>/</span>
            <a href="{{ route('dashboard.siswa.posts') }}" class="hover:text-accent">My Articles</a>
            <span>/</span>
            <span class="text-ink">Edit</span>
        </nav>

        <div class="border-b border-line pb-4">
            <h1 class="text-2xl font-bold tracking-tight text-ink">Edit Article</h1>
            <p class="mt-1 text-[13px] text-ink-faint">
                Make changes or address teacher feedback. Saving updates will re-submit this draft for review.
            </p>
        </div>

        @if($post->status === 'rejected' && $post->rejection_reason)
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-[13px] text-red-900">
                <div class="flex items-center gap-2 font-bold text-red-800">
                    <svg class="h-4 w-4 shrink-0 text-danger" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span>Teacher Revision Instructions:</span>
                </div>
                <p class="mt-1.5 pl-6 leading-relaxed">{{ $post->rejection_reason }}</p>
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-md border border-danger/30 bg-danger-soft px-4 py-3 text-[13px] font-medium text-danger">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('dashboard.posts.update', $post) }}" enctype="multipart/form-data" class="space-y-6 rounded-xl border border-line bg-canvas p-6 sm:p-8 shadow-xs">
            @csrf
            @method('PUT')

            <div class="space-y-5">
                <div>
                    <label for="title" class="block text-[13px] font-semibold text-ink">Article Title</label>
                    <input type="text" id="title" name="title" value="{{ old('title', $post->title) }}" required
                           class="mt-1.5 w-full rounded-md border border-line bg-canvas px-3.5 py-2.5 text-[15px] text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="category_id" class="block text-[13px] font-semibold text-ink">Category</label>
                        <select id="category_id" name="category_id" required
                                class="mt-1.5 w-full rounded-md border border-line bg-canvas px-3.5 py-2.5 text-[14px] text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" @selected(old('category_id', $post->category_id) == $cat->id)>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="tags" class="block text-[13px] font-semibold text-ink">Tags / Keywords</label>
                        <input type="text" id="tags" name="tags" value="{{ old('tags', $post->tags->pluck('name')->implode(', ')) }}" placeholder="comma-separated tags"
                               class="mt-1.5 w-full rounded-md border border-line bg-canvas px-3.5 py-2.5 text-[14px] text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
                    </div>
                </div>

                <div>
                    <label for="cover_image" class="block text-[13px] font-semibold text-ink">Replace Cover Photo (Optional)</label>
                    @if($post->cover_image)
                        @php
                            $coverUrl = Str::startsWith($post->cover_image, ['http://', 'https://'])
                                ? $post->cover_image
                                : Storage::url($post->cover_image);
                        @endphp
                        <div class="mt-2 mb-3 flex items-center gap-3">
                            <img src="{{ $coverUrl }}" alt="Current cover" class="h-16 w-28 rounded-md object-cover border border-line">
                            <span class="text-[12px] text-ink-faint">Current cover photo. Uploading a new file will replace it.</span>
                        </div>
                    @endif
                    <input type="file" id="cover_image" name="cover_image" accept="image/*"
                           class="text-[13px] text-ink-faint file:mr-3 file:rounded-md file:border file:border-line file:bg-canvas-subtle file:px-3.5 file:py-2 file:text-[13px] file:font-semibold file:text-ink-soft hover:file:bg-canvas-alt file:transition-colors">
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <label for="content" class="block text-[13px] font-semibold text-ink">Article Body</label>
                        <div class="flex items-center gap-1 text-[11px] text-ink-soft">
                            <button type="button" onclick="insertTag('content', '<strong>', '</strong>')" class="rounded border border-line bg-canvas px-2 py-0.5 font-bold hover:bg-canvas-subtle">Bold</button>
                            <button type="button" onclick="insertTag('content', '<em>', '</em>')" class="rounded border border-line bg-canvas px-2 py-0.5 italic hover:bg-canvas-subtle">Italic</button>
                            <button type="button" onclick="insertTag('content', '<h2>', '</h2>')" class="rounded border border-line bg-canvas px-2 py-0.5 font-semibold hover:bg-canvas-subtle">Heading</button>
                            <button type="button" onclick="insertTag('content', '<blockquote>', '</blockquote>')" class="rounded border border-line bg-canvas px-2 py-0.5 hover:bg-canvas-subtle">Quote</button>
                        </div>
                    </div>

                    <textarea id="content" name="content" rows="12" required
                              class="mt-1.5 w-full rounded-md border border-line bg-canvas p-4 text-[15px] leading-relaxed text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">{{ old('content', $post->content) }}</textarea>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-3 border-t border-line">
                <button type="submit" class="rounded-md bg-accent px-5 py-2.5 text-[13px] font-semibold text-white transition-colors hover:bg-accent-hover shadow-xs">
                    Save &amp; Resubmit Article
                </button>
                <a href="{{ route('dashboard.siswa.posts') }}" class="rounded-md border border-line px-4 py-2.5 text-[13px] font-medium text-ink-soft hover:bg-canvas-subtle">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <script>
        function insertTag(textareaId, openTag, closeTag) {
            const textarea = document.getElementById(textareaId);
            if (!textarea) return;
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            const selected = text.substring(start, end);
            textarea.value = text.substring(0, start) + openTag + selected + closeTag + text.substring(end);
            textarea.focus();
            textarea.setSelectionRange(start + openTag.length, end + openTag.length);
        }
    </script>
</x-layouts.dashboard>
