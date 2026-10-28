<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscription;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NewsletterSubscriptionController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['status', 'q']);

        $subscriptions = NewsletterSubscription::with('user')
            ->when(($filters['status'] ?? '') !== '', fn ($q) => $q->where('status', $filters['status']),
                fn ($q) => $q->whereIn('status', ['pending', 'subscribed']))
            ->when($filters['q'] ?? '', fn ($q, $term) => $q->where('email', 'like', "%{$term}%"))
            ->latest('subscribed_at')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.newsletter.index', [
            'subscriptions' => $subscriptions,
            'filters'       => $filters,
            'stats'         => [
                'total'      => NewsletterSubscription::count(),
                'subscribed' => NewsletterSubscription::where('status', 'subscribed')->count(),
                'pending'    => NewsletterSubscription::where('status', 'pending')->count(),
                'unsubscribed' => NewsletterSubscription::where('status', 'unsubscribed')->count(),
                'bounced'    => NewsletterSubscription::where('status', 'bounced')->count(),
            ],
            'statuses'      => ['pending', 'subscribed', 'unsubscribed', 'bounced'],
        ]);
    }

    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids'    => ['required', 'array', 'min:1', 'max:500'],
            'ids.*'  => ['integer'],
            'action' => ['required', 'in:unsubscribe,subscribe,bounce,delete,export'],
        ]);

        if ($validated['action'] === 'export') {
            return redirect()->route('admin.newsletter.export', ['ids' => implode(',', $validated['ids'])]);
        }

        $subscriptions = NewsletterSubscription::whereIn('id', $validated['ids'])->get();
        $changed = 0;

        foreach ($subscriptions as $subscription) {
            if ($validated['action'] === 'delete') {
                $this->audit->record('newsletter.bulk.delete', $subscription);
                $subscription->delete();
            } else {
                $status = ['subscribe' => 'subscribed', 'unsubscribe' => 'unsubscribed', 'bounce' => 'bounced'][$validated['action']];

                $subscription->update(array_merge(
                    $validated['action'] === 'subscribe'
                        ? ['unsubscribed_at' => null, 'subscribed_at' => now()]
                        : [],
                    $validated['action'] === 'unsubscribe'
                        ? ['unsubscribed_at' => now()]
                        : [],
                    ['status' => $status],
                ));
            }

            $changed++;
        }

        $this->audit->record('newsletter.bulk.update', null, null, [
            'action' => $validated['action'],
            'ids'    => $validated['ids'],
        ]);

        return back()->with('success', $changed.' subscriber(s) updated.');
    }

    public function export(Request $request): StreamedResponse
    {
        $ids = collect(explode(',', (string) $request->input('ids', '')))->filter()->map(fn ($id) => (int) $id);

        $query = NewsletterSubscription::query()
            ->when($ids->isNotEmpty(), fn ($q) => $q->whereIn('id', $ids));

        $this->audit->record('newsletter.export', null, null, [
            'ids'  => $ids->all(),
            'rows' => $query->count(),
        ]);

        $filename = 'newsletter-subscribers-'.now()->format('ymd-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Email', 'Status', 'Account email', 'Subscribed at', 'Unsubscribed at', 'Signup IP']);

            $query->with('user')->chunk(500, function ($subscriptions) use ($out) {
                foreach ($subscriptions as $subscription) {
                    fputcsv($out, [
                        $subscription->email,
                        $subscription->status,
                        $subscription->user?->email,
                        $subscription->subscribed_at?->toISOString(),
                        $subscription->unsubscribed_at?->toISOString(),
                        $subscription->ip_address,
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
