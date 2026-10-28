@extends('admin.layout')

@section('title', 'Preview: '.$template->name.' — Haarsingharr Admin')

@section('content')
    <x-admin.header :title="'Preview — '.$template->name"
                    :subtitle="'Rendered with sample data; test sends use the same renderer.'">
        <x-slot:actions>
            <a href="{{ route('admin.emails.edit', $template) }}" class="btn-outline btn-sm">Edit template</a>
            <a href="{{ route('admin.emails.index') }}" class="btn-ghost btn-sm">Back</a>
        </x-slot:actions>
    </x-admin.header>

    <div class="grid lg:grid-cols-[1fr_280px] gap-6">
        <x-admin.card>
            <dl class="text-sm mb-4 pb-3 border-b border-charcoal/10">
                <div class="flex gap-2 mb-1"><dt class="text-muted w-20 shrink-0">Subject</dt><dd class="font-medium">{{ $rendered['subject'] }}</dd></div>
                <div class="flex gap-2"><dt class="text-muted w-20 shrink-0">Sample</dt>
                    <dd class="text-xs font-mono text-muted">{{ collect($sample)->map(fn ($v, $k) => $k.'='.$v)->implode(' · ') }}</dd></div>
            </dl>
            <div class="card bg-white max-h-[520px] overflow-auto p-5" x-data="{ raw: false }">
                <div class="flex justify-end mb-2">
                    <label class="flex items-center gap-2 text-xs">
                        <input type="checkbox" x-model="raw"> Show raw HTML
                    </label>
                </div>
                <iframe srcdoc="{{ $rendered['body'] }}" title="Rendered email preview"
                        class="w-full min-h-[420px] rounded border border-charcoal/10 bg-white"
                        x-show="!raw"></iframe>
                <pre class="text-xs overflow-auto whitespace-pre-wrap" x-show="raw" x-cloak>{{ $rendered['body'] }}</pre>
            </div>
        </x-admin.card>

        <x-admin.card>
            <h2 class="font-serif text-base mb-3">Sample data</h2>
            <textarea rows="10" readonly class="text-xs font-mono">{{ collect($sample)->map(fn ($v, $k) => '&#123;&#123;'.e($k).'&#125;&#125; → '.e($v))->implode("\n") }}</textarea>
        </x-admin.card>
    </div>
@endsection
