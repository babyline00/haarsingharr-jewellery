<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\SecurityEvent;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('account.profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:60',
            'last_name' => 'required|string|max:60',
            'phone_country' => 'required|string|max:5',
            'phone' => 'required|string|max:20',
        ]);
        $request->user()->update($validated);
        return back()->with('success', 'Profile updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:12', 'confirmed', 'strong_password'],
        ], [
            'current_password.current_password' => 'Your current password is incorrect.',
            'password.strong_password' => 'Password must include uppercase, lowercase, digit and symbol.',
            'password.min' => 'Password must be at least 12 characters long.',
        ]);

        $request->user()->update([
            'password' => $validated['password'],
            'password_changed_at' => now(),
        ]);

        SecurityEvent::log(
            'password.changed',
            $request->user(),
            'success',
            [],
            'Password changed from the account area.',
        );

        return back()->with('success', 'Password updated.');
    }
}
