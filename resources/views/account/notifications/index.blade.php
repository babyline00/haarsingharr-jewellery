@extends('layouts.storefront')

@section('title', 'Notifications — Haarsingharr')

@section('content')
    <section class="py-12">
        <div class="container-haars max-w-3xl">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-8">
                <div>
                    <h1 class="font-serif text-3xl md:text-4xl">Notifications</h1>
                    <p class="text-sm text-muted mt-1">Order updates and messages from Haarsingharr.</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('account.dashboard') }}" class="text-sm text-muted hover:text-charcoal">← My account</a>
                    <form action="{{ route('account.notifications.read-all') }}" method="post">
                        @csrf
                        <button type="submit" class="btn-outline text-sm">Mark all read</button>
                    </form>
                </div>
            </div>

            <div class="card overflow-hidden">
                <div class="divide-y divide-charcoal/5">
                    @forelse ($notifications as $notification)
                        @php
                            $data = $notification->data;
                            $isUnread = $notification->read_at === null;
                        @endphp
                        <a href="{{ $data['url'] ?? $data['action_url'] ?? '#' }}"
                           class="flex gap-4 p-4 transition-colors hover:bg-ivory-alt/40 {{ $isUnread ? 'bg-champagne/5' : '' }}">
                            <div class="mt-0.5 flex h-9 w-9 flex-none items-center justify-center rounded-full {{ $isUnread ? 'bg-champagne/20 text-champagne-dark' : 'bg-charcoal/5 text-muted' }}">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-medium">{{ $data['title'] ?? 'Notification' }}</p>
                                    @if ($isUnread)
                                        <span class="badge badge-sm bg-champagne text-charcoal">New</span>
                                    @endif
                                </div>
                                @if (! empty($data['message']))
                                    <p class="mt-1 text-sm text-charcoal-soft">{{ $data['message'] }}</p>
                                @endif
                                <p class="mt-1 text-xs text-muted">{{ $notification->created_at->diffForHumans() }}</p>
                            </div>
                        </a>
                    @empty
                        <div class="p-10 text-center text-sm text-muted">
                            No notifications yet.
                            <a href="{{ route('shop.index') }}" class="text-champagne-dark hover:underline">Browse the collection →</a>
                        </div>
                    @endforelse
                </div>
                @if ($notifications->hasPages())
                    <div class="border-t border-charcoal/5 p-3">{{ $notifications->links() }}</div>
                @endif
            </div>
        </div>
    </section>
@endsection
