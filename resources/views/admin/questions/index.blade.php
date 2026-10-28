@extends('admin.layout')

@section('title', 'Product Questions — Haarsingharr Admin')

@section('content')
    <x-admin.header title="Product Questions"
                    subtitle="Answer questions from customers. Answers appear on the storefront once approved.">
        <x-slot:actions>
            <x-admin.button :href="route('admin.reviews.index')" variant="outline" size="sm" icon="star">
                Reviews
            </x-admin.button>
        </x-slot:actions>
    </x-admin.header>

    <x-admin.tabs :items="collect([
        ['label' => 'All ('.$counts['all'].')', 'url' => route('admin.questions.index'), 'active' => ($filters['status'] ?? '') === ''],
        ['label' => 'Pending ('.$counts['pending'].')', 'url' => route('admin.questions.index', ['status' => 'pending']), 'active' => ($filters['status'] ?? '') === 'pending'],
        ['label' => 'Unanswered ('.$counts['unanswered'].')', 'url' => route('admin.questions.index', ['status' => 'unanswered']), 'active' => ($filters['status'] ?? '') === 'unanswered'],
        ['label' => 'Published ('.$counts['approved'].')', 'url' => route('admin.questions.index', ['status' => 'approved']), 'active' => ($filters['status'] ?? '') === 'approved'],
    ])->values()->all()" />

    <x-admin.filter-bar>
        <div class="flex-1 min-w-[220px]">
            <x-admin.field name="q" label="Search">
                <x-admin.input id="question-search" name="q" type="search" :value="$filters['q'] ?? ''"
                               placeholder="Question text, customer or product" />
            </x-admin.field>
        </div>

        @if (($filters['status'] ?? '') !== '')
            <input type="hidden" name="status" value="{{ $filters['status'] }}">
        @endif

        <x-admin.button type="submit" variant="outline" size="sm" icon="filter">Apply</x-admin.button>

        @if (array_filter($filters ?? []))
            <x-admin.button :href="route('admin.questions.index')" variant="ghost" size="sm">Clear</x-admin.button>
        @endif
    </x-admin.filter-bar>

    <form method="POST" action="{{ route('admin.questions.bulk') }}" x-data="{ selected: [] }">
        @csrf

        <div x-show="selected.length" x-cloak
             class="card p-3 mb-3 flex flex-wrap items-center gap-3 border-champagne/40 bg-ivory-alt/40">
            <span class="text-sm font-medium" x-text="`${selected.length} selected`"></span>

            <select name="action" class="text-sm max-w-[180px]" aria-label="Bulk action">
                <option value="approve">Approve &amp; publish</option>
                <option value="reject">Unpublish</option>
                <option value="delete">Delete</option>
            </select>

            <x-admin.button type="submit" size="sm" variant="outline">Apply to selected</x-admin.button>
        </div>

        <x-admin.card :padding="false">
            <x-admin.table caption="Product questions">
                <thead>
                    <tr>
                        <th scope="col" class="p-3 w-10">
                            <span class="sr-only">Select all</span>
                            <input type="checkbox"
                                   x-on:change="selected = $event.target.checked ? @js($questions->pluck('id')->map(fn ($id) => (string) $id)->all()) : []"
                                   aria-label="Select all questions on this page">
                        </th>
                        <x-admin.th label="Product" />
                        <x-admin.th label="Asked by" />
                        <x-admin.th label="Question" />
                        <x-admin.th label="Answer" />
                        <x-admin.th label="Status" />
                        <x-admin.th label="Date" />
                        <x-admin.th label="Actions" align="right" />
                    </tr>
                </thead>

                <tbody class="divide-y divide-charcoal/5">
                    @forelse ($questions as $question)
                        <tr class="hover:bg-ivory-alt/30 transition-colors align-top">
                            <td class="p-3">
                                <input type="checkbox" name="ids[]" value="{{ $question->id }}"
                                       x-model="selected" aria-label="Select question {{ $question->id }}">
                            </td>
                            <td class="p-3">
                                @if ($question->product)
                                    <a href="{{ route('admin.products.edit', $question->product) }}"
                                       class="font-medium text-champagne-dark hover:underline">{{ $question->product->name }}</a>
                                @else
                                    <span class="text-muted">Product removed</span>
                                @endif
                            </td>
                            <td class="p-3 text-charcoal-soft">
                                {{ $question->user?->email ?? $question->customer_name ?? 'Guest' }}
                            </td>
                            <td class="p-3 max-w-[260px] text-sm">{{ $question->question }}</td>
                            <td class="p-3 max-w-[260px] text-sm">
                                @if ($question->answer)
                                    <div class="text-charcoal-soft whitespace-pre-line">{{ \Illuminate\Support\Str::limit($question->answer, 120) }}</div>
                                    <div class="text-xs text-muted mt-1">by {{ $question->answerer?->name ?? 'staff' }} · {{ $question->answered_at?->format('M j, Y') }}</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="p-3">
                                <x-admin.status-pill :status="$question->is_approved ? 'approved' : 'pending'" :label="$question->is_approved ? 'Published' : 'Pending'" />
                                @if ($question->answer) <span class="badge bg-ivory-alt text-charcoal-soft mt-1">Answered</span> @endif
                            </td>
                            <td class="p-3 text-xs text-muted whitespace-nowrap">{{ $question->created_at?->format('M j, Y') }}</td>
                            <td class="p-3 text-right">
                                <details class="inline-block text-left">
                                    <summary class="text-xs text-champagne-dark hover:underline cursor-pointer select-none">{{ $question->answer ? 'Edit answer' : 'Answer' }}</summary>
                                    <form action="{{ route('admin.questions.answer', $question) }}" method="post" class="card p-3 mt-2 w-[280px]">
                                        @csrf
                                        <label for="answer-{{ $question->id }}" class="sr-only">Answer</label>
                                        <textarea id="answer-{{ $question->id }}" name="answer" rows="4" required
                                                  placeholder="Public answer shown with the question">{{ old('answer', $question->answer) }}</textarea>
                                        <x-admin.button type="submit" size="sm" class="mt-2 w-full">Save answer</x-admin.button>
                                    </form>
                                </details>

                                @if (! $question->is_approved)
                                    <form action="{{ route('admin.questions.approve', $question) }}" method="post" class="inline ml-2">
                                        @csrf
                                        <button class="text-xs text-emerald-700 hover:underline">Publish</button>
                                    </form>
                                @else
                                    <form action="{{ route('admin.questions.reject', $question) }}" method="post" class="inline ml-2">
                                        @csrf
                                        <button class="text-xs text-charcoal-soft hover:underline">Unpublish</button>
                                    </form>
                                @endif

                                @canany(['questions.delete'])
                                    <x-admin.confirm :action="route('admin.questions.destroy', $question)"
                                                     method="DELETE"
                                                     title="Delete this question?"
                                                     message="The question and its answer are removed permanently."
                                                     label="Delete"
                                                     confirm-label="Delete question" />
                                @endcanany
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <x-admin.empty icon="inbox" title="No product questions"
                                               description="Questions from customers (and staff) will queue here for answering." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-admin.table>

            @if ($questions->total() > 0)
                <div class="px-4 border-t border-charcoal/5">
                    <x-admin.pagination :paginator="$questions" />
                </div>
            @endif
        </x-admin.card>
    </form>
@endsection
