<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? config('app.name', 'Web Berita Sekolah') }}</title>
        <meta name="description" content="Portal berita resmi sekolah   informasi terbaru, prestasi, dan kegiatan siswa terkurasi.">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-full bg-canvas text-ink flex flex-col font-sans">
        {{-- Main Navigation Bar --}}
        <header class="border-b border-line bg-canvas">
            <nav class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6" aria-label="Navigasi utama">
                {{-- Brand Identity --}}
                <a href="{{ route('home') }}" class="focus-ring group flex items-center gap-3 py-1">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-900 text-emerald-400 border border-slate-800 shadow-sm transition-transform duration-200 group-hover:scale-105">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                            <path d="M6 8h2"/>
                            <path d="M6 12h2"/>
                            <path d="M16 8h2"/>
                            <path d="M16 12h2"/>
                        </svg>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[19px] font-extrabold tracking-tight text-ink leading-none">
                            Lentera<span class="text-accent">Sekolah</span>
                        </span>
                        <span class="text-[11px] font-medium text-ink-faint mt-1 tracking-normal">
                            Warta Jurnalistik &amp; Prestasi
                        </span>
                    </div>
                </a>

                {{-- Desktop Menu Links --}}
                <ul class="hidden items-center gap-6 md:flex">
                    <li>
                        <a href="{{ route('home') }}" class="focus-ring text-[13px] font-medium text-ink-soft transition-colors hover:text-ink">
                            Beranda
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('home') }}#terbaru" class="focus-ring text-[13px] font-medium text-ink-soft transition-colors hover:text-ink">
                            Terbaru
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('home') }}#trending" class="focus-ring text-[13px] font-medium text-ink-soft transition-colors hover:text-ink">
                            Trending
                        </a>
                    </li>
                    <li class="relative">
                        <details class="group relative">
                            <summary class="focus-ring flex cursor-pointer list-none items-center gap-1.5 text-[13px] font-medium text-ink-soft transition-colors hover:text-ink">
                                <span>Kategori</span>
                                <svg class="h-3.5 w-3.5 text-ink-faint transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </summary>
                            <div class="absolute left-0 mt-2 w-52 overflow-hidden rounded-lg border border-line bg-canvas p-1 shadow-card z-50">
                                @foreach($categories as $cat)
                                    <a href="{{ route('category', $cat->slug) }}" class="flex items-center justify-between rounded-md px-3 py-2 text-[13px] text-ink-soft transition-colors hover:bg-canvas-alt hover:text-ink">
                                        <span>{{ $cat->name }}</span>
                                        @if($cat->posts_count > 0)
                                            <span class="rounded bg-canvas-subtle px-1.5 py-0.5 text-[10px] text-ink-faint">{{ $cat->posts_count }}</span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </details>
                    </li>
                </ul>

                {{-- Action / Auth Controls --}}
                <div class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('dashboard.index') }}" class="focus-ring inline-flex items-center gap-1.5 rounded-md bg-accent px-3.5 py-1.5 text-[13px] font-semibold text-white transition-colors hover:bg-accent-hover">
                            <span>Dasbor</span>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="focus-ring px-3 py-1.5 text-[13px] font-medium text-ink-soft transition-colors hover:text-ink">
                            Masuk
                        </a>
                        <a href="{{ route('register') }}" class="focus-ring rounded-md bg-accent px-3.5 py-1.5 text-[13px] font-semibold text-white transition-colors hover:bg-accent-hover">
                            Daftar
                        </a>
                    @endauth

                    <button type="button" class="focus-ring flex h-8 w-8 items-center justify-center rounded-md border border-line text-ink-soft hover:bg-canvas-alt md:hidden" aria-label="Buka menu navigasi" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>
            </nav>

            {{-- Mobile Dropdown Menu --}}
            <div id="mobile-menu" class="hidden border-t border-line bg-canvas px-4 py-3 md:hidden">
                <a href="{{ route('home') }}" class="focus-ring block rounded-md px-3 py-2 text-[14px] text-ink-soft hover:bg-canvas-alt hover:text-ink">Beranda</a>
                <a href="{{ route('home') }}#terbaru" class="focus-ring block rounded-md px-3 py-2 text-[14px] text-ink-soft hover:bg-canvas-alt hover:text-ink">Terbaru</a>
                <a href="{{ route('home') }}#trending" class="focus-ring block rounded-md px-3 py-2 text-[14px] text-ink-soft hover:bg-canvas-alt hover:text-ink">Trending</a>
                <a href="{{ route('home') }}#kategori" class="focus-ring block rounded-md px-3 py-2 text-[14px] text-ink-soft hover:bg-canvas-alt hover:text-ink">Kategori</a>
            </div>

            {{-- Mobile Category Strip --}}
            <nav class="md:hidden border-t border-line bg-canvas-alt px-4 py-2 overflow-x-auto scrollbar-hide" aria-label="Kategori berita (mobile)">
                <ul class="flex gap-1.5 min-w-max" role="list">
                    @foreach($categories->where('posts_count', '>', 0) as $cat)
                        <li>
                            <a href="{{ route('category', $cat->slug) }}" class="focus-ring inline-flex items-center gap-1.5 whitespace-nowrap rounded-md border border-line bg-canvas px-2.5 py-1 text-[12px] text-ink-soft transition-colors hover:border-accent hover:text-accent" aria-label="{{ $cat->name }} ({{ $cat->posts_count }} berita)">
                                {{ $cat->name }}
                                <span class="text-[10px] text-ink-faint">{{ $cat->posts_count }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </header>

        {{-- Main Page Content --}}
        <main class="flex-1">
            {{ $slot }}
        </main>

        {{-- Restrained, Dignified Footer --}}
        <footer class="mt-20 border-t border-line bg-canvas-alt text-ink-soft">
            <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    {{-- Column 1: School Identity --}}
                    <div class="lg:col-span-2">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-900 text-emerald-400 border border-slate-800 shadow-sm">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                                    <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                                </svg>
                            </div>
                            <span class="text-[17px] font-bold tracking-tight text-ink">
                                Lentera<span class="text-accent">Sekolah</span>
                            </span>
                        </div>
                        <p class="mt-3 max-w-md text-[13px] leading-relaxed text-ink-faint">
                            Portal warta dan jurnalistik resmi sekolah. Menyajikan informasi terverifikasi, prestasi siswa, karya sastra, dan dokumentasi kegiatan belajar mengajar.
                        </p>
                    </div>

                    {{-- Column 2: Navigasi --}}
                    <div>
                        <h3 class="text-[12px] font-bold uppercase tracking-wider text-ink">Navigasi Halaman</h3>
                        <ul class="mt-3 space-y-2 text-[13px] text-ink-soft">
                            <li><a href="{{ route('home') }}" class="focus-ring transition-colors hover:text-accent">Halaman Utama</a></li>
                            <li><a href="{{ route('home') }}#terbaru" class="focus-ring transition-colors hover:text-accent">Berita Terbaru</a></li>
                            <li><a href="{{ route('home') }}#trending" class="focus-ring transition-colors hover:text-accent">Trending Mingguan</a></li>
                            <li><a href="{{ route('home') }}#kategori" class="focus-ring transition-colors hover:text-accent">Kategori Berita</a></li>
                        </ul>
                    </div>

                    {{-- Column 3: Alamat --}}
                    <div>
                        <h3 class="text-[12px] font-bold uppercase tracking-wider text-ink">Alamat Redaksi</h3>
                        <ul class="mt-3 space-y-1.5 text-[13px] text-ink-faint">
                            <li>Ruang Publikasi &amp; Jurnalistik</li>
                            <li>Jl. Pendidikan No. 12, Kota Bandung</li>
                            <li>Email: info@sekolah.sch.id</li>
                            <li>Telepon: (022) 1234-5678</li>
                        </ul>
                    </div>
                </div>

                <div class="mt-10 flex flex-col items-center justify-between gap-3 border-t border-line pt-6 text-[12px] text-ink-faint sm:flex-row">
                    <p>&copy; {{ date('Y') }} Lentera Sekolah. Hak cipta dilindungi undang-undang.</p>
                    <p>Dikurasi dan disupervisi oleh dewan guru pembina.</p>
                </div>
            </div>
        </footer>
    </body>
</html>
