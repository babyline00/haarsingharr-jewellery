@extends('admin.layout')

@section('title', 'New Product — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="font-serif text-2xl md:text-3xl">New Product</h1>
        <a href="{{ route('admin.products.index') }}" class="btn-ghost text-sm">Cancel</a>
    </div>

    <form action="{{ route('admin.products.store') }}" method="post">
        @csrf
        <div class="grid lg:grid-cols-[1fr_320px] gap-6">
            <div class="space-y-4">
                <div class="card p-5">
                    <h2 class="font-serif text-lg mb-4">Basic Information</h2>
                    <div class="space-y-4">
                        <div>
                            <label for="name">Product name *</label>
                            <input type="text" id="name" name="name" required value="{{ old('name') }}" autofocus>
                            @error('name') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="short_description">Short description</label>
                            <input type="text" id="short_description" name="short_description" value="{{ old('short_description') }}">
                        </div>
                        <div>
                            <label for="description">Detailed description</label>
                            <textarea id="description" name="description" rows="6">{{ old('description') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="card p-5">
                    <h2 class="font-serif text-lg mb-4">Pricing</h2>
                    <div class="grid sm:grid-cols-3 gap-4">
                        <div>
                            <label for="regular_price">Regular price (Rs.) *</label>
                            <input type="number" id="regular_price" name="regular_price" step="0.01" required value="{{ old('regular_price') }}">
                        </div>
                        <div>
                            <label for="sale_price">Sale price (Rs.)</label>
                            <input type="number" id="sale_price" name="sale_price" step="0.01" value="{{ old('sale_price') }}">
                        </div>
                        @can('products.adjust_cost')
                        <div>
                            <label for="cost_price">Cost price (Rs.)</label>
                            <input type="number" id="cost_price" name="cost_price" step="0.01" value="{{ old('cost_price') }}">
                        </div>
                        @endcan
                    </div>
                </div>
            </div>

            <aside class="space-y-4">
                <div class="card p-5">
                    <h3 class="font-serif text-base mb-3">Status</h3>
                    <select name="status">
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(old('status', 'draft') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="card p-5">
                    <h3 class="font-serif text-base mb-3">Organisation</h3>
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id">
                        <option value="">—</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>

                    <label for="sku" class="mt-3 block">SKU</label>
                    <input type="text" id="sku" name="sku" value="{{ old('sku') }}">
                </div>

                <button type="submit" class="btn-gold w-full">Create Product</button>
            </aside>
        </div>
    </form>
@endsection
