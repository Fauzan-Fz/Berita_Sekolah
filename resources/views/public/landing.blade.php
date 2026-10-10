<x-layouts.app title="Lentera Sekolah   Portal Berita &amp; Karya Siswa">
    <div class="mx-auto max-w-6xl px-4 sm:px-6">

        {{-- Lead Story (Berita Utama Terkini) --}}
        @if($featured)
        <section class="pt-8 sm:pt-10" aria-labelledby="lead-story-heading">
            <article class="group grid overflow-hidden rounded-xl border border-line bg-canvas transition-colors lg:grid-cols-12">
                {{-- Cover Image --}}
                <a href="{{ route('news.show', $featured->slug) }}" class="focus-ring block overflow-hidden bg-canvas-subtle lg:col-span-7">
                    @if($featured->cover_image)
                        @php
                            $coverUrl = Str::startsWith($featured->cover_image, ['http://', 'https://'])
                                ? $featured->cover_image
                                : Storage::url($featured->cover_image);
                        @endphp
                        <img src="{{ $coverUrl }}" alt="{{ $featured->title }}"
                             width="1280" height="800"
                             class="aspect-[16/10] h-full w-full object-cover transition-transform duration-300 ease-out group-hover:scale-[1.02] lg:aspect-auto">
                    @else
                        <div class="flex aspect-[16/10] h-full w-full items-center justify-center bg-canvas-subtle text-ink-muted text-4xl font-bold lg:aspect-auto">
                            {{ mb_substr($featured->category->name, 0, 1) }}
                        </div>
                    @endif
                </a>

                {{-- Editorial Details --}}
                <div class="flex flex-col justify-between p-6 sm:p-8 lg:col-span-5">
                    <div>
                        <div class="flex items-center gap-2 text-[12px]">
                            <a href="{{ route('category', $featured->category->slug) }}" class="font-bold uppercase tracking-wider text-accent hover:underline">
                                {{ $featured->category->name }}
                            </a>
                            <span class="text-ink-muted">&middot;</span>
                            <span class="text-ink-faint">{{ $featured->published_at?->translatedFormat('d M Y') }}</span>
                        </div>

                        <h1 id="lead-story-heading" class="mt-3 text-2xl font-bold leading-snug tracking-tight text-ink sm:text-[28px]">
                            <a href="{{ route('news.show', $featured->slug) }}" class="focus-ring transition-colors hover:text-accent">
                                {{ $featured->title }}
                            </a>
                        </h1>

                        <p class="mt-3 text-[14px] leading-relaxed text-ink-soft">
                            {{ Str::limit(strip_tags($featured->content), 160) }}
                        </p>
                    </div>

                    <div class="mt-6 flex items-center justify-between border-t border-line-soft pt-4 text-[12px] text-ink-faint">
                        <div class="flex items-center gap-2">
                            @if($featured->author->profile_photo)
                                <img src="{{ Storage::url($featured->author->profile_photo) }}" alt="{{ $featured->author->name }}" class="h-6 w-6 rounded-full object-cover">
                            @else
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-800 text-[10px] font-bold text-white">{{ mb_substr($featured->author->name, 0, 1) }}</span>
                            @endif
                            <span class="font-medium text-ink-soft">{{ $featured->author->name }}</span>
                        </div>
                        <span>{{ number_format($featured->views_count) }} dibaca</span>
                    </div>
                </div>
            </article>
        </section>
        @endif

        {{-- Main Grid: Berita Terbaru & Sidebar Trending --}}
        <div class="mt-16 grid gap-12 lg:grid-cols-12">
            
            {{-- Kolom Kiri: Berita Terbaru (8 kolom) --}}
            <section id="terbaru" class="lg:col-span-8">
                <div class="flex items-baseline justify-between border-b-2 border-slate-900 pb-3">
                    <h2 class="text-xl font-bold tracking-tight text-ink sm:text-2xl">Berita Terbaru</h2>
                    <span class="text-[12px] font-medium text-ink-faint">{{ $latest->count() }} artikel</span>
                </div>

                <div class="mt-6 divide-y divide-line">
                    @foreach($latest as $post)
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

                                    <h3 class="mt-1.5 text-[17px] font-bold leading-snug tracking-tight text-ink">
                                        <a href="{{ route('news.show', $post->slug) }}" class="focus-ring transition-colors hover:text-accent">
                                            {{ $post->title }}
                                        </a>
                                    </h3>

                                    <p class="mt-2 text-[13px] leading-relaxed text-ink-soft line-clamp-2">
                                        {{ Str::limit(strip_tags($post->content), 120) }}
                                    </p>

                                    <div class="mt-3 flex items-center gap-3 text-[11px] text-ink-faint">
                                        <span class="font-medium text-ink-soft">{{ $post->author->name }}</span>
                                        <span>&middot;</span>
                                        <span>{{ number_format($post->views_count) }} kali dibaca</span>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            {{-- Kolom Kanan: Trending & Kategori (4 kolom) --}}
            <aside class="space-y-12 lg:col-span-4">
                
                {{-- Seksi Trending Mingguan --}}
                <section id="trending">
                    <div class="flex items-baseline justify-between border-b-2 border-slate-900 pb-3">
                        <h2 class="text-lg font-bold tracking-tight text-ink">Trending Minggu Ini</h2>
                    </div>

                    <ol class="mt-4 divide-y divide-line">
                        @foreach($trending as $i => $post)
                            <li class="group flex items-start gap-4 py-4 first:pt-2">
                                <span class="text-2xl font-black tabular-nums text-slate-300 leading-none select-none">
                                    {{ $i + 1 }}
                                </span>

                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('category', $post->category->slug) }}" class="text-[11px] font-bold uppercase tracking-wider text-accent hover:underline">
                                        {{ $post->category->name }}
                                    </a>
                                    <h3 class="mt-1 text-[14px] font-bold leading-snug text-ink">
                                        <a href="{{ route('news.show', $post->slug) }}" class="focus-ring transition-colors hover:text-accent">
                                            {{ $post->title }}
                                        </a>
                                    </h3>
                                    <div class="mt-1.5 flex items-center gap-2 text-[11px] text-ink-faint">
                                        <span>{{ $post->author->name }}</span>
                                        <span>&middot;</span>
                                        <span>{{ number_format($post->views_count) }} dibaca</span>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </section>

                {{-- Seksi Kategori Berita --}}
                <section id="kategori">
                    <div class="border-b-2 border-slate-900 pb-3">
                        <h2 class="text-lg font-bold tracking-tight text-ink">Kategori Berita</h2>
                    </div>

                    <div class="mt-4 divide-y divide-line">
                        @foreach($categories->where('posts_count', '>', 0) as $cat)
                            <a href="{{ route('category', $cat->slug) }}" class="focus-ring group flex items-center justify-between py-3 text-[14px] text-ink-soft transition-colors hover:text-accent">
                                <span class="font-medium group-hover:underline">{{ $cat->name }}</span>
                                <span class="rounded bg-canvas-subtle px-2 py-0.5 text-[11px] font-medium text-ink-faint">{{ $cat->posts_count }}</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            </aside>
        </div>
    </div>
</x-layouts.app>
