<x-layouts.app :title="$post->title">
    <article class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
        {{-- Preview Banner for Unapproved Articles --}}
        @if($post->status !== 'approved')
            <div class="mb-6 rounded-xl border p-4 sm:p-5 {{ $post->status === 'pending' ? 'border-amber-200 bg-amber-50 text-amber-900' : 'border-red-200 bg-red-50 text-red-900' }}">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2 font-bold text-[13px]">
                            @if($post->status === 'pending')
                                <span class="flex h-2 w-2 rounded-full bg-amber-500"></span>
                                <span>Draft Preview   Pending Teacher Moderation</span>
                            @else
                                <span class="flex h-2 w-2 rounded-full bg-danger"></span>
                                <span>Draft Preview   Needs Student Revision</span>
                            @endif
                        </div>
                        <p class="mt-1 text-[12px] text-ink-soft">
                            @if($post->status === 'pending')
                                This article is currently unlisted and visible only to you, teachers, and administrators.
                            @else
                                @if($post->rejection_reason)
                                    <span class="font-semibold text-red-800">Teacher Correction Note:</span> {{ $post->rejection_reason }}
                                @else
                                    This draft was returned by a teacher and is not published on the public portal.
                                @endif
                            @endif
                        </p>
                    </div>

                    @if(auth()->check() && (auth()->id() === $post->user_id || auth()->user()->isAdmin()))
                        <a href="{{ route('dashboard.siswa.edit', $post) }}" class="inline-flex items-center gap-1.5 rounded-md bg-slate-900 px-3.5 py-1.5 text-[12px] font-semibold text-white hover:bg-slate-800 shrink-0">
                            <span>Edit / Revise Story</span>
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </a>
                    @endif
                </div>
            </div>
        @endif

        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-2 text-[12px] text-ink-faint" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="transition-colors hover:text-accent">Beranda</a>
            <span class="text-slate-300">/</span>
            <a href="{{ route('category', $post->category->slug) }}" class="transition-colors hover:text-accent">{{ $post->category->name }}</a>
        </nav>

        {{-- Article Header --}}
        <header class="mt-4">
            <div class="flex items-center gap-2">
                <a href="{{ route('category', $post->category->slug) }}" class="text-[12px] font-bold uppercase tracking-wider text-accent hover:underline">
                    {{ $post->category->name }}
                </a>
            </div>

            <h1 class="mt-2 text-3xl font-extrabold leading-[1.25] tracking-tight text-ink sm:text-[38px]">
                {{ $post->title }}
            </h1>

            {{-- Byline & Timestamp --}}
            <div class="mt-5 flex flex-wrap items-center justify-between gap-4 border-y border-line py-3.5 text-[13px] text-ink-faint">
                <div class="flex items-center gap-2.5">
                    @if($post->author->profile_photo)
                        <img src="{{ Storage::url($post->author->profile_photo) }}" alt="{{ $post->author->name }}" class="h-8 w-8 rounded-full object-cover">
                    @else
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-800 text-[11px] font-bold text-white">
                            {{ mb_substr($post->author->name, 0, 1) }}
                        </span>
                    @endif
                    <div>
                        <span class="font-bold text-ink">{{ $post->author->name }}</span>
                        <span class="text-[11px] text-ink-faint">({{ ucfirst($post->author->role) }})</span>
                    </div>
                </div>

                <div class="flex items-center gap-3 text-[12px]">
                    <span>{{ $post->published_at?->translatedFormat('l, d F Y') }}</span>
                    <span>&middot;</span>
                    <span>{{ number_format($post->views_count) }} dibaca</span>
                </div>
            </div>
        </header>

        {{-- Cover Image --}}
        @if($post->cover_image)
            @php
                $coverUrl = Str::startsWith($post->cover_image, ['http://', 'https://'])
                    ? $post->cover_image
                    : Storage::url($post->cover_image);
            @endphp
            <figure class="mt-8">
                <img src="{{ $coverUrl }}" alt="{{ $post->title }}" class="aspect-[16/9] w-full rounded-lg object-cover border border-line">
            </figure>
        @endif

        {{-- Main Article Prose --}}
        <div class="prose-news mt-8">
            {!! $post->content !!}
        </div>

        {{-- Tags Section --}}
        @if($post->tags->isNotEmpty())
            <div class="mt-10 flex flex-wrap items-center gap-2 border-t border-line pt-6 text-[13px]">
                <span class="font-bold text-ink-soft">Topik Terkait:</span>
                @foreach($post->tags as $tag)
                    <a href="{{ route('tag', $tag->slug) }}" class="rounded bg-canvas-subtle px-2.5 py-1 text-[12px] font-medium text-ink-soft transition-colors hover:bg-accent-soft hover:text-accent">
                        #{{ $tag->name }}
                    </a>
                @endforeach
            </div>
        @endif
    </article>

    {{-- Related News Section --}}
    @if($related->isNotEmpty())
        <section class="border-t border-line bg-canvas-alt/50 py-12">
            <div class="mx-auto max-w-4xl px-4 sm:px-6">
                <div class="border-b-2 border-slate-900 pb-2">
                    <h2 class="text-lg font-bold tracking-tight text-ink">Berita Terkait Lainnya</h2>
                </div>

                <div class="mt-6 grid gap-6 sm:grid-cols-3">
                    @foreach($related as $r)
                        <article class="group">
                            <a href="{{ route('news.show', $r->slug) }}" class="block overflow-hidden rounded-md bg-canvas-subtle">
                                <x-post-cover :post="$r" ratio="16/9" size="24" />
                            </a>
                            <div class="mt-2.5">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-accent">{{ $r->category->name }}</span>
                                <h3 class="mt-1 text-[14px] font-bold leading-snug text-ink group-hover:text-accent transition-colors">
                                    <a href="{{ route('news.show', $r->slug) }}">{{ $r->title }}</a>
                                </h3>
                                <p class="mt-1 text-[11px] text-ink-faint">
                                    {{ $r->published_at?->translatedFormat('d M Y') }}
                                </p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts.app>
