@extends('admin.layout')

@section('title', 'Edit Collection — Haarsingharr Admin')

@section('content')
    @php $rules = old('rules', $collection->rules ?? []); @endphp
    <div class="flex items-center justify-between mb-6">
        <h1 class="font-serif text-2xl md:text-3xl">Edit Collection</h1>
        <a href="{{ route('admin.collections.index') }}" class="btn-ghost text-sm">Back</a>
    </div>

    <form action="{{ route('admin.collections.update', $collection) }}" method="post">
        @csrf
        @method('PUT')
        <div class="grid lg:grid-cols-[1fr_320px] gap-6">
            <div class="space-y-4">
                <div class="card p-5">
                    <h2 class="font-serif text-lg mb-4">Details</h2>
                    <div class="space-y-4">
                        <div>
                            <label for="name">Name *</label>
                            <input type="text" id="name" name="name" required value="{{ old('name', $collection->name) }}">
                            @error('name') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="5">{{ old('description', $collection->description) }}</textarea>
                        </div>
                        <div>
                            <label for="banner_image">Banner image URL</label>
                            <input type="text" id="banner_image" name="banner_image" value="{{ old('banner_image', $collection->banner_image) }}">
                        </div>
                    </div>
                </div>

                <div class="card p-5">
                    <h2 class="font-serif text-lg mb-4">Rules</h2>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label for="rule_min_price">Minimum price (Rs.)</label>
                            <input type="number" step="0.01" id="rule_min_price" name="rules[min_price]" value="{{ $rules['min_price'] ?? '' }}">
                        </div>
                        <div>
                            <label for="rule_max_price">Maximum price (Rs.)</label>
                            <input type="number" step="0.01" id="rule_max_price" name="rules[max_price]" value="{{ $rules['max_price'] ?? '' }}">
                        </div>
                        <div>
                            <label for="rule_category">Category ID</label>
                            <input type="number" id="rule_category" name="rules[category_id]" value="{{ $rules['category_id'] ?? '' }}">
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="rules[is_featured]" value="1" @checked(!empty($rules['is_featured']))>
                                Featured only
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="space-y-4">
                <div class="card p-5 space-y-4">
                    <div>
                        <label for="type">Type</label>
                        <select id="type" name="type">
                            <option value="manual" @selected(old('type', $collection->type) === 'manual')>Manual</option>
                            <option value="rule_based" @selected(old('type', $collection->type) === 'rule_based')>Rule based</option>
                        </select>
                    </div>
                    <div>
                        <label for="match_condition">Match condition</label>
                        <select id="match_condition" name="match_condition">
                            <option value="all" @selected(old('match_condition', $collection->match_condition) === 'all')>Match all rules</option>
                            <option value="any" @selected(old('match_condition', $collection->match_condition) === 'any')>Match any rule</option>
                        </select>
                    </div>
                    <div>
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="active" @selected(old('status', $collection->status) === 'active')>Active</option>
                            <option value="inactive" @selected(old('status', $collection->status) === 'inactive')>Inactive</option>
                        </select>
                    </div>
                    <div>
                        <label for="sort_order">Sort order</label>
                        <input type="number" id="sort_order" name="sort_order" min="0" value="{{ old('sort_order', $collection->sort_order) }}">
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="show_on_homepage" value="1" @checked(old('show_on_homepage', $collection->show_on_homepage))>
                        Show on homepage
                    </label>
                </div>

                <div class="card p-5 space-y-4">
                    <h3 class="font-serif text-base">Schedule</h3>
                    <div>
                        <label for="starts_at">Starts at</label>
                        <input type="datetime-local" id="starts_at" name="starts_at" value="{{ old('starts_at', optional($collection->starts_at)->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div>
                        <label for="ends_at">Ends at</label>
                        <input type="datetime-local" id="ends_at" name="ends_at" value="{{ old('ends_at', optional($collection->ends_at)->format('Y-m-d\TH:i')) }}">
                    </div>
                </div>

                <button type="submit" class="btn-gold w-full">Save Changes</button>
                @can('collections.delete')
                <form action="{{ route('admin.collections.destroy', $collection) }}" method="post" onsubmit="return confirm('Archive this collection?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-ghost w-full text-danger">Archive Collection</button>
                </form>
                @endcan
            </aside>
        </div>
    </form>
@endsection
