<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Notifications\CustomerWelcomeNotification;
use App\Services\AuditService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RegisterController extends Controller
{
    use RegistersUsers;

    public function __construct(private readonly AuditService $audit)
    {
        $this->middleware('guest');
    }

    public function showRegistrationForm(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name'        => 'required|string|max:60',
            'last_name'         => 'required|string|max:60',
            'email'             => ['required', 'email', 'max:120', Rule::unique('users')],
            'phone_country'     => 'required|string|max:5',
            'phone'             => 'required|string|max:20',
            'password'          => 'required|string|min:12|confirmed|strong_password',
            'marketing_consent' => 'sometimes|boolean',
            'terms'             => 'accepted',
        ], [
            'terms.accepted'               => 'Please accept the Terms of Service to continue.',
            'password.strong_password'    => 'Password must include uppercase, lowercase, digit and symbol.',
            'password.min'                => 'Password must be at least 12 characters long.',
        ]);

        $user = DB::transaction(function () use ($validated, $request) {
            $user = User::create([
                'first_name'        => $validated['first_name'],
                'last_name'         => $validated['last_name'],
                'email'             => $validated['email'],
                'phone_country'     => $validated['phone_country'],
                'phone'             => $validated['phone'],
                'password'          => Hash::make($validated['password']),
                'type'               => 'customer',
                'status'             => 'active',
                'marketing_consent' => (bool) ($validated['marketing_consent'] ?? false),
                'marketing_consent_at' => isset($validated['marketing_consent']) ? now() : null,
                'password_changed_at' => now(),
                'consent_log' => [
                    ['event' => 'register', 'terms_accepted' => true, 'ts' => now()->toIso8601String(), 'ip' => $request->ip()],
                ],
            ]);

            $customerRole = Role::where('slug', 'customer')->first();
            if ($customerRole) {
                $user->roles()->attach($customerRole->id);
            }

            event(new Registered($user));
            return $user;
        });

        SecurityEvent::log('register.success', $user, 'success',
            [], 'New customer registered from '.$request->ip());

        // Send verification email (queued if database queue configured).
        $user->sendEmailVerificationNotification();

        // Optional welcome notification
        $user->notify(new CustomerWelcomeNotification());

        Auth()->login($user);
        $request->session()->regenerate(true);

        return redirect()->route('account.dashboard')
            ->with('success', 'Welcome to Haarsingharr. Please verify your email to unlock all features.');
    }
}
