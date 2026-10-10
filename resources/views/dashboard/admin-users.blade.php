<x-layouts.dashboard title="User Management">
    <div class="space-y-6">
        <nav class="flex items-center gap-2 text-[12px] text-ink-faint">
            <a href="{{ route('dashboard.admin') }}" class="hover:text-accent">Dashboard</a>
            <span>/</span>
            <span class="text-ink">User Management</span>
        </nav>

        <div class="border-b border-line pb-4">
            <h1 class="text-2xl font-bold tracking-tight text-ink">User Management</h1>
            <p class="mt-1 text-[13px] text-ink-faint">
                Control access permissions, assign student/teacher/admin roles, and suspend inactive accounts.
            </p>
        </div>

        @if(session('status'))
            <div class="rounded-md border border-success/30 bg-emerald-50 px-4 py-3 text-[13px] font-medium text-success">
                {{ session('status') }}
            </div>
        @endif
        @if($errors->any())
            <div class="rounded-md border border-danger/30 bg-danger-soft px-4 py-3 text-[13px] font-medium text-danger">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="overflow-hidden rounded-xl border border-line bg-canvas shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="border-b border-line bg-canvas-alt text-[11px] font-bold uppercase tracking-wider text-ink-faint">
                        <tr>
                            <th class="px-5 py-3.5">User Identity</th>
                            <th class="px-5 py-3.5">Email Address</th>
                            <th class="px-5 py-3.5">Role Permission</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach($users as $user)
                            <tr class="transition-colors hover:bg-canvas-alt/40">
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-2.5">
                                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-800 text-[11px] font-bold text-white shrink-0">
                                            {{ mb_substr($user->name, 0, 1) }}
                                        </span>
                                        <div>
                                            <div class="text-[13px] font-bold text-ink">{{ $user->name }}</div>
                                            <div class="text-[11px] text-ink-faint">{{ $user->posts_count }} articles &middot; Joined {{ $user->created_at->translatedFormat('d M Y') }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-[13px] text-ink-soft">{{ $user->email }}</td>
                                <td class="px-5 py-3.5">
                                    <form method="POST" action="{{ route('dashboard.users.role', $user) }}" class="flex items-center gap-1.5">
                                        @csrf
                                        <select name="role" class="rounded border border-line bg-canvas px-2.5 py-1 text-[11px] font-medium text-ink focus:border-accent focus:outline-none">
                                            <option value="siswa" @selected($user->role === 'siswa')>Student</option>
                                            <option value="guru" @selected($user->role === 'guru')>Teacher</option>
                                            <option value="admin" @selected($user->role === 'admin')>Admin</option>
                                        </select>
                                        <button type="submit" class="rounded border border-line bg-canvas px-2.5 py-1 text-[11px] font-semibold text-ink-soft hover:bg-canvas-subtle">
                                            Update
                                        </button>
                                    </form>
                                </td>
                                <td class="px-5 py-3.5 text-[12px]">
                                    @if($user->is_active)
                                        <span class="inline-flex items-center gap-1 font-semibold text-emerald-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 font-semibold text-danger">
                                            <span class="h-1.5 w-1.5 rounded-full bg-danger"></span>
                                            Suspended
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        @if($user->id !== auth()->id())
                                            <form method="POST" action="{{ route('dashboard.users.toggle', $user) }}">
                                                @csrf
                                                <button type="submit" class="rounded border border-line px-2.5 py-1 text-[11px] font-medium text-ink-soft hover:bg-canvas-subtle">
                                                    {{ $user->is_active ? 'Suspend' : 'Activate' }}
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('dashboard.users.destroy', $user) }}" onsubmit="return confirm('Permanently delete this user account and all their articles?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded border border-line px-2.5 py-1 text-[11px] font-medium text-ink-faint hover:bg-red-50 hover:text-danger hover:border-red-200">
                                                    Delete
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-[11px] text-ink-faint italic">Current User</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.dashboard>
