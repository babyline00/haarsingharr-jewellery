<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SecurityEvent;
use App\Services\MfaService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MfaController extends Controller
{
    public function enroll(Request $request, MfaService $mfa): View|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->requiresMfa(), 403);
        if ($user->hasMfaEnabled()) {
            return redirect()->route('admin.mfa.challenge');
        }

        $secret = $request->session()->get('mfa.pending_secret');
        if (! is_string($secret) || strlen($secret) < 16) {
            $secret = $mfa->newSecret();
            $request->session()->put('mfa.pending_secret', $secret);
        }

        return view('admin.mfa.enroll', [
            'secret' => $secret,
            'provisioningUri' => $mfa->provisioningUri($secret, $user->email),
        ]);
    }

    public function confirmEnrollment(Request $request, MfaService $mfa): View|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->requiresMfa() && ! $user->hasMfaEnabled(), 403);

        $validated = $request->validate([
            'code' => ['required', 'string', 'regex:/^\d{6}$/'],
        ]);
        $secret = $request->session()->get('mfa.pending_secret');
        if (! is_string($secret) || ! $mfa->verifyCode($secret, $validated['code'])) {
            throw ValidationException::withMessages([
                'code' => 'That code did not match. Check the authenticator app and try again.',
            ]);
        }

        $recoveryCodes = $mfa->recoveryCodes();
        $user->forceFill([
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode(
                array_map(fn (string $code) => Hash::make($code), $recoveryCodes),
                JSON_THROW_ON_ERROR,
            )),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $request->session()->forget('mfa.pending_secret');
        $request->session()->put('mfa_confirmed', true);
        SecurityEvent::log('mfa.enabled', $user, 'success', [], 'Multi-factor authentication enabled');

        return view('admin.mfa.recovery-codes', compact('recoveryCodes'));
    }

    public function challenge(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->requiresMfa(), 403);
        if (! $user->hasMfaEnabled()) {
            return redirect()->route('admin.mfa.enroll');
        }
        if ($request->session()->boolean('mfa_confirmed')) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return view('admin.mfa.challenge');
    }

    public function verify(Request $request, MfaService $mfa): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->requiresMfa() && $user->hasMfaEnabled(), 403);
        if ($request->session()->boolean('mfa_confirmed')) {
            return redirect()->intended(route('admin.dashboard'));
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'min:6', 'max:20'],
        ]);

        if (! $this->verifyAuthenticatorCode($user->two_factor_secret, $validated['code'], $mfa)
            && ! $this->consumeRecoveryCode($user, $validated['code'])) {
            SecurityEvent::log('mfa.challenge', $user, 'failure', [], 'Invalid MFA challenge code');
            throw ValidationException::withMessages([
                'code' => 'That code is invalid or has already been used.',
            ]);
        }

        $request->session()->put('mfa_confirmed', true);
        SecurityEvent::log('mfa.challenge', $user, 'success', [], 'MFA challenge completed');

        return redirect()->intended(route('admin.dashboard'));
    }

    private function verifyAuthenticatorCode(?string $storedSecret, string $code, MfaService $mfa): bool
    {
        if (! $storedSecret) {
            return false;
        }

        try {
            $secret = Crypt::decryptString($storedSecret);
        } catch (DecryptException) {
            // Accept legacy plaintext secrets so existing manually enrolled accounts remain usable.
            $secret = $storedSecret;
        }

        return $mfa->verifyCode($secret, $code);
    }

    private function consumeRecoveryCode($user, string $submittedCode): bool
    {
        $stored = $user->two_factor_recovery_codes;
        if (! $stored) {
            return false;
        }

        try {
            $stored = Crypt::decryptString($stored);
        } catch (DecryptException) {
            // Treat undecipherable legacy recovery data as invalid instead of trusting it.
            return false;
        }

        $hashes = json_decode($stored, true);
        if (! is_array($hashes)) {
            return false;
        }

        foreach ($hashes as $index => $hash) {
            if (is_string($hash) && Hash::check(Str::upper(trim($submittedCode)), $hash)) {
                unset($hashes[$index]);
                $user->forceFill([
                    'two_factor_recovery_codes' => Crypt::encryptString(json_encode(array_values($hashes), JSON_THROW_ON_ERROR)),
                ])->save();

                return true;
            }
        }

        return false;
    }
}
