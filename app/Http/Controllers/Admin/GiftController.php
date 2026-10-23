<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GiftClaim;
use App\Models\GiftPopupEvent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GiftController extends Controller
{
    public function index(Request $request): View
    {
        $days = 30;
        $from = now()->subDays($days - 1)->startOfDay();

        $impressions = GiftPopupEvent::where('event', 'impression')
            ->where('created_at', '>=', $from)->count();

        $claims = GiftClaim::where('claimed_at', '>=', $from)->count();

        $issued = GiftClaim::whereNotNull('coupon_id')
            ->where('claimed_at', '>=', $from)->count();

        $redeemed = GiftClaim::where('status', 'redeemed')
            ->where('claimed_at', '>=', $from)->count();

        $conversion = $impressions > 0
            ? round($claims / $impressions * 100, 1)
            : 0.0;

        $daily = collect(range(13, 0))->map(function (int $offset) {
            $date = now()->subDays($offset);

            return [
                'label'       => $date->format('d M'),
                'impressions' => GiftPopupEvent::where('event', 'impression')
                    ->whereDate('created_at', $date->toDateString())->count(),
                'claims'      => GiftClaim::whereDate('claimed_at', $date->toDateString())->count(),
            ];
        });

        $maxDaily = max(1, (int) $daily->max('impressions'), (int) $daily->max('claims'));

        $recent = GiftClaim::with(['user', 'coupon'])
            ->latest('claimed_at')
            ->paginate(20)
            ->withQueryString();

        $topPrizes = GiftClaim::selectRaw('prize, COUNT(*) as total')
            ->groupBy('prize')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $modes = GiftClaim::selectRaw('mode, COUNT(*) as total')
            ->groupBy('mode')
            ->pluck('total', 'mode');

        return view('admin.gifts.index', [
            'stats' => [
                'impressions' => $impressions,
                'claims'      => $claims,
                'issued'      => $issued,
                'redeemed'    => $redeemed,
                'conversion'  => $conversion,
                'all_time'    => GiftClaim::count(),
            ],
            'daily'       => $daily,
            'maxDaily'    => $maxDaily,
            'recent'      => $recent,
            'topPrizes'   => $topPrizes,
            'modes'       => $modes,
            'days'        => $days,
        ]);
    }
}
