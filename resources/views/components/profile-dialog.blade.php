@php
    $user = auth()->user();
    $initial = mb_substr($user->name, 0, 1);
    $photo = $user->profile_photo ? Storage::url($user->profile_photo) : null;
    $bioMax = 1000;
@endphp

<div data-dialog class="fixed inset-0 z-[100] hidden" role="dialog" aria-modal="true" aria-labelledby="profile-dialog-title">
    {{-- Overlay --}}
    <div data-dialog-overlay class="absolute inset-0 bg-slate-900/50"></div>

    {{-- Panel --}}
    <div class="absolute left-1/2 top-1/2 z-10 flex w-full max-w-lg -translate-x-1/2 -translate-y-1/2 flex-col overflow-hidden rounded-xl border border-line bg-canvas shadow-float">
        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-line px-6 py-4 bg-canvas-alt">
            <h2 id="profile-dialog-title" class="text-[16px] font-bold text-ink">Profile Settings</h2>
            <button type="button" data-dialog-close aria-label="Tutup" class="flex h-7 w-7 items-center justify-center rounded-md text-ink-faint hover:bg-canvas-subtle hover:text-ink">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="overflow-y-auto p-6">
            <div data-dialog-error class="mb-4 hidden rounded-md border border-danger/30 bg-danger-soft px-3.5 py-2.5 text-[13px] font-medium text-danger"></div>

            <form id="profile-form" data-profile-form method="POST" action="{{ route('dashboard.profile.update') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf
                @method('PUT')
                <input type="hidden" name="remove_photo" value="0" data-avatar-remove-hidden>

                {{-- Avatar + Identitas --}}
                <div class="flex items-center gap-4 p-4 rounded-lg border border-line bg-canvas-alt">
                    <div class="relative shrink-0">
                        <div class="flex h-14 w-14 items-center justify-center overflow-hidden rounded-full bg-slate-800 text-[20px] font-bold text-white">
                            <img data-avatar-preview data-src="{{ $photo }}" src="{{ $photo }}" alt="{{ $user->name }}" class="h-full w-full object-cover {{ $photo ? '' : 'hidden' }}">
                            <span data-avatar-placeholder class="{{ $photo ? 'hidden' : '' }}">{{ $initial }}</span>
                        </div>
                        <label class="absolute -bottom-1 -right-1 flex h-6 w-6 cursor-pointer items-center justify-center rounded-full border border-white bg-accent text-white shadow-xs" aria-label="Ubah foto profil">
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            <input type="file" name="profile_photo" accept="image/*" class="hidden" data-avatar-input>
                        </label>
                        <button type="button" data-avatar-remove aria-label="Hapus foto profil" class="absolute -top-1 right-3 flex h-5 w-5 items-center justify-center rounded-full border border-white bg-danger text-white shadow-xs {{ $photo ? '' : 'hidden' }}">
                            <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="min-w-0">
                        <div class="truncate text-[15px] font-bold text-ink">{{ $user->name }}</div>
                        <div class="truncate text-[12px] text-ink-faint">{{ ucfirst($user->role) }} &middot; {{ $user->email }}</div>
                    </div>
                </div>

                {{-- Nama lengkap --}}
                <div>
                    <label for="profile-name" class="block text-[13px] font-semibold text-ink">Full Name</label>
                    <input type="text" id="profile-name" name="name" value="{{ old('name', $user->name) }}" required
                           class="mt-1.5 w-full rounded-md border border-line bg-canvas px-3 py-2 text-[14px] text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
                </div>

                {{-- Bio --}}
                <div>
                    <label for="profile-bio" class="block text-[13px] font-semibold text-ink">Bio</label>
                    <textarea id="profile-bio" name="bio" rows="3" maxlength="{{ $bioMax }}" placeholder="Brief biography..."
                              data-bio-input
                              class="mt-1.5 w-full rounded-md border border-line bg-canvas px-3 py-2 text-[14px] text-ink placeholder:text-ink-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">{{ old('bio', $user->bio) }}</textarea>
                    <p class="mt-1.5 text-right text-[11px] text-ink-faint">
                        <span data-bio-count>{{ $bioMax - mb_strlen($user->bio) }}</span> characters remaining
                    </p>
                </div>
            </form>
        </div>

        {{-- Footer --}}
        <div class="flex justify-end gap-2 border-t border-line bg-canvas-alt px-6 py-3.5">
            <button type="button" data-dialog-close class="rounded-md border border-line bg-canvas px-3.5 py-1.5 text-[13px] font-medium text-ink-soft hover:bg-canvas-subtle">Cancel</button>
            <button type="submit" form="profile-form" data-profile-submit class="rounded-md bg-accent px-4 py-1.5 text-[13px] font-semibold text-white hover:bg-accent-hover disabled:opacity-50">Save Changes</button>
        </div>
    </div>
</div>
