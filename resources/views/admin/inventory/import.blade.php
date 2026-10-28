@extends('admin.layout')

@section('title', 'Import Stock — Haarsingharr Admin')

@section('content')
    <x-admin.header title="Import stock"
                    subtitle="Apply new on-hand counts by SKU. Every row is posted through the inventory ledger.">
        <x-slot:actions>
            <x-admin.button :href="route('admin.inventory.index')" variant="ghost" size="sm" icon="arrow-left">
                Back to inventory
            </x-admin.button>
        </x-slot:actions>
    </x-admin.header>

    <x-admin.error-summary />

    @if (count($importErrors) > 0)
        <x-admin.card title="Skipped rows" subtitle="These rows were not applied. Fix them and upload the file again."
                      class="mb-5">
            <ul class="divide-y divide-charcoal/5">
                @foreach ($importErrors as $error)
                    <li class="py-2 flex gap-3 text-sm">
                        <span class="font-mono text-xs text-muted w-12 shrink-0">Line {{ $error['line'] }}</span>
                        <span class="text-danger">{{ $error['message'] }}</span>
                    </li>
                @endforeach
            </ul>
        </x-admin.card>
    @endif

    <div class="grid lg:grid-cols-[1fr_340px] gap-6">
        <x-admin.card title="Upload CSV" subtitle="Maximum 4 MB and 1,000 data rows.">
            <form action="{{ route('admin.inventory.import.store') }}" method="post" enctype="multipart/form-data" class="space-y-5">
                @csrf

                <x-admin.field name="file" label="CSV file" :required="true"
                               hint="UTF-8 CSV with a header row. Unknown SKUs are skipped, never created.">
                    <x-admin.input name="file" type="file" accept=".csv,text/csv" required />
                </x-admin.field>

                <div class="flex flex-wrap gap-2">
                    <x-admin.button type="submit" icon="upload">Apply stock counts</x-admin.button>
                    <x-admin.button :href="route('admin.inventory.index')" variant="ghost">Cancel</x-admin.button>
                </div>
            </form>
        </x-admin.card>

        <div class="space-y-4">
            <x-admin.card title="CSV columns">
                <dl class="text-sm space-y-2">
                    <div class="flex justify-between gap-3">
                        <dt class="font-mono text-xs">sku</dt>
                        <dd class="text-muted text-right">Unique variant SKU (required)</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="font-mono text-xs">new_stock</dt>
                        <dd class="text-muted text-right">New on-hand count ≥ 0 (required)</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="font-mono text-xs">reason</dt>
                        <dd class="text-muted text-right">Defaults to physical_count</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="font-mono text-xs">notes</dt>
                        <dd class="text-muted text-right">Optional note saved on the movement</dd>
                    </div>
                </dl>
            </x-admin.card>

            <x-admin.card title="Safe by design">
                <ul class="text-sm text-charcoal-soft space-y-2 list-disc list-inside">
                    <li>Imported counts replace on-hand stock, not reserved stock.</li>
                    <li>Each change creates a ledger entry and low-stock alert refresh.</li>
                    <li>Rows with missing SKUs or invalid counts are reported and skipped.</li>
                    <li>Export the current stock first if you want a backup snapshot.</li>
                </ul>
            </x-admin.card>

            <x-admin.card title="Example">
                <pre class="text-xs leading-relaxed bg-ivory-alt/40 rounded-md p-3 overflow-x-auto"><code>sku,new_stock,reason,notes
HS-RING-001-16,24,physical_count,October count
HS-RING-001-18,8,physical_count,October count
HS-NECK-004,0,damaged,Stones loose</code></pre>
            </x-admin.card>
        </div>
    </div>
@endsection
