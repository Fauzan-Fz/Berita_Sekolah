<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? 'Dashboard' }}   {{ config('app.name') }}</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-full bg-canvas-alt text-ink font-sans">
        @php
            $user = auth()->user();
            $menuItems = [];
            
            if ($user->isAdmin()) {
                $menuItems = [
                    [
                        'label' => 'Dashboard',
                        'route' => route('dashboard.admin'),
                        'active' => request()->routeIs('dashboard.admin'),
                        'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2M9 21h6'
                    ],
                    [
                        'label' => 'All Posts',
                        'route' => route('dashboard.admin.posts'),
                        'active' => request()->routeIs('dashboard.admin.posts'),
                        'icon' => 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z'
                    ],
                    [
                        'label' => 'Categories',
                        'route' => route('dashboard.admin.categories'),
                        'active' => request()->routeIs('dashboard.admin.categories'),
                        'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'
                    ],
                    [
                        'label' => 'User Management',
                        'route' => route('dashboard.admin.users'),
                        'active' => request()->routeIs('dashboard.admin.users'),
                        'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'
                    ],
                    [
                        'label' => 'Create Post',
                        'route' => route('dashboard.siswa.create'),
                        'active' => request()->routeIs('dashboard.siswa.create'),
                        'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'
                    ]
                ];
            } elseif ($user->isGuru()) {
                $pendingCount = \App\Models\Post::where('status', 'pending')->count();
                $menuItems = [
                    [
                        'label' => 'Dashboard',
                        'route' => route('dashboard.guru'),
                        'active' => request()->routeIs('dashboard.guru'),
                        'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2M9 21h6'
                    ],
                    [
                        'label' => 'Moderation Queue',
                        'route' => route('dashboard.guru.queue'),
                        'active' => request()->routeIs('dashboard.guru.queue'),
                        'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                        'badge' => $pendingCount > 0 ? $pendingCount : null,
                        'badgeColor' => 'bg-amber-100 text-amber-800'
                    ],
                    [
                        'label' => 'Published History',
                        'route' => route('dashboard.guru.history'),
                        'active' => request()->routeIs('dashboard.guru.history'),
                        'icon' => 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z'
                    ]
                ];
            } else {
                // Peran Siswa
                $userPosts = \App\Models\Post::where('user_id', $user->id)->get();
                $myPostCount = $userPosts->count();
                $needsRevisionCount = $userPosts->where('status', 'rejected')->count();

                $menuItems = [
                    [
                        'label' => 'Dashboard',
                        'route' => route('dashboard.siswa'),
                        'active' => request()->routeIs('dashboard.siswa'),
                        'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2M9 21h6'
                    ],
                    [
                        'label' => 'Create Post',
                        'route' => route('dashboard.siswa.create'),
                        'active' => request()->routeIs('dashboard.siswa.create'),
                        'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'
                    ],
                    [
                        'label' => 'My Articles',
                        'route' => route('dashboard.siswa.posts'),
                        'active' => request()->routeIs('dashboard.siswa.posts') || request()->routeIs('dashboard.siswa.edit'),
                        'icon' => 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z',
                        'badge' => $needsRevisionCount > 0 ? $needsRevisionCount : ($myPostCount > 0 ? $myPostCount : null),
                        'badgeColor' => $needsRevisionCount > 0 ? 'bg-red-100 text-red-800' : 'bg-slate-100 text-slate-700'
                    ]
                ];
            }
        @endphp

        <div class="flex h-screen overflow-hidden">
            {{-- Desktop Sidebar --}}
            <aside class="hidden w-64 shrink-0 border-r border-line bg-canvas lg:flex lg:flex-col">
                {{-- Brand Header --}}
                <div class="flex h-16 items-center gap-2.5 border-b border-line px-5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-900 text-emerald-400 border border-slate-800 shadow-sm">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                        </svg>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-[15px] font-bold tracking-tight text-ink truncate leading-tight">Lentera<span class="text-accent">Sekolah</span></span>
                        <span class="text-[10px] font-semibold uppercase tracking-wider text-ink-faint">{{ ucfirst($user->role) }} Portal</span>
                    </div>
                </div>

                {{-- Navigation Items --}}
                <nav class="flex-1 space-y-1 overflow-y-auto p-3">
                    <div class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-ink-muted">
                        Navigation
                    </div>

                    @foreach($menuItems as $item)
                        <a href="{{ $item['route'] }}"
                           class="flex items-center justify-between rounded-md px-3 py-2 text-[13px] font-medium transition-colors {{ $item['active'] ? 'bg-canvas-subtle font-semibold text-accent' : 'text-ink-soft hover:bg-canvas-subtle hover:text-ink' }}">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <svg class="h-4 w-4 shrink-0 {{ $item['active'] ? 'text-accent' : 'text-ink-faint' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                                </svg>
                                <span class="truncate">{{ $item['label'] }}</span>
                            </div>
                            @if(!empty($item['badge']))
                                <span class="rounded px-1.5 py-0.2 text-[10px] font-bold {{ $item['badgeColor'] ?? 'bg-slate-100 text-slate-700' }}">
                                    {{ $item['badge'] }}
                                </span>
                            @endif
                        </a>
                    @endforeach

                    <div class="pt-4 px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-ink-muted">
                        Public Portal
                    </div>

                    <a href="{{ route('home') }}" class="flex items-center gap-2.5 rounded-md px-3 py-2 text-[13px] font-medium text-ink-soft hover:bg-canvas-subtle hover:text-ink transition-colors">
                        <svg class="h-4 w-4 shrink-0 text-ink-faint" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        <span>Visit Site</span>
                    </a>
                </nav>

                {{-- User / Logout Bottom Bar --}}
                <div class="border-t border-line p-3 space-y-1">
                    <button type="button" data-profile-trigger class="flex w-full items-center gap-2.5 rounded-md px-3 py-2 text-[13px] font-medium text-ink-soft hover:bg-canvas-subtle hover:text-ink transition-colors">
                        <svg class="h-4 w-4 shrink-0 text-ink-faint" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span>Profile Settings</span>
                    </button>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2.5 rounded-md px-3 py-2 text-[13px] font-medium text-ink-soft transition-colors hover:bg-red-50 hover:text-danger">
                            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            <span>Log Out</span>
                        </button>
                    </form>
                </div>
            </aside>

            {{-- Main Content Area --}}
            <div class="flex flex-1 flex-col overflow-hidden">
                {{-- Top Bar Header --}}
                <header class="flex h-16 items-center justify-between border-b border-line bg-canvas px-4 sm:px-8">
                    <div class="flex items-center gap-3">
                        <button type="button" class="focus-ring flex h-8 w-8 items-center justify-center rounded-md border border-line text-ink-soft hover:bg-canvas-subtle lg:hidden" aria-label="Open sidebar menu" onclick="document.getElementById('sidebar-mobile').classList.toggle('-translate-x-full')">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>
                        <h1 class="text-[16px] font-bold tracking-tight text-ink">{{ $title ?? 'Dashboard' }}</h1>
                    </div>

                    <div class="flex items-center gap-4">
                        <a href="{{ route('home') }}" class="focus-ring hidden sm:inline-flex items-center gap-1.5 text-[12px] font-semibold text-ink-soft transition-colors hover:text-accent">
                            <span>Visit Public Site</span>
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
                        <x-account-dropdown />
                    </div>
                </header>

                {{-- Mobile Sidebar Drawer --}}
                <div id="sidebar-mobile" class="fixed inset-0 z-50 -translate-x-full transition-transform lg:hidden">
                    <div class="absolute inset-0 bg-slate-900/40" onclick="document.getElementById('sidebar-mobile').classList.add('-translate-x-full')"></div>
                    <aside class="relative flex h-full w-64 flex-col bg-canvas shadow-card">
                        <div class="flex h-16 items-center justify-between border-b border-line px-5">
                            <div class="flex items-center gap-2">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-900 text-emerald-400 border border-slate-800 shadow-sm">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                                        <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                                    </svg>
                                </div>
                                <span class="text-[15px] font-bold tracking-tight">LenteraSekolah</span>
                            </div>
                            <button type="button" onclick="document.getElementById('sidebar-mobile').classList.add('-translate-x-full')" class="text-ink-faint hover:text-ink text-xl font-bold">&times;</button>
                        </div>
                        <div class="p-3 space-y-1 overflow-y-auto flex-1">
                            @foreach($menuItems as $item)
                                <a href="{{ $item['route'] }}"
                                   onclick="document.getElementById('sidebar-mobile').classList.add('-translate-x-full')"
                                   class="flex items-center justify-between rounded-md px-3 py-2 text-[13px] font-medium {{ $item['active'] ? 'bg-canvas-subtle font-semibold text-accent' : 'text-ink-soft hover:bg-canvas-subtle hover:text-ink' }}">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <svg class="h-4 w-4 shrink-0 {{ $item['active'] ? 'text-accent' : 'text-ink-faint' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                                        </svg>
                                        <span class="truncate">{{ $item['label'] }}</span>
                                    </div>
                                    @if(!empty($item['badge']))
                                        <span class="rounded px-1.5 py-0.2 text-[10px] font-bold {{ $item['badgeColor'] ?? 'bg-slate-100 text-slate-700' }}">
                                            {{ $item['badge'] }}
                                        </span>
                                    @endif
                                </a>
                            @endforeach
                            <a href="{{ route('home') }}" class="flex items-center gap-2.5 rounded-md px-3 py-2 text-[13px] font-medium text-ink-soft hover:bg-canvas-subtle hover:text-ink">
                                <svg class="h-4 w-4 shrink-0 text-ink-faint" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                                <span>Visit Site</span>
                            </a>
                        </div>
                    </aside>
                </div>

                {{-- Page Content --}}
                <main class="flex-1 overflow-y-auto p-4 sm:p-8">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
