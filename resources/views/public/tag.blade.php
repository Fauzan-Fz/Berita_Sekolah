<x-layouts.app :title="'#'.$tag->name">
    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
        <nav class="flex items-center gap-2 text-[12px] text-ink-faint" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="transition-colors hover:text-accent">Beranda</a>
            <span class="text-slate-300">/</span>
            <span>Tag</span>
        </nav>

        <header class="mt-4 border-b-2 border-slate-900 pb-4">
            <div class="flex items-baseline justify-between">
                <div>
                    <span class="text-[12px] font-bold uppercase tracking-wider text-accent">Topik Berita</span>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-ink sm:text-3xl">#{{ $tag->name }}</h1>
                </div>
                <span class="text-[13px] font-medium text-ink-faint">{{ $posts->total() }} berita</span>
            </div>
        </header>

        <div class="mt-6 divide-y divide-line">
            @forelse($posts as $post)
                <article class="group py-6 first:pt-2">
                    <div class="grid gap-4 sm:grid-cols-12 sm:items-center">
                        <a href="{{ route('news.show', $post->slug) }}" class="focus-ring block overflow-hidden rounded-md bg-canvas-subtle sm:col-span-4">
                            <x-post-cover :post="$post" ratio="16/10" size="28" />
                        </a>

                        <div class="flex flex-col justify-center sm:col-span-8">
                            <div class="flex items-center gap-2 text-[12px]">
                                <a href="{{ route('category', $post->category->slug) }}" class="font-bold uppercase tracking-wider text-accent hover:underline">
                                    {{ $post->category->name }}
                                </a>
                                <span class="text-ink-muted">&middot;</span>
                                <span class="text-ink-faint">{{ $post->published_at?->translatedFormat('d M Y') }}</span>
                            </div>

                            <h2 class="mt-1.5 text-[18px] font-bold leading-snug tracking-tight text-ink">
                                <a href="{{ route('news.show', $post->slug) }}" class="focus-ring transition-colors hover:text-accent">
                                    {{ $post->title }}
                                </a>
                            </h2>

                            <p class="mt-2 text-[13px] leading-relaxed text-ink-soft line-clamp-2">
                                {{ Str::limit(strip_tags($post->content), 140) }}
                            </p>

                            <div class="mt-3 text-[12px] text-ink-faint">
                                <span>Penulis: </span>
                                <span class="font-medium text-ink-soft">{{ $post->author->name }}</span>
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="py-16 text-center text-[14px] text-ink-faint">
                    Belum ada berita dengan tag ini.
                </div>
            @endforelse
        </div>

        <div class="mt-10 border-t border-line pt-6">
            {{ $posts->links() }}
        </div>
    </div>
</x-layouts.app>
