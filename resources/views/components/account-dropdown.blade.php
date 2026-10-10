@props(['align' => 'end'])
@php
    $user = auth()->user();
    $initial = mb_substr($user->name, 0, 1);
    $roleLabel = $user->role;
    $photo = $user->profile_photo ? Storage::disk('public')->url($user->profile_photo) : null;
@endphp

<div class="relative" data-dropdown {{ $align === 'start' ? 'data-dropdown--start' : '' }}>
    {{-- Trigger --}}
    <button type="button"
            data-dropdown-trigger
            class="flex items-center gap-2 rounded-full p-0.5 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-800"
            aria-haspopup="menu"
            aria-expanded="false">
        <span class="flex h-8 w-8 items-center justify-center overflow-hidden rounded-full bg-slate-800 text-[12px] font-bold text-white shadow-xs">
            <img data-avatar-current alt="{{ $user->name }}" src="{{ $photo }}" class="h-full w-full object-cover {{ $photo ? '' : 'hidden' }}">
            <span data-avatar-initial class="{{ $photo ? 'hidden' : '' }}">{{ $initial }}</span>
        </span>
        <svg data-dropdown-chevron class="h-3.5 w-3.5 text-ink-faint transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    {{-- Menu panel --}}
    <div data-dropdown-panel
         role="menu"
         class="absolute top-full mt-2 w-56 origin-top-right rounded-lg border border-line bg-canvas p-1 opacity-0 shadow-card transition duration-100 ease-out translate-y-1 scale-95 pointer-events-none {{ $align === 'start' ? 'left-0 origin-top-left' : 'right-0' }}">

        {{-- User info --}}
        <div class="px-3 py-2 border-b border-line">
            <p class="truncate text-[13px] font-bold text-ink">{{ $user->name }}</p>
            <p class="truncate text-[11px] text-ink-faint">{{ $user->email }}</p>
            <span class="inline-block mt-1 rounded bg-canvas-subtle px-1.5 py-0.5 text-[10px] font-semibold text-ink-soft uppercase tracking-wider">
                {{ ucfirst($roleLabel) }}
            </span>
        </div>

        {{-- Edit profil --}}
        <div class="py-1">
            <button type="button"
                    data-profile-trigger
                    role="menuitem"
                    class="flex w-full items-center gap-2 rounded-md px-3 py-1.5 text-[13px] font-medium text-ink-soft transition-colors hover:bg-canvas-alt hover:text-ink">
                <svg class="h-4 w-4 shrink-0 text-ink-faint" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span>Profile Settings</span>
            </button>
        </div>

        <div class="border-t border-line pt-1">
            {{-- Logout --}}
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        role="menuitem"
                        class="flex w-full items-center gap-2 rounded-md px-3 py-1.5 text-[13px] font-medium text-danger transition-colors hover:bg-red-50">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span>Log Out</span>
                </button>
            </form>
        </div>
    </div>
</div>

<x-profile-dialog />
