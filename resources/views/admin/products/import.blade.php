@extends('admin.layout')

@section('title', 'Import Products — Haarsingharr Admin')

@section('content')
    <x-admin.header title="Import products"
                    subtitle="Upload a CSV, review a dry-run, then confirm the import. Nothing is written until you confirm.">
        <x-slot:actions>
            <x-admin.button :href="route('admin.products.index')" variant="ghost" size="sm" icon="arrow-left">
                Back to products
            </x-admin.button>
        </x-slot:actions>
    </x-admin.header>

    <x-admin.error-summary />

    <div class="grid lg:grid-cols-[1fr_340px] gap-6">
        <x-admin.card title="Upload CSV" subtitle="Maximum 4 MB and 500 data rows.">
            <form action="{{ route('admin.products.import.preview') }}" method="post" enctype="multipart/form-data" class="space-y-5">
                @csrf

                <x-admin.field name="file" label="CSV file" :required="true"
                               hint="Use UTF-8 CSV with a header row. Existing SKUs are rejected.">
                    <x-admin.input name="file" type="file" accept=".csv,text/csv" required />
                </x-admin.field>

                <div class="flex flex-wrap gap-2">
                    <x-admin.button type="submit" icon="upload">Upload &amp; preview</x-admin.button>
                    <x-admin.button :href="route('admin.products.index')" variant="ghost">Cancel</x-admin.button>
                </div>
            </form>
        </x-admin.card>

        <div class="space-y-4">
            <x-admin.card title="Required columns">
                <dl class="text-sm space-y-2">
                    <div class="flex justify-between gap-3">
                        <dt class="font-mono text-xs">name</dt>
                        <dd class="text-muted text-right">Product name (required)</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="font-mono text-xs">regular_price</dt>
                        <dd class="text-muted text-right">Number ≥ 0 (required)</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="font-mono text-xs">sku</dt>
                        <dd class="text-muted text-right">Unique product SKU</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="font-mono text-xs">category</dt>
                        <dd class="text-muted text-right">Category name or slug</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="font-mono text-xs">sale_price</dt>
                        <dd class="text-muted text-right">Lower than regular price</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="font-mono text-xs">cost_price</dt>
                        <dd class="text-muted text-right">Optional cost</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="font-mono text-xs">status</dt>
                        <dd class="text-muted text-right">draft / active / archived</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="font-mono text-xs">stock</dt>
                        <dd class="text-muted text-right">Opening stock on default variant</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="font-mono text-xs">description</dt>
                        <dd class="text-muted text-right">Optional long description</dd>
                    </div>
                </dl>
            </x-admin.card>

            <x-admin.card title="How it works">
                <ol class="text-sm text-charcoal-soft space-y-2 list-decimal list-inside">
                    <li>Every valid row creates one product and one default variant.</li>
                    <li>Opening stock is posted through the inventory ledger.</li>
                    <li>Invalid rows are listed with line numbers and skipped.</li>
                    <li>Imported products keep the status in the CSV (default <span class="font-mono text-xs">draft</span>).</li>
                </ol>
            </x-admin.card>
        </div>
    </div>
@endsection
