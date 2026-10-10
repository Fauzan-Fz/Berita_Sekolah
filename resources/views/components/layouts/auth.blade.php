@props(['title', 'heading', 'subheading'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? config('app.name', 'Web Berita Sekolah') }}</title>
        <meta name="description" content="Portal berita resmi sekolah.">
        <meta name="robots" content="noindex">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-full bg-canvas-alt text-ink antialiased font-sans">
        <div class="flex min-h-screen flex-col md:flex-row">

            {{-- Panel Kiri --}}
            <aside class="relative hidden bg-slate-900 md:flex md:w-[42%] md:shrink-0 md:flex-col md:justify-between md:p-12 text-white">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-md bg-accent text-[13px] font-bold text-white">
                        W
                    </span>
                    <span class="text-[17px] font-bold tracking-tight">Lentera<span class="text-emerald-400">Sekolah</span></span>
                </a>

                <div class="my-auto py-8">
                    <h2 class="text-2xl font-bold leading-tight text-white sm:text-3xl">
                        Kanal publikasi resmi warga sekolah.
                    </h2>
                    <p class="mt-3 text-[14px] leading-relaxed text-slate-400">
                        Masuk untuk membuat draf artikel bagi siswa, melakukan moderasi konten bagi dewan guru, atau mengelola sistem bagi administrator.
                    </p>

                    <div class="mt-8 border-t border-slate-800 pt-6">
                        <p class="text-[12px] font-medium text-slate-400">
                            Setiap draf tulisan yang diajukan akan melalui proses verifikasi guru pembina sebelum terbit ke publik.
                        </p>
                    </div>
                </div>

                <div class="text-[12px] text-slate-500">&copy; {{ date('Y') }} Lentera Sekolah.</div>
            </aside>

            {{-- Kolom Kanan: Formulir --}}
            <main class="flex flex-1 items-center justify-center px-4 py-12 sm:px-8 md:py-16 bg-canvas">
                <div class="w-full max-w-[380px]">
                    <header class="text-left">
                        <h1 class="text-2xl font-bold tracking-tight text-ink">{{ $heading }}</h1>
                        <p class="mt-1.5 text-[14px] text-ink-faint">{{ $subheading }}</p>
                    </header>

                    <div class="mt-6 rounded-lg border border-line bg-canvas p-6 shadow-xs">
                        {{ $slot }}
                    </div>

                    <div class="mt-6 text-center text-[12px] text-ink-faint">
                        <a href="{{ route('home') }}" class="transition-colors hover:text-ink">&larr; Kembali ke beranda utama</a>
                    </div>
                </div>
            </main>
        </div>
    </body>
</html>
