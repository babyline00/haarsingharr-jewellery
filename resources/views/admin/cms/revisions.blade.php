@extends('admin.layout')

@section('title', 'Revisions: '.$page->title.' — Haarsingharr Admin')

@section('content')
    <x-admin.header :title="'Revisions — '.$page->title"
                    subtitle="Every save is snapshotted. Restoring first snapshots the current state so rollbacks are reversible.">
        <x-slot:actions>
            <a href="{{ route('admin.pages.edit', $page) }}" class="btn-outline btn-sm">Edit page</a>
            <a href="{{ route('admin.pages.preview', $page) }}" target="_blank" rel="noopener" class="btn-ghost btn-sm">Preview</a>
        </x-slot:actions>
    </x-admin.header>

    <x-admin.card :padding="false">
        <x-admin.table caption="Page revisions, newest first">
            <thead>
                <tr>
                    <x-admin.th label="Saved" />
                    <x-admin.th label="By" />
                    <x-admin.th label="Title" />
                    <x-admin.th label="Status" />
                    <x-admin.th label="Content size" />
                    <x-admin.th label="Actions" align="right" />
                </tr>
            </thead>
            <tbody class="divide-y divide-charcoal/5">
                @forelse ($revisions as $revision)
                    <tr class="hover:bg-ivory-alt/30 transition-colors">
                        <td class="p-3 whitespace-nowrap">{{ $revision->created_at?->format('d M Y H:i') }}</td>
                        <td class="p-3 text-charcoal-soft">{{ $revision->user?->name ?? 'system' }}</td>
                        <td class="p-3 font-medium">{{ $revision->title }}</td>
                        <td class="p-3"><x-admin.status-pill :status="$revision->status === 'published' ? 'approved' : 'pending'" :label="ucfirst($revision->status ?? 'n/a')" /></td>
                        <td class="p-3 text-xs text-muted tabular-nums">{{ number_format(strlen($revision->content ?? '')) }} chars</td>
                        <td class="p-3 text-right whitespace-nowrap">
                            @can('cms.update')
                                <x-admin.confirm :action="route('admin.pages.revisions.restore', [$page, $revision])"
                                                 method="POST"
                                                 title="Restore this revision?"
                                                 message="The page is rolled back to this snapshot. The current state is saved as a new revision first."
                                                 label="Restore"
                                                 confirm-label="Restore revision" />
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">
                        <x-admin.empty icon="file-text" title="No revisions yet"
                                       description="Revisions are created automatically every time the page is updated." />
                    </td></tr>
                @endforelse
            </tbody>
        </x-admin.table>

        <div class="px-4 border-t border-charcoal/5">
            <x-admin.pagination :paginator="$revisions" />
        </div>
    </x-admin.card>
@endsection
