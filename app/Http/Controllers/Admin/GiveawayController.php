<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Giveaway;
use App\Models\GiveawayEntry;
use App\Models\User;
use App\Notifications\GiftWonNotification;
use App\Services\AuditService;
use App\Services\GiftCouponService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GiveawayController extends Controller
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly GiftCouponService $gifts,
    ) {}

    public function index(): View
    {
        $giveaways = Giveaway::withCount(['entries', 'winners as winners_total'])
            ->latest()
            ->paginate(20);

        return view('admin.giveaways.index', compact('giveaways'));
    }

    public function create(): View
    {
        return view('admin.giveaways.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        $validated['created_by'] = $request->user()->id;

        $giveaway = Giveaway::create($validated);
        $this->audit->record('giveaways.create', $giveaway);

        return redirect()->route('admin.giveaways.show', $giveaway)
            ->with('success', 'Giveaway created. Add customers, then run the draw.');
    }

    public function show(Giveaway $giveaway, Request $request): View
    {
        $search = trim((string) $request->input('q', ''));
        $enteredIds = $giveaway->entries()->pluck('user_id');

        $customers = User::query()
            ->where('type', 'customer')
            ->whereNotIn('id', $enteredIds)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('email', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderBy('first_name')
            ->limit(20)
            ->get();

        $entries = $giveaway->entries()
            ->with(['user', 'claim.coupon'])
            ->orderByDesc('is_winner')
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.giveaways.show', [
            'giveaway' => $giveaway,
            'entries'  => $entries,
            'customers'=> $customers,
            'search'   => $search,
        ]);
    }

    public function edit(Giveaway $giveaway)
    {
        if ($giveaway->isDrawn()) {
            return redirect()->route('admin.giveaways.show', $giveaway)
                ->with('error', 'A drawn giveaway cannot be edited. Create a new giveaway instead.');
        }

        return view('admin.giveaways.edit', compact('giveaway'));
    }

    public function update(Request $request, Giveaway $giveaway)
    {
        if ($giveaway->isDrawn()) {
            return back()->with('error', 'A drawn giveaway cannot be edited. Create a new giveaway instead.');
        }

        $giveaway->update($this->validated($request));
        $this->audit->record('giveaways.update', $giveaway);

        return redirect()->route('admin.giveaways.show', $giveaway)
            ->with('success', 'Giveaway updated.');
    }

    public function destroy(Giveaway $giveaway)
    {
        $this->audit->record('giveaways.delete', $giveaway);
        $giveaway->delete();

        return redirect()->route('admin.giveaways.index')->with('success', 'Giveaway removed.');
    }

    public function addEntry(Request $request, Giveaway $giveaway)
    {
        if ($giveaway->isDrawn()) {
            return back()->with('error', 'This giveaway has already been drawn.');
        }

        $data = $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'email'   => 'nullable|email|max:190',
        ]);

        if (empty($data['user_id']) && empty($data['email'])) {
            return back()->with('error', 'Choose a customer or enter an email address.');
        }

        $customerQuery = User::query()->where('type', 'customer');

        $user = ! empty($data['user_id'])
            ? (clone $customerQuery)->find($data['user_id'])
            : (clone $customerQuery)->where('email', $data['email'])->first();

        if (! $user) {
            return back()->with('error', 'No customer account found for that customer.');
        }

        $entry = $giveaway->entries()->firstOrCreate(
            ['user_id' => $user->id],
            ['added_by' => $request->user()->id],
        );

        if ($entry->wasRecentlyCreated) {
            $this->audit->record('giveaways.entries.create', $giveaway, null, ['user_id' => $user->id]);
        }

        return back()->with('success', $entry->wasRecentlyCreated
            ? "{$user->name} was added to the draw."
            : "{$user->name} is already in this draw.");
    }

    public function removeEntry(Giveaway $giveaway, GiveawayEntry $entry)
    {
        abort_unless($entry->giveaway_id === $giveaway->id, 404);

        if ($giveaway->isDrawn()) {
            return back()->with('error', 'Winners cannot be changed after the draw.');
        }

        $entry->delete();
        $this->audit->record('giveaways.entries.delete', $giveaway, null, ['user_id' => $entry->user_id]);

        return back()->with('success', 'Customer removed from the draw.');
    }

    public function draw(Giveaway $giveaway)
    {
        if ($giveaway->isDrawn()) {
            return back()->with('error', 'This giveaway has already been drawn.');
        }

        if ($giveaway->status === 'cancelled') {
            return back()->with('error', 'A cancelled giveaway cannot be drawn.');
        }

        $entries = $giveaway->entries()
            ->where('is_winner', false)
            ->whereHas('user')
            ->with('user')
            ->get();

        if ($entries->count() < $giveaway->winners_count) {
            return back()->with('error', "Add at least {$giveaway->winners_count} customer(s) before drawing.");
        }

        $winners = $entries->shuffle()->take($giveaway->winners_count);

        DB::transaction(function () use ($giveaway, $winners) {
            foreach ($winners as $entry) {
                $coupon = $this->gifts->issueCoupon([
                    'label' => $giveaway->prize,
                    'type'  => $giveaway->coupon_type,
                    'value' => (float) $giveaway->coupon_value,
                ], $giveaway->coupon_expiry_days, (float) ($giveaway->coupon_min_order ?? 0));

                $claim = $this->gifts->createClaim([
                    'user_id'       => $entry->user_id,
                    'coupon_id'     => $coupon->id,
                    'customer_name' => $entry->user?->name,
                    'email'         => $entry->user?->email,
                    'prize'         => $giveaway->prize,
                    'mode'          => 'giveaway',
                    'status'        => 'issued',
                    'ip_address'    => request()->ip(),
                    'user_agent'    => 'admin-draw',
                    'claimed_at'    => now(),
                ], $coupon);

                $entry->update([
                    'is_winner'     => true,
                    'drawn_at'      => now(),
                    'gift_claim_id' => $claim->id,
                ]);

                $entry->user?->notify(new GiftWonNotification($claim->load('coupon')));
            }

            $giveaway->update([
                'status'   => 'drawn',
                'drawn_at' => now(),
            ]);
        });

        $this->audit->record('giveaways.draw', $giveaway, null, [
            'winners' => $winners->count(),
        ]);

        return back()->with('success', $winners->count().' winner(s) drawn and gift coupons issued.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name'               => 'required|string|max:160',
            'description'        => 'nullable|string|max:2000',
            'prize'              => 'required|string|max:160',
            'coupon_type'        => 'required|in:percentage,fixed,free_shipping',
            'coupon_value'       => 'required|numeric|min:0',
            'coupon_min_order'   => 'nullable|numeric|min:0',
            'coupon_expiry_days' => 'required|integer|min:1|max:365',
            'winners_count'      => 'required|integer|min:1|max:100',
            'status'             => 'required|in:draft,open,cancelled',
            'draw_at'            => 'nullable|date',
        ]);
    }
}
