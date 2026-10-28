@extends('admin.layout')

@section('title', 'FAQs — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">FAQs</h1>
            <p class="text-sm text-muted mt-1">Frequently asked questions shown on the storefront.</p>
        </div>
        @can('faqs.create')
        <a href="{{ route('admin.faqs.create') }}" class="btn-gold">New FAQ</a>
        @endcan
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="text-left p-3">Question</th>
                        <th class="text-left p-3">Category</th>
                        <th class="text-right p-3">Order</th>
                        <th class="text-left p-3">Published</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @foreach ($faqs as $faq)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3">
                                <a href="{{ route('admin.faqs.edit', $faq) }}" class="font-medium hover:text-champagne-dark transition-colors">{{ $faq->question }}</a>
                            </td>
                            <td class="p-3 text-charcoal-soft">{{ $faq->category ?? '—' }}</td>
                            <td class="p-3 text-right">{{ $faq->sort_order }}</td>
                            <td class="p-3">{!! $faq->is_published ? '<span class="badge bg-emerald-100 text-emerald-800">Yes</span>' : '<span class="badge bg-slate-100 text-slate-700">No</span>' !!}</td>
                            <td class="p-3 text-right">
                                <a href="{{ route('admin.faqs.edit', $faq) }}" class="text-xs text-champagne-dark hover:underline">Edit</a>
                                @can('faqs.delete')
                                <x-admin.confirm :action="route('admin.faqs.destroy', $faq)" title="Delete this FAQ?" :message="$faq->question" label="Delete FAQ" />
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                    @if ($faqs->isEmpty())
                        <tr><td colspan="5" class="p-8 text-center text-muted text-sm">No FAQs yet.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-charcoal/5">{{ $faqs->links() }}</div>
    </div>
@endsection
