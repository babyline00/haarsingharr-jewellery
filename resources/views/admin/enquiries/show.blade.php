@extends('admin.layout')

@section('title', 'Enquiry: '.$enquiry->subject.' — Haarsingharr Admin')

@section('content')
    <x-admin.header :title="$enquiry->subject ?: ('Enquiry from '.$enquiry->name)"
                    :subtitle="ucfirst($enquiry->type).' enquiry — received '.$enquiry->created_at?->format('d M Y H:i').' from '.$enquiry->name">
        <x-slot:actions>
            <a href="{{ route('admin.enquiries.index') }}" class="btn-ghost btn-sm">Back to inbox</a>
        </x-slot:actions>
    </x-admin.header>

    <div class="grid lg:grid-cols-[1fr_320px] gap-6">
        <div class="space-y-4">
            <x-admin.card>
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-serif text-lg">Message</h2>
                    <x-admin.status-pill :status="$enquiry->status === 'new' ? 'pending' : $enquiry->status" :label="ucfirst($enquiry->status)" />
                </div>

                <div class="bg-ivory-alt/40 rounded-md p-4 text-sm leading-relaxed whitespace-pre-line">
                    {{ $enquiry->message }}
                </div>
            </x-admin.card>

            <x-admin.card>
                <h2 class="font-serif text-lg mb-3">Reply by email</h2>
                <form action="{{ route('admin.enquiries.reply', $enquiry) }}" method="post">
                    @csrf
                    <label for="reply-message" class="sr-only">Reply message</label>
                    <textarea id="reply-message" name="message" rows="6" required
                              placeholder="Type your reply — it is emailed to {{ $enquiry->email }} and stored in the notes log.">{{ old('message') }}</textarea>

                    <div class="flex items-center justify-between mt-3">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="resolve" value="1" @checked($enquiry->status !== 'closed')>
                            Mark resolved after sending
                        </label>
                        <x-admin.button type="submit" variant="gold" icon="send">Send reply</x-admin.button>
                    </div>
                </form>
                @error('message') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
            </x-admin.card>

            @if ($enquiry->internal_notes)
                <x-admin.card>
                    <h2 class="font-serif text-lg mb-3">Notes log</h2>
                    <p class="text-sm text-charcoal-soft whitespace-pre-line leading-relaxed">{{ $enquiry->internal_notes }}</p>
                </x-admin.card>
            @endif
        </div>

        <aside class="space-y-4">
            <x-admin.card>
                <h2 class="font-serif text-base mb-3">Customer</h2>
                <dl class="text-sm space-y-2">
                    <div class="flex justify-between"><dt class="text-muted">Name</dt><dd>{{ $enquiry->name }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Email</dt><dd><a class="text-champagne-dark hover:underline" href="mailto:{{ $enquiry->email }}">{{ $enquiry->email }}</a></dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Phone</dt><dd>{{ $enquiry->phone ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">IP</dt><dd class="font-mono text-xs">{{ $enquiry->ip_address ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Account</dt>
                        @php $user = \App\Models\User::where('email', $enquiry->email)->where('type', 'customer')->first(); @endphp
                        <dd>@if ($user) <a href="{{ route('admin.customers.show', $user) }}" class="text-champagne-dark hover:underline">Matched customer</a> @else <span class="text-muted">No match</span> @endif</dd>
                    </div>
                </dl>
            </x-admin.card>

            <x-admin.card>
                <h2 class="font-serif text-base mb-3">Assignment &amp; status</h2>
                <form action="{{ route('admin.enquiries.assign', $enquiry) }}" method="post" class="space-y-3">
                    @csrf
                    <x-admin.field name="assigned_to" label="Assigned to">
                        <x-admin.select id="assigned_to" name="assigned_to" :selected="$enquiry->assigned_to"
                                        placeholder="Unassigned"
                                        :options="$staff->mapWithKeys(fn ($s) => [$s->id => $s->name])->all()" />
                    </x-admin.field>
                    <x-admin.button type="submit" variant="outline" size="sm">Save assignment</x-admin.button>
                </form>

                <form method="POST" action="{{ route('admin.enquiries.bulk') }}" class="mt-4 pt-3 border-t border-charcoal/5 flex gap-2 flex-wrap">
                    @csrf
                    <input type="hidden" name="ids[]" value="{{ $enquiry->id }}">
                    <select name="action" class="text-sm" aria-label="Change status">
                        <option value="open">Mark open</option>
                        <option value="resolve">Mark resolved</option>
                        <option value="close">Mark closed</option>
                    </select>
                    <x-admin.button type="submit" size="sm" variant="outline">Apply</x-admin.button>
                </form>
            </x-admin.card>

            @can('contacts.delete')
                <x-admin.card>
                    <x-admin.confirm :action="route('admin.enquiries.destroy', $enquiry)"
                                     method="DELETE"
                                     title="Delete this enquiry?"
                                     message="The enquiry and its notes are removed permanently."
                                     label="Delete enquiry"
                                     confirm-label="Delete" />
                </x-admin.card>
            @endcan
        </aside>
    </div>
@endsection
