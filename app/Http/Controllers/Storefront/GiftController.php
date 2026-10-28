<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\GiftPopupEvent;
use App\Services\GiftCouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GiftController extends Controller
{
    public function event(Request $request): JsonResponse
    {
        $data = $request->validate([
            'event' => 'required|in:impression',
            'mode'  => 'nullable|in:scratch,spin,both',
        ]);

        GiftPopupEvent::create([
            'event'      => $data['event'],
            'session_id' => $request->session()->getId(),
            'user_id'    => $request->user()?->id,
            'mode'       => $data['mode'] ?? null,
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['ok' => true]);
    }

    public function claim(Request $request, GiftCouponService $gift): JsonResponse
    {
        $settings = $gift->settings();

        abort_unless($settings['enabled'], 404);

        $data = $request->validate([
            'mode' => 'nullable|in:scratch,spin,both',
        ]);

        $user = $request->user();

        $result = $gift->claim([
            'user_id'       => $user?->id,
            'session_id'    => $request->session()->getId(),
            'customer_name' => $user ? trim($user->first_name.' '.$user->last_name) : null,
            'email'         => $user?->email,
            'mode'          => $data['mode'] ?? $settings['mode'],
            'ip_address'    => $request->ip(),
            'user_agent'    => Str::limit((string) $request->userAgent(), 250, ''),
        ]);

        $claim = $result['claim'];

        return response()->json([
            'ok'              => true,
            'prize'           => $claim->prize,
            'code'            => $result['code'],
            'discount'        => $claim->discountLabel(),
            'expires_at'      => $claim->coupon?->expires_at?->toIso8601String(),
            'already_claimed' => $result['already_claimed'],
        ]);
    }
}
