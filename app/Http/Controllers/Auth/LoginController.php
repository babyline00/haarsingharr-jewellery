<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActiveSession;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    use AuthenticatesUsers {
        login as parentLogin;
    }

    public function __construct(private readonly AuditService $audit)
    {
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
            'remember' => 'sometimes|boolean',
        ]);

        if (method_exists($this, 'hasTooManyLoginAttempts') && $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->getAuthPassword())) {
            $this->incrementLoginAttempts($request);
            SecurityEvent::log('login.attempt', $user, 'failure',
                ['email' => $validated['email'], 'reason' => 'invalid_credentials'],
                'Failed login attempt for '.$validated['email']);
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        if (! $user->isActive()) {
            SecurityEvent::log('login.suspended', $user, 'blocked',
                ['email' => $validated['email']], 'Suspended account login attempt');
            throw ValidationException::withMessages([
                'email' => 'This account is suspended. Contact support.',
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate(true);
        $this->clearLoginAttempts($request);
        $user->touchLastLogin($request->ip());
        SecurityEvent::log('login.success', $user, 'success',
            [], 'Successful login from '.$request->ip());

        ActiveSession::create([
            'user_id'           => $user->id,
            'session_id'        => $request->session()->getId(),
            'ip_address'        => $request->ip(),
            'user_agent'        => $request->userAgent(),
            'device'            => $this->deviceName($request->userAgent()),
            'location'          => '',
            'last_activity_at'  => now(),
        ]);

        return $this->authenticatedRedirect($user);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            ActiveSession::where('user_id', $user->id)
                ->where('session_id', $request->session()->getId())
                ->delete();
            SecurityEvent::log('logout', $user, 'success', [], 'Logout');
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }

    private function authenticatedRedirect(User $user): RedirectResponse
    {
        if ($user->isStaff()) {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->intended(route('account.dashboard'));
    }

    private function deviceName(?string $ua): string
    {
        if (! $ua) return 'Unknown';
        if (Str::contains($ua, 'iPhone'))    return 'iPhone';
        if (Str::contains($ua, 'iPad'))     return 'iPad';
        if (Str::contains($ua, 'Android'))  return 'Android';
        if (Str::contains($ua, 'Windows'))  return 'Windows PC';
        if (Str::contains($ua, 'Mac OS X')) return 'Mac';
        if (Str::contains($ua, 'Linux'))    return 'Linux PC';
        return 'Unknown';
    }
}
