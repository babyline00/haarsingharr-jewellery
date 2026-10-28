<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Services\AuditService;
use App\Support\ImageCompressor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BlogPostController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['status', 'q', 'tag']);

        $posts = BlogPost::with(['author', 'tags'])
            ->when($filters['status'] ?? '', fn ($q, $s) => $q->where('status', $s))
            ->when($filters['q'] ?? '', fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('title', 'like', "%{$term}%")
                ->orWhere('content', 'like', "%{$term}%")))
            ->when($filters['tag'] ?? '', fn ($q, $tagId) => $q
                ->whereHas('tags', fn ($q) => $q->where('blog_tags.id', $tagId)))
            ->orderByRaw(<<<'SQL'
                case status
                    when 'published' then 1
                    when 'scheduled' then 2
                    when 'draft' then 3
                    else 4
                end
            SQL)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.blog.index', [
            'posts'   => $posts,
            'filters' => $filters,
            'counts'  => [
                'all'       => BlogPost::count(),
                'published' => BlogPost::where('status', 'published')->count(),
                'scheduled' => BlogPost::where('status', 'scheduled')->count(),
                'draft'     => BlogPost::where('status', 'draft')->count(),
                'archived'  => BlogPost::where('status', 'archived')->count(),
            ],
            'tags'    => BlogTag::withCount('posts')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.blog.create', [
            'post'          => null,
            'statusOptions' => ['draft', 'published', 'scheduled', 'archived'],
            'allTags'       => BlogTag::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePost($request);
        $validated['featured_image'] = $this->resolveFeaturedImage($request, null, $validated['featured_image'] ?? null);
        unset($validated['featured_image_file']);

        $post = BlogPost::create($this->finalise($validated, $request));
        $this->syncTags($post, (string) $request->input('tags'));
        $this->audit->record('blog.create', $post);

        return redirect()->route('admin.blog.edit', $post)->with('success', 'Blog post created.');
    }

    public function edit(BlogPost $blog_post): View
    {
        return view('admin.blog.edit', [
            'post'          => $blog_post,
            'statusOptions' => ['draft', 'published', 'scheduled', 'archived'],
            'allTags'       => BlogTag::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, BlogPost $blog_post): RedirectResponse
    {
        $validated = $this->validatePost($request, $blog_post);
        $validated['featured_image'] = $this->resolveFeaturedImage($request, $blog_post, $validated['featured_image'] ?? null);
        unset($validated['featured_image_file']);

        $blog_post->update($this->finalise($validated, $request, $blog_post));
        $this->syncTags($blog_post, (string) $request->input('tags'));
        $this->audit->record('blog.update', $blog_post);

        return back()->with('success', 'Blog post updated.');
    }

    public function destroy(BlogPost $blog_post): RedirectResponse
    {
        $this->audit->record('blog.delete', $blog_post);
        $blog_post->delete();

        return redirect()->route('admin.blog.index')->with('success', 'Blog post deleted.');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids'    => ['required', 'array', 'min:1', 'max:200'],
            'ids.*'  => ['integer'],
            'action' => ['required', 'in:publish,schedule_confirm,draft,archive,delete'],
        ]);

        $requiresPermission = $validated['action'] === 'delete'
            ? 'blog.delete'
            : 'blog.update';

        if (! $request->user()->can($requiresPermission)) {
            abort(403);
        }

        $posts = BlogPost::whereIn('id', $validated['ids'])->get();
        $changed = 0;

        foreach ($posts as $post) {
            match ($validated['action']) {
                'publish' => (function () use ($post, &$changed) {
                    $post->update(['status' => 'published', 'published_at' => $post->published_at ?? now()]);
                    $changed++;
                })(),
                'schedule_confirm' => (function () use ($post, &$changed) {
                    // Only meaningful when a future publish date is set.
                    if ($post->published_at && $post->published_at->isFuture()) {
                        $post->update(['status' => 'scheduled']);
                        $changed++;
                    }
                })(),
                'draft' => (function () use ($post, &$changed) {
                    $post->update(['status' => 'draft']);
                    $changed++;
                })(),
                'archive' => (function () use ($post, &$changed) {
                    $post->update(['status' => 'archived']);
                    $changed++;
                })(),
                'delete' => (function () use ($post, &$changed) {
                    $this->audit->record('blog.bulk.delete', $post);
                    $post->delete();
                    $changed++;
                })(),
            };
        }

        $this->audit->record('blog.bulk.update', null, null, [
            'action' => $validated['action'],
            'ids'    => $validated['ids'],
        ]);

        return back()->with('success', $changed.' post(s) updated.');
    }

    /** Render the storefront view for this post (drafts included) for staff. */
    public function preview(Request $request, BlogPost $blog_post): View
    {
        return view('storefront.blog.show', [
            'post'       => $blog_post,
            'isPreview'  => true,
            'related'    => BlogPost::published()
                ->where('id', '!=', $blog_post->id)
                ->whereHas('tags', fn ($q) => $q->whereIn('blog_tags.id', $blog_post->tags()->pluck('blog_tags.id')))
                ->limit(3)->get(),
        ]);
    }

    // -------------------------------------------------------------------------

    private function validatePost(Request $request, ?BlogPost $post = null): array
    {
        return $request->validate([
            'title'             => 'required|string|max:200',
            'slug'              => [
                'nullable', 'string', 'max:220',
                Rule::unique('blog_posts', 'slug')->ignore($post?->id),
            ],
            'excerpt'           => 'nullable|string|max:500',
            'content'           => 'nullable|string',
            'featured_image'    => 'nullable|string|max:300',
            'featured_image_file' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:5120',
            'status'            => 'required|in:draft,published,scheduled,archived',
            'published_at'      => 'nullable|date',
            'seo_title'         => 'nullable|string|max:200',
            'seo_description'   => 'nullable|string|max:500',
            'tags'              => 'nullable|string|max:1000',
        ]);
    }

    /** Normalise status-vs-published_at consistency before persisting. */
    private function finalise(array $validated, Request $request, ?BlogPost $post = null): array
    {
        $validated['author_id'] = $validated['author_id']
            ?? $post?->author_id
            ?? auth()->id();

        $publishAt = isset($validated['published_at']) && $validated['published_at']
            ? \Illuminate\Support\Carbon::parse($validated['published_at'])
            : null;
        $validated['published_at'] = $publishAt;

        if ($validated['status'] === 'published' && $publishAt) {
            // Publishing with a future date is really a schedule.
            $validated['status'] = $publishAt->isFuture() ? 'scheduled' : 'published';
        }

        return $validated;
    }

    private function syncTags(BlogPost $post, string $csv): void
    {
        $ids = collect(array_map('trim', explode(',', $csv)))
            ->filter()
            ->map(fn (string $name) => BlogTag::resolve($name)->id)
            ->unique()
            ->values();

        $post->tags()->sync($ids->all());
    }

    private function resolveFeaturedImage(Request $request, ?BlogPost $post, ?string $current): ?string
    {
        if ($request->hasFile('featured_image_file')) {
            return ImageCompressor::store($request->file('featured_image_file'), 'blog');
        }

        return $current ?: $post?->featured_image;
    }
}
