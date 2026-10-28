@extends('admin.layout')

@section('title', 'Payment Settings — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Payment Settings</h1>
            <p class="text-sm text-muted mt-1">Enable gateways and manage customer-facing instructions. Credentials are read from the environment.</p>
        </div>
        <a href="{{ route('admin.settings.index') }}" class="btn-ghost text-sm">Back</a>
    </div>

    @can('payments.configure')
    <form action="{{ route('admin.settings.payments.update') }}" method="post">
        @csrf
        <div class="space-y-4">
            @foreach ($gateways as $provider => $gateway)
                <div class="card p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="font-serif text-lg">{{ $gateway['label'] }}</h2>
                            @if ($gateway['description']) <p class="text-sm text-muted mt-1">{{ $gateway['description'] }}</p> @endif
                            @if ($gateway['environment'])
                                <span class="badge bg-ivory-alt text-charcoal-soft mt-2">{{ ucfirst($gateway['environment']) }}</span>
                            @endif
                        </div>
                        <div>
                            <input type="hidden" name="enabled[{{ $provider }}]" value="0">
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="enabled[{{ $provider }}]" value="1" @checked($gateway['enabled'])>
                                Enabled
                            </label>
                        </div>
                    </div>

                    <div class="grid md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label for="instructions-{{ $provider }}">Customer-facing instructions</label>
                            <textarea id="instructions-{{ $provider }}" name="instructions[{{ $provider }}]" rows="3" placeholder="Shown on the checkout page.">{{ $gateway['instructions'] }}</textarea>
                        </div>
                        <div>
                            <span class="block text-sm font-medium text-charcoal-soft mb-1.5">Configuration</span>
                            <dl class="text-xs space-y-1 bg-ivory-alt/40 rounded-md p-3">
                                @foreach ($gateway['credentials'] as $key => $credential)
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-muted">{{ str_replace('_', ' ', $key) }}</dt>
                                        <dd class="font-mono text-right">
                                            @if ($credential['is_secret'])
                                                {{ $credential['set'] ? '••••••••' : 'not set' }}
                                            @else
                                                {{ $credential['set'] ? $credential['value'] : '—' }}
                                            @endif
                                        </dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6 flex justify-end">
            <button type="submit" class="btn-gold">Save Payment Settings</button>
        </div>
    </form>
    @else
        <div class="card p-5">
            <p class="text-sm text-muted">You do not have permission to configure payments.</p>
        </div>
    @endcan
@endsection
