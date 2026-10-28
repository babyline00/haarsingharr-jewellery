@extends('admin.layout')

@section('title', 'Product Import Preview — Haarsingharr Admin')

@section('content')
    <x-admin.header title="Import preview"
                    :subtitle="'Dry run for '.$filename.' — '.number_format($total).' data row(s) checked.'">
        <x-slot:actions>
            <x-admin.button :href="route('admin.products.import')" variant="outline" size="sm" icon="arrow-left">
                Choose another file
            </x-admin.button>
        </x-slot:actions>
    </x-admin.header>

    <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 mb-5">
        <x-admin.stat label="Rows checked" :value="number_format($total)" icon="list" />
        <x-admin.stat label="Ready to import" :value="number_format(count($valid))" icon="check-circle" />
        <x-admin.stat label="Rows with errors" :value="number_format(count($errors))" icon="alert-triangle" />
    </div>

    @if (count($errors) > 0)
        <x-admin.card title="Rows that will be skipped" subtitle="Fix these in the CSV and re-upload, or import the valid rows now." class="mb-5">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <x-admin.th label="Line" />
                            <x-admin.th label="Problem" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-charcoal/5">
                        @foreach ($errors as $error)
                            <tr>
                                <td class="p-3 font-mono text-xs">{{ $error['line'] }}</td>
                                <td class="p-3 text-sm">
                                    <ul class="list-disc list-inside space-y-0.5">
                                        @foreach ($error['messages'] as $message)
                                            <li>{{ $message }}</li>
                                        @endforeach
                                    </ul>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-admin.card>
    @endif

    <x-admin.card title="Valid rows" subtitle="These products will be created when you confirm." :padding="false" class="mb-5">
        @if (count($valid) === 0)
            <x-admin.empty icon="upload" title="Nothing to import"
                           description="Every row needs attention. Fix the CSV and upload it again.">
                <x-admin.button :href="route('admin.products.import')" variant="outline" size="sm">Upload again</x-admin.button>
            </x-admin.empty>
        @else
            <x-admin.table caption="Products ready to import">
                <thead>
                    <tr>
                        <x-admin.th label="Line" />
                        <x-admin.th label="Name" />
                        <x-admin.th label="SKU" />
                        <x-admin.th label="Category" />
                        <x-admin.th label="Price" align="right" />
                        <x-admin.th label="Stock" align="right" />
                        <x-admin.th label="Status" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @foreach ($valid as $row)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3 font-mono text-xs text-muted">{{ $row['line'] }}</td>
                            <td class="p-3 font-medium">{{ $row['name'] }}</td>
                            <td class="p-3 font-mono text-xs">{{ $row['sku'] ?? '—' }}</td>
                            <td class="p-3 text-charcoal-soft">{{ $row['category'] ?: '—' }}</td>
                            <td class="p-3 text-right"><x-admin.money :amount="$row['regular_price']" /></td>
                            <td class="p-3 text-right tabular-nums">{{ number_format($row['stock']) }}</td>
                            <td class="p-3"><x-admin.status-pill :status="$row['status']" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </x-admin.table>
        @endif
    </x-admin.card>

    @if (count($valid) > 0)
        <form action="{{ route('admin.products.import.store') }}" method="post"
              class="card p-4 flex flex-wrap items-center justify-between gap-3">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <p class="text-sm text-charcoal-soft">
                Ready to import <span class="font-medium">{{ number_format(count($valid)) }}</span> product(s).
                @if (count($errors) > 0)
                    {{ number_format(count($errors)) }} invalid row(s) will be skipped.
                @endif
            </p>

            <div class="flex gap-2">
                <x-admin.button :href="route('admin.products.import')" variant="ghost">Cancel</x-admin.button>
                <x-admin.button type="submit" icon="check">Confirm import</x-admin.button>
            </div>
        </form>
    @endif
@endsection
