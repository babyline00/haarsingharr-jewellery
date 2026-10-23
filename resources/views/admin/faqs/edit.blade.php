@extends('admin.layout')

@section('title', 'Edit FAQ — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="font-serif text-2xl md:text-3xl">Edit FAQ</h1>
        <a href="{{ route('admin.faqs.index') }}" class="btn-ghost text-sm">Back</a>
    </div>

    <form action="{{ route('admin.faqs.update', $faq) }}" method="post">
        @csrf
        @method('PUT')
        <div class="grid lg:grid-cols-[1fr_320px] gap-6">
            <div class="card p-5 space-y-4">
                <div>
                    <label for="question">Question *</label>
                    <input type="text" id="question" name="question" required value="{{ old('question', $faq->question) }}">
                    @error('question') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="answer">Answer *</label>
                    <textarea id="answer" name="answer" rows="6" required class="ckeditor">{{ old('answer', $faq->answer) }}</textarea>
                    @error('answer') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                </div>
            </div>

            <aside class="space-y-4">
                <div class="card p-5 space-y-4">
                    <div>
                        <label for="category">Category</label>
                        <input type="text" id="category" name="category" value="{{ old('category', $faq->category) }}">
                    </div>
                    <div>
                        <label for="sort_order">Sort order</label>
                        <input type="number" min="0" id="sort_order" name="sort_order" value="{{ old('sort_order', $faq->sort_order) }}">
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $faq->is_published))>
                        Published
                    </label>
                </div>
                <button type="submit" class="btn-gold w-full">Save Changes</button>
                @can('faqs.delete')
                <form action="{{ route('admin.faqs.destroy', $faq) }}" method="post" onsubmit="return confirm('Delete this FAQ?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-ghost w-full text-danger">Delete FAQ</button>
                </form>
                @endcan
            </aside>
        </div>
    </form>
@endsection
