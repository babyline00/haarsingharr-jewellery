<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ResetPasswordController extends Controller
{
    use ResetsPasswords;

    public function __construct(private readonly AuditService $audit)
    {
        $this->middleware('guest');
    }

    public function showResetForm(Request $request, ?string $token = null): View
    {
        return view('auth.passwords.reset', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|string|min:12|confirmed|strong_password',
        ], [
            'password.strong_password' => 'Password must include uppercase, lowercase, digit and symbol.',
            'password.min'            => 'Password must be at least 12 characters long.',
        ]);

        $response = $this->broker()->reset(
            $validated,
            function (User $user, $password) use ($request) {
                $user->forceFill([
                    'password'            => Hash::make($password),
                    'password_changed_at'  => now(),
                    'remember_token'       => null,
                ])->save();
                SecurityEvent::log('password_reset.complete', $user, 'success',
                    [], 'Password reset completed from '.$request->ip());
                $this->audit->record('password.reset', $user);
            }
        );

        if ($response === 'passwords.reset') {
            return redirect()->route('login')->with('status', 'Your password has been reset. You may now log in.');
        }
        return back()->withErrors(['email' => trans($response)]);
    }
}
