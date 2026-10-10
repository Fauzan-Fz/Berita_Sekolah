<x-layouts.dashboard title="Categories">
    <div class="space-y-6">
        <nav class="flex items-center gap-2 text-[12px] text-ink-faint">
            <a href="{{ route('dashboard.admin') }}" class="hover:text-accent">Dashboard</a>
            <span>/</span>
            <span class="text-ink">Categories</span>
        </nav>

        <div class="border-b border-line pb-4">
            <h1 class="text-2xl font-bold tracking-tight text-ink">News Categories</h1>
            <p class="mt-1 text-[13px] text-ink-faint">
                Organize school news and student journalism into official thematic channels.
            </p>
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

        <form method="POST" action="{{ route('dashboard.categories.store') }}" class="flex flex-col sm:flex-row items-stretch sm:items-end gap-3 rounded-xl border border-line bg-canvas p-5 shadow-xs">
            @csrf
            <div class="flex-1">
                <label for="name" class="block text-[13px] font-semibold text-ink">New Category Name</label>
                <input type="text" id="name" name="name" required placeholder="e.g. Science Olympiad, Arts &amp; Culture, Extracurricular..."
                       class="mt-1.5 w-full rounded-md border border-line bg-canvas px-3.5 py-2 text-[14px] text-ink placeholder:text-ink-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
            </div>
            <button type="submit" class="rounded-md bg-accent px-4 py-2 text-[13px] font-semibold text-white hover:bg-accent-hover transition-colors shrink-0 shadow-xs">
                Add Category
            </button>
        </form>

        <div class="overflow-hidden rounded-xl border border-line bg-canvas shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="border-b border-line bg-canvas-alt text-[11px] font-bold uppercase tracking-wider text-ink-faint">
                        <tr>
                            <th class="px-5 py-3.5">Category Name</th>
                            <th class="px-5 py-3.5">URL Slug</th>
                            <th class="px-5 py-3.5">Articles</th>
                            <th class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse($categories as $cat)
                            <tr class="transition-colors hover:bg-canvas-alt/40">
                                <td class="px-5 py-3.5 text-[13px] font-bold text-ink">{{ $cat->name }}</td>
                                <td class="px-5 py-3.5 text-[12px] font-mono text-ink-faint">/kategori/{{ $cat->slug }}</td>
                                <td class="px-5 py-3.5 text-[12px] text-ink-soft">{{ $cat->posts_count }} stories</td>
                                <td class="px-5 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <details class="relative">
                                            <summary class="cursor-pointer list-none rounded border border-line px-2.5 py-1 text-[11px] font-medium text-ink-soft hover:bg-canvas-subtle">
                                                Edit
                                            </summary>
                                            <form method="POST" action="{{ route('dashboard.categories.update', $cat) }}" class="absolute right-0 z-20 mt-2 w-64 rounded-lg border border-line bg-canvas p-4 shadow-float text-left">
                                                @csrf
                                                @method('PUT')
                                                <label class="block text-[12px] font-bold text-ink">Rename Category</label>
                                                <input name="name" value="{{ $cat->name }}" required
                                                       class="mt-1 w-full rounded border border-line bg-canvas px-3 py-1.5 text-[13px] text-ink focus:border-accent focus:outline-none">
                                                <button type="submit" class="mt-2.5 w-full rounded bg-accent px-3 py-1.5 text-[12px] font-semibold text-white hover:bg-accent-hover">
                                                    Save Changes
                                                </button>
                                            </form>
                                        </details>
                                        <form method="POST" action="{{ route('dashboard.categories.destroy', $cat) }}" onsubmit="return confirm('Delete this category?')">
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
                                <td colspan="4" class="p-12 text-center text-[13px] text-ink-faint">
                                    No categories created yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.dashboard>
