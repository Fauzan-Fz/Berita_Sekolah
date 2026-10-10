<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * OWASP A04 / A07 Protected Login with Rate Limiting (Anti-Brute-Force).
     */
    public function login(Request $request)
    {
        $throttleKey = Str::transliterate(Str::lower($request->input('email', '')).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            Log::warning("Audit [Brute-Force Blocked]: Login throttle triggered for email {$request->input('email')} from IP {$request->ip()}");

            return back()
                ->withErrors(['email' => "Terlalu banyak percobaan masuk. Silakan coba lagi dalam {$seconds} detik."])
                ->onlyInput('email');
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if ($user && ! $user->is_active) {
            Log::warning("Audit [Inactive Account Login]: Blocked login attempt for suspended user {$user->id} ({$user->email}) from IP {$request->ip()}");

            return back()
                ->withErrors(['email' => 'Akun Anda dinonaktifkan. Hubungi administrator.'])
                ->onlyInput('email');
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();
            Log::info("Audit [Successful Login]: User {$user->id} ({$user->email}) logged in successfully from IP {$request->ip()}");

            return redirect($this->home($request->user()));
        }

        RateLimiter::hit($throttleKey, 60);
        Log::warning("Audit [Auth Failure]: Invalid login attempt for email {$request->input('email')} from IP {$request->ip()}");

        return back()
            ->withErrors(['email' => 'Email atau kata sandi salah.'])
            ->onlyInput('email');
    }

    /**
     * OWASP A04 Rate-Limited Registration against Automated Bot Flooding.
     */
    public function register(Request $request)
    {
        $throttleKey = 'register|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            Log::warning("Audit [Registration Throttle]: Registration rate limit reached for IP {$request->ip()}");

            return back()
                ->withErrors(['email' => "Terlalu banyak percobaan pendaftaran. Silakan coba lagi dalam {$seconds} detik."])
                ->onlyInput('email', 'name');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', Rule::in(['siswa', 'guru'])],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        RateLimiter::hit($throttleKey, 60);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
        ]);

        Log::info("Audit [User Registered]: New {$data['role']} account created with ID {$user->id} ({$user->email}) from IP {$request->ip()}");

        Auth::login($user);
        $request->session()->regenerate();

        return redirect($this->home($user));
    }

    public function logout(Request $request)
    {
        $userId = Auth::id();
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info("Audit [Logout]: User {$userId} logged out from IP {$request->ip()}");

        return redirect()->route('home')->with('status', 'Anda telah keluar.');
    }

    private function home($user): string
    {
        return match ($user->role) {
            'admin' => route('dashboard.admin'),
            'guru' => route('dashboard.guru'),
            default => route('dashboard.siswa'),
        };
    }
}
