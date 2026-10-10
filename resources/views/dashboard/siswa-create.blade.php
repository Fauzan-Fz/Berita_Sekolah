<x-layouts.dashboard title="Create Post">
    <div class="max-w-4xl space-y-6">
        <nav class="flex items-center gap-2 text-[12px] text-ink-faint">
            <a href="{{ route('dashboard.siswa') }}" class="hover:text-accent">Dashboard</a>
            <span>/</span>
            <span class="text-ink">Create Post</span>
        </nav>

        <div class="border-b border-line pb-4">
            <h1 class="text-2xl font-bold tracking-tight text-ink">Create New Article</h1>
            <p class="mt-1 text-[13px] text-ink-faint">
                Write your article draft below. Your submission will be routed to a teacher for review before going live.
            </p>
        </div>

        @if($errors->any())
            <div class="rounded-md border border-danger/30 bg-danger-soft px-4 py-3 text-[13px] font-medium text-danger">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('dashboard.posts.store') }}" enctype="multipart/form-data" class="space-y-6 rounded-xl border border-line bg-canvas p-6 sm:p-8 shadow-xs">
            @csrf

            <div class="space-y-5">
                <div>
                    <label for="title" class="block text-[13px] font-semibold text-ink">Article Title</label>
                    <input type="text" id="title" name="title" value="{{ old('title') }}" required placeholder="e.g. Science Club Wins Regional Olympiad"
                           class="mt-1.5 w-full rounded-md border border-line bg-canvas px-3.5 py-2.5 text-[15px] text-ink placeholder:text-ink-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="category_id" class="block text-[13px] font-semibold text-ink">Category</label>
                        <select id="category_id" name="category_id" required
                                class="mt-1.5 w-full rounded-md border border-line bg-canvas px-3.5 py-2.5 text-[14px] text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" @selected(old('category_id') == $cat->id)>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="tags" class="block text-[13px] font-semibold text-ink">Tags / Keywords (comma-separated)</label>
                        <input type="text" id="tags" name="tags" value="{{ old('tags') }}" placeholder="e.g. science, award, competition"
                               class="mt-1.5 w-full rounded-md border border-line bg-canvas px-3.5 py-2.5 text-[14px] text-ink placeholder:text-ink-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
                    </div>
                </div>

                <div>
                    <label for="cover_image" class="block text-[13px] font-semibold text-ink">Cover Photo (Optional, JPG/PNG max 4MB)</label>
                    <div class="mt-1.5 flex flex-col sm:flex-row items-start sm:items-center gap-4">
                        <input type="file" id="cover_image" name="cover_image" accept="image/*" onchange="previewCover(this)"
                               class="text-[13px] text-ink-faint file:mr-3 file:rounded-md file:border file:border-line file:bg-canvas-subtle file:px-3.5 file:py-2 file:text-[13px] file:font-semibold file:text-ink-soft hover:file:bg-canvas-alt file:transition-colors">
                        <div id="cover-preview-box" class="hidden">
                            <img id="cover-preview-img" src="" alt="Cover preview" class="h-16 w-28 rounded-md object-cover border border-line">
                        </div>
                    </div>
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

                    <textarea id="content" name="content" rows="12" required placeholder="Write your full article here..."
                              oninput="updateCount(this, 'word-counter')"
                              class="mt-1.5 w-full rounded-md border border-line bg-canvas p-4 text-[15px] leading-relaxed text-ink placeholder:text-ink-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">{{ old('content') }}</textarea>

                    <div class="mt-1.5 flex justify-between text-[11px] text-ink-faint">
                        <span>Basic HTML formatting is supported (paragraphs, headings, lists).</span>
                        <span id="word-counter">0 words</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-3 border-t border-line">
                <button type="submit" class="rounded-md bg-accent px-5 py-2.5 text-[13px] font-semibold text-white transition-colors hover:bg-accent-hover shadow-xs">
                    Submit for Review
                </button>
                <a href="{{ route('dashboard.siswa') }}" class="rounded-md border border-line px-4 py-2.5 text-[13px] font-medium text-ink-soft hover:bg-canvas-subtle">
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

        function updateCount(textarea, counterId) {
            const val = textarea.value.trim();
            const words = val.length ? val.split(/\s+/).length : 0;
            const counter = document.getElementById(counterId);
            if (counter) counter.textContent = words + ' words';
        }

        function previewCover(input) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('cover-preview-img').src = e.target.result;
                    document.getElementById('cover-preview-box').classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            }
        }
    </script>
</x-layouts.dashboard>
