@extends('admin.layout')

@section('title', 'New FAQ — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="font-serif text-2xl md:text-3xl">New FAQ</h1>
        <a href="{{ route('admin.faqs.index') }}" class="btn-ghost text-sm">Cancel</a>
    </div>

    <form action="{{ route('admin.faqs.store') }}" method="post">
        @csrf
        <div class="grid lg:grid-cols-[1fr_320px] gap-6">
            <div class="card p-5 space-y-4">
                <div>
                    <label for="question">Question *</label>
                    <input type="text" id="question" name="question" required value="{{ old('question') }}" autofocus>
                    @error('question') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="answer">Answer *</label>
                    <textarea id="answer" name="answer" rows="6" required>{{ old('answer') }}</textarea>
                    @error('answer') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                </div>
            </div>

            <aside class="space-y-4">
                <div class="card p-5 space-y-4">
                    <div>
                        <label for="category">Category</label>
                        <input type="text" id="category" name="category" value="{{ old('category') }}" placeholder="e.g. Orders">
                    </div>
                    <div>
                        <label for="sort_order">Sort order</label>
                        <input type="number" min="0" id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}">
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_published" value="1" @checked(old('is_published', true))>
                        Published
                    </label>
                </div>
                <button type="submit" class="btn-gold w-full">Create FAQ</button>
            </aside>
        </div>
    </form>
@endsection
