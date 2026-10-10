<x-layouts.auth title="Daftar   Warta Sekolah"
               heading="Pendaftaran Akun Baru"
               subheading="Daftar sebagai kontributor berita siswa.">
    <form method="POST" action="{{ route('register.attempt') }}" class="space-y-4">
        @csrf

        <div>
            <label for="name" class="block text-[13px] font-semibold text-ink">Nama Lengkap</label>
            <input type="text" id="name" name="name" required autofocus
                   class="mt-1.5 w-full rounded-md border border-line bg-canvas px-3 py-2 text-[14px] text-ink placeholder:text-ink-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
        </div>

        <div>
            <label for="email" class="block text-[13px] font-semibold text-ink">Alamat Email</label>
            <input type="email" id="email" name="email" required
                   class="mt-1.5 w-full rounded-md border border-line bg-canvas px-3 py-2 text-[14px] text-ink placeholder:text-ink-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
        </div>

        <input type="hidden" name="role" value="siswa">

        <div>
            <label for="password" class="block text-[13px] font-semibold text-ink">Kata Sandi</label>
            <input type="password" id="password" name="password" required
                   class="mt-1.5 w-full rounded-md border border-line bg-canvas px-3 py-2 text-[14px] text-ink placeholder:text-ink-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
        </div>

        <div>
            <label for="password_confirmation" class="block text-[13px] font-semibold text-ink">Konfirmasi Kata Sandi</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required
                   class="mt-1.5 w-full rounded-md border border-line bg-canvas px-3 py-2 text-[14px] text-ink placeholder:text-ink-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
        </div>

        <button type="submit" class="w-full rounded-md bg-accent py-2 text-[14px] font-semibold text-white transition-colors hover:bg-accent-hover focus:outline-none focus:ring-2 focus:ring-accent/40">
            Daftar Sekarang
        </button>

        @if($errors->any())
            <div class="rounded-md border border-danger/30 bg-danger-soft px-3.5 py-2.5 text-[13px] text-danger">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </form>

    <p class="mt-5 text-center text-[13px] text-ink-faint">
        Sudah memiliki akun?
        <a href="{{ route('login') }}" class="font-semibold text-accent hover:underline">Masuk</a>
    </p>
</x-layouts.auth>
