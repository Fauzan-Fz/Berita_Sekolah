<x-layouts.app title="404   Halaman Tidak Ditemukan">
    <div class="mx-auto max-w-2xl px-4 py-20 text-center sm:px-6">
        <span class="inline-block rounded bg-amber-50 border border-amber-200 px-3 py-1 text-[12px] font-bold text-amber-800">
            Status 404
        </span>

        <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-ink sm:text-4xl">
            Berita Belum Diterbitkan atau Tidak Ditemukan
        </h1>

        <p class="mt-3 text-[15px] leading-relaxed text-ink-soft">
            Artikel yang Anda tuju mungkin masih berstatus draf, sedang dalam proses moderasi oleh dewan guru, atau telah dipindahkan.
        </p>

        @guest
            <div class="mt-4 p-4 rounded-xl border border-line bg-canvas-alt text-[13px] text-ink-faint">
                Jika Anda adalah penulis artikel ini, silakan <a href="{{ route('login') }}" class="font-bold text-accent hover:underline">Masuk ke Akun Anda</a> untuk melihat draf pratinjau.
            </div>
        @endguest

        <div class="mt-8 flex justify-center gap-3">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-md bg-accent px-5 py-2.5 text-[13px] font-semibold text-white hover:bg-accent-hover transition-colors shadow-xs">
                <span>&larr; Kembali ke Halaman Utama</span>
            </a>
        </div>
    </div>
</x-layouts.app>
