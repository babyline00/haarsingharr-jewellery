@extends('admin.layout')

@section('title', 'Blog — Haarsingharr Admin')

@section('content')
    <x-admin.header title="Blog"
                    subtitle="Editorial posts with scheduling, tags, SEO and a staff-only preview.">
        <x-slot:actions>
            @can('blog.create')
                <x-admin.button :href="route('admin.blog.create')" size="sm" icon="plus">New Post</x-admin.button>
            @endcan
        </x-slot:actions>
    </x-admin.header>

    <x-admin.tabs :items="collect([
        ['label' => 'All ('.$counts['all'].')', 'url' => route('admin.blog.index'), 'active' => ($filters['status'] ?? '') === ''],
        ['label' => 'Published ('.$counts['published'].')', 'url' => route('admin.blog.index', ['status' => 'published']), 'active' => ($filters['status'] ?? '') === 'published'],
        ['label' => 'Scheduled ('.$counts['scheduled'].')', 'url' => route('admin.blog.index', ['status' => 'scheduled']), 'active' => ($filters['status'] ?? '') === 'scheduled'],
        ['label' => 'Drafts ('.$counts['draft'].')', 'url' => route('admin.blog.index', ['status' => 'draft']), 'active' => ($filters['status'] ?? '') === 'draft'],
        ['label' => 'Archived ('.$counts['archived'].')', 'url' => route('admin.blog.index', ['status' => 'archived']), 'active' => ($filters['status'] ?? '') === 'archived'],
    ])->values()->all()" />

    <x-admin.filter-bar>
        <div class="flex-1 min-w-[200px]">
            <x-admin.field name="q" label="Search">
                <x-admin.input id="blog-search" name="q" type="search" :value="$filters['q'] ?? ''" placeholder="Title or content" />
            </x-admin.field>
        </div>
        <div class="w-full sm:w-44">
            <x-admin.field name="tag" label="Tag">
                <x-admin.select id="blog-tag" name="tag" :selected="$filters['tag'] ?? ''"
                                placeholder="All tags"
                                :options="$tags->mapWithKeys(fn ($tag) => [$tag->id => $tag->name.' ('.$tag->posts_count.')'])->all()" />
            </x-admin.field>
        </div>

        @if (($filters['status'] ?? '') !== '')
            <input type="hidden" name="status" value="{{ $filters['status'] }}">
        @endif

        <x-admin.button type="submit" variant="outline" size="sm" icon="filter">Apply</x-admin.button>

        @if (array_filter($filters ?? []))
            <x-admin.button :href="route('admin.blog.index')" variant="ghost" size="sm">Clear</x-admin.button>
        @endif
    </x-admin.filter-bar>

    <form method="POST" action="{{ route('admin.blog.bulk') }}" x-data="{ selected: [] }">
        @csrf

        <div x-show="selected.length" x-cloak
             class="card p-3 mb-3 flex flex-wrap items-center gap-3 border-champagne/40 bg-ivory-alt/40">
            <span class="text-sm font-medium" x-text="`${selected.length} selected`"></span>
            <select name="action" class="text-sm max-w-[180px]" aria-label="Bulk action">
                <option value="publish">Publish</option>
                <option value="schedule_confirm">Set scheduled (future date only)</option>
                <option value="draft">Set as draft</option>
                <option value="archive">Archive</option>
                <option value="delete">Delete</option>
            </select>
            <x-admin.button type="submit" size="sm" variant="outline">Apply to selected</x-admin.button>
        </div>

        <x-admin.card :padding="false">
            <x-admin.table caption="Blog posts">
                <thead>
                    <tr>
                        <th scope="col" class="p-3 w-10">
                            <span class="sr-only">Select all</span>
                            <input type="checkbox"
                                   x-on:change="selected = $event.target.checked ? @js($posts->pluck('id')->map(fn ($id) => (string) $id)->all()) : []"
                                   aria-label="Select all blog posts on this page">
                        </th>
                        <x-admin.th label="Post" />
                        <x-admin.th label="Author" />
                        <x-admin.th label="Tags" />
                        <x-admin.th label="Publish" />
                        <x-admin.th label="Status" />
                        <x-admin.th label="Actions" align="right" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @forelse ($posts as $post)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3">
                                <input type="checkbox" name="ids[]" value="{{ $post->id }}"
                                       x-model="selected" aria-label="Select blog post {{ $post->title }}">
                            </td>
                            <td class="p-3">
                                <div class="flex items-center gap-3">
                                    @if ($post->featured_image)
                                        <img src="{{ \App\Support\Media::url($post->featured_image, \App\Support\Media::WIDE_PLACEHOLDER) }}"
                                             alt="" class="w-16 h-10 object-cover rounded bg-ivory-alt">
                                    @endif
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.blog.edit', $post) }}" class="font-medium text-champagne-dark hover:underline">{{ $post->title }}</a>
                                        <span class="block text-xs text-muted">/blog/{{ $post->slug }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3 text-charcoal-soft">{{ $post->author?->name ?? '—' }}</td>
                            <td class="p-3 text-xs">
                                @forelse ($post->tags as $tag)
                                    <span class="badge bg-ivory-alt text-charcoal-soft">{{ $tag->name }}</span>
                                @empty
                                    <span class="text-muted">—</span>
                                @endforelse
                            </td>
                            <td class="p-3 text-xs text-muted whitespace-nowrap">
                                {{ $post->published_at?->format('M j, Y') ?? '—' }}
                            </td>
                            <td class="p-3"><x-admin.status-pill :status="$post->status" /></td>
                            <td class="p-3 text-right whitespace-nowrap">
                                <a href="{{ route('admin.blog.preview', $post) }}" class="text-xs text-champagne-dark hover:underline">Preview</a>
                                <a href="{{ route('admin.blog.edit', $post) }}" class="text-xs text-charcoal-soft hover:underline ml-2">Edit</a>
                                @can('blog.delete')
                                    <x-admin.confirm :action="route('admin.blog.destroy', $post)"
                                                     method="DELETE"
                                                     title="Delete this post?"
                                                     message="The post is removed from the blog immediately."
                                                     label="Delete"
                                                     confirm-label="Delete post" />
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <x-admin.empty icon="file-text" title="No blog posts"
                                               description="Share jewellery care, styling guides and campaign stories.">
                                    @can('blog.create')
                                        <x-slot:action>
                                            <x-admin.button :href="route('admin.blog.create')" size="sm" icon="plus">New Post</x-admin.button>
                                        </x-slot:action>
                                    @endcan
                                </x-admin.empty>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-admin.table>

            @if ($posts->total() > 0)
                <div class="px-4 border-t border-charcoal/5">
                    <x-admin.pagination :paginator="$posts" />
                </div>
            @endif
        </x-admin.card>
    </form>
@endsection
