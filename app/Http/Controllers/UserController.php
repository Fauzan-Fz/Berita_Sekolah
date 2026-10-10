<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * OWASP A01 / ISO 27001 A.8.26 Hardened Role Updating with Traceable Audit Logging.
     */
    public function updateRole(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(['siswa', 'guru', 'admin'])],
        ]);

        if ($user->id === $request->user()->id && $data['role'] !== 'admin') {
            return back()->withErrors(['role' => 'Anda tidak dapat menurunkan role akun sendiri.']);
        }

        $oldRole = $user->role;
        $user->update(['role' => $data['role']]);

        Log::info("Audit [Role Changed]: User {$user->id} ({$user->email}) role changed from {$oldRole} to {$data['role']} by Admin {$request->user()->id}");

        return back()->with('status', 'Peran pengguna diperbarui.');
    }

    /**
     * OWASP A01 Account Activation Toggle with Audit Trace.
     */
    public function toggleActive(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'Anda tidak dapat mengubah status akun sendiri.']);
        }

        $user->update(['is_active' => ! $user->is_active]);

        Log::warning("Audit [Account Status Toggle]: User {$user->id} ({$user->email}) is_active set to ".($user->is_active ? '1' : '0')." by Admin {$request->user()->id}");

        return back()->with('status', $user->is_active ? 'Akun diaktifkan.' : 'Akun dinonaktifkan.');
    }

    /**
     * Account Deletion with Traceable Security Logs.
     */
    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'Anda tidak dapat menghapus akun sendiri.']);
        }

        $email = $user->email;
        $id = $user->id;
        $user->delete();

        Log::warning("Audit [User Deletion]: User {$id} ({$email}) deleted by Admin {$request->user()->id}");

        return back()->with('status', 'Pengguna dihapus.');
    }
}
