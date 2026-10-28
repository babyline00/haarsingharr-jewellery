@extends('admin.layout')

@section('title', 'Notifications — Haarsingharr Admin')

@section('content')
    <x-admin.header
        title="Notifications"
        :subtitle="$notifications->total() . ' ' . ($filter === 'unread' ? 'unread ' : '') . Str::plural('notification', $notifications->total())">
        <x-slot:actions>
            <x-admin.button
                :href="route('admin.notifications.index', ['filter' => $filter === 'unread' ? 'all' : 'unread'])"
                variant="outline" size="sm" icon="eye">
                {{ $filter === 'unread' ? 'Show all' : 'Show unread' }}
            </x-admin.button>

            @if ($filter === 'unread')
                <form action="{{ route('admin.notifications.read-all') }}" method="post">
                    @csrf
                    <x-admin.button type="submit" size="sm" icon="check">Mark all read</x-admin.button>
                </form>
            @endif
        </x-slot:actions>
    </x-admin.header>

    <x-admin.card :flush="true">
        @forelse ($notifications as $notification)
            <div class="flex items-start gap-4 p-4 border-b border-charcoal/5 last:border-0">
                <span class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 {{ $notification->read_at ? 'bg-ivory-alt text-muted' : 'bg-champagne/20 text-champagne-dark' }}">
                    <x-admin.icon name="bell" :size="16" />
                </span>

                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium {{ $notification->read_at ? 'text-charcoal-soft' : 'text-charcoal' }}">
                        {{ $notification->data['title'] ?? 'Notification' }}
                    </p>

                    @if (! empty($notification->data['message']))
                        <p class="text-sm text-muted mt-0.5">{{ $notification->data['message'] }}</p>
                    @endif

                    <p class="text-xs text-muted mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                </div>

                <div class="flex items-center gap-2 flex-shrink-0">
                    @if (! empty($notification->data['url']))
                        <x-admin.button :href="$notification->data['url']" variant="outline" size="sm">Open</x-admin.button>
                    @endif

                    @unless ($notification->read_at)
                        <form action="{{ route('admin.notifications.read', $notification->id) }}" method="post">
                            @csrf
                            <button type="submit" class="btn-ghost btn-sm">Mark read</button>
                        </form>
                    @endunless
                </div>
            </div>
        @empty
            <x-admin.empty icon="bell"
                           title="You're all caught up"
                           description="No {{ $filter === 'unread' ? 'unread ' : '' }}notifications right now." />
        @endforelse
    </x-admin.card>

    @if ($notifications->total() > 0)
        <div class="mt-4">
            <x-admin.pagination :paginator="$notifications" />
        </div>
    @endif
@endsection
