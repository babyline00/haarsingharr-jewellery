<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscription;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:120', Rule::unique('newsletter_subscriptions', 'email')
                ->where('status', 'subscribed')],
        ]);

        $subscription = NewsletterSubscription::subscribe(
            $validated['email'],
            $request->user(),
            $request->ip(),
        );

        return back()->with('success', 'Thank you for subscribing — please check your inbox to confirm.');
    }

    public function unsubscribe(string $token)
    {
        $subscription = NewsletterSubscription::where('token', $token)->first();
        if ($subscription && $subscription->status === 'subscribed') {
            $subscription->update([
                'status' => 'unsubscribed',
                'unsubscribed_at' => now(),
            ]);
        }
        return view('storefront.newsletter.unsubscribed', ['subscription' => $subscription]);
    }
}
