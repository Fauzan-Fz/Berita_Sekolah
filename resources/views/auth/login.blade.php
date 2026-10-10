<x-layouts.auth title="Masuk   Warta Sekolah"
               heading="Masuk ke Akun Anda"
               subheading="Akses dasbor penulisan dan moderasi berita.">
    <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-[13px] font-semibold text-ink">Alamat Email</label>
            <input type="email" id="email" name="email" required autofocus
                   class="mt-1.5 w-full rounded-md border border-line bg-canvas px-3 py-2 text-[14px] text-ink placeholder:text-ink-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
        </div>

        <div>
            <div class="flex items-center justify-between">
                <label for="password" class="text-[13px] font-semibold text-ink">Kata Sandi</label>
            </div>
            <input type="password" id="password" name="password" required
                   class="mt-1.5 w-full rounded-md border border-line bg-canvas px-3 py-2 text-[14px] text-ink placeholder:text-ink-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
        </div>

        <label class="flex items-center gap-2 text-[13px] text-ink-soft">
            <input type="checkbox" name="remember" class="h-4 w-4 rounded border-line text-accent accent-accent">
            Ingat sesi masuk saya
        </label>

        <button type="submit" class="w-full rounded-md bg-accent py-2 text-[14px] font-semibold text-white transition-colors hover:bg-accent-hover focus:outline-none focus:ring-2 focus:ring-accent/40">
            Masuk
        </button>

        @if($errors->any())
            <div class="rounded-md border border-danger/30 bg-danger-soft px-3.5 py-2.5 text-[13px] text-danger">
                {{ $errors->first() }}
            </div>
        @endif
    </form>

    <p class="mt-5 text-center text-[13px] text-ink-faint">
        Belum memiliki akun siswa?
        <a href="{{ route('register') }}" class="font-semibold text-accent hover:underline">Daftar di sini</a>
    </p>
</x-layouts.auth>
