<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetToken;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Notifications\PasswordResetLinkNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function showLinkRequestForm(): View
    {
        return view('auth.passwords.email');
    }

    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();
        if (! $user) {
            // For privacy, do not disclose that the email does not exist.
            return back()->with('status', 'If your email exists, a reset link has been sent.');
        }

        SecurityEvent::log('password_reset.request', $user, 'success',
            [], 'Password reset requested from '.$request->ip());

        $response = Password::sendResetLink(['email' => $request->email]);

        if ($response === Password::RESET_LINK_SENT) {
            return back()->with('status', 'A password reset link has been sent to your email.');
        }
        throw ValidationException::withMessages([
            'email' => trans($response),
        ]);
    }
}
