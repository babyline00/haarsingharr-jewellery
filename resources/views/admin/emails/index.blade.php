@extends('admin.layout')

@section('title', 'Email Templates — Haarsingharr Admin')

@section('content')
    <x-admin.header title="Email templates"
                    subtitle="Transactional templates with variables, preview, test sends and per-template enable/disable.">
    </x-admin.header>

    <div class="grid grid-cols-3 gap-3 mb-5">
        <x-admin.stat label="Templates" :value="number_format($stats['total'])" icon="mail" />
        <x-admin.stat label="Active" :value="number_format($stats['active'])" icon="check-circle" />
        <x-admin.stat label="Disabled" :value="number_format($stats['paused'])" icon="pause" />
    </div>

    <x-admin.card :padding="false">
        <x-admin.table caption="Templates by event key">
            <thead>
                <tr>
                    <x-admin.th label="Key" />
                    <x-admin.th label="Name" />
                    <x-admin.th label="Subject" />
                    <x-admin.th label="Variables" />
                    <x-admin.th label="State" />
                    <x-admin.th label="Actions" align="right" />
                </tr>
            </thead>
            <tbody class="divide-y divide-charcoal/5">
                @forelse ($templates as $template)
                    <tr class="hover:bg-ivory-alt/30 transition-colors">
                        <td class="p-3"><span class="font-mono text-xs">{{ $template->key }}</span></td>
                        <td class="p-3 font-medium">{{ $template->name }}</td>
                        <td class="p-3 text-charcoal-soft max-w-[240px]">{{ \Illuminate\Support\Str::limit($template->subject, 50) }}</td>
                        <td class="p-3 text-xs text-muted">
                            {{ collect($template->variables ?? [])->map(fn ($v) => is_array($v) ? ($v['name'] ?? '') : $v)->implode(', ') ?: '—' }}
                        </td>
                        <td class="p-3">
                            <x-admin.status-pill :status="$template->is_active ? 'approved' : 'pending'"
                                                 :label="$template->is_active ? 'Active' : 'Disabled'" />
                        </td>
                        <td class="p-3 text-right whitespace-nowrap">
                            <a href="{{ route('admin.emails.preview', $template) }}" class="text-xs text-champagne-dark hover:underline">Preview</a>
                            <a href="{{ route('admin.emails.edit', $template) }}" class="text-xs text-charcoal-soft hover:underline ml-2">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-admin.empty icon="mail" title="No templates" description="Seed default templates to get started." /></td></tr>
                @endforelse
            </tbody>
        </x-admin.table>
    </x-admin.card>
@endsection
