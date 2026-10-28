<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\BlogTag;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(Request $request): View
    {
        $posts = BlogPost::published()
            ->with(['tags', 'author'])
            ->when($request->input('tag'), fn ($q, $tagSlug) => $q
                ->whereHas('tags', fn ($q) => $q->where('blog_tags.slug', $tagSlug)))
            ->latest('published_at')
            ->orderByDesc('id')
            ->paginate(9);

        $tag = null;
        if ($request->input('tag')) {
            $tag = BlogTag::where('slug', $request->input('tag'))->first();
            abort_unless($tag, 404);
        }

        return view('storefront.blog.index', [
            'posts'    => $posts,
            'tag'      => $tag,
            'tags'     => BlogTag::withCount(['posts' => fn ($q) => $q->published()])
                ->get()
                ->filter(fn ($t) => $t->posts_count > 0)
                ->sortBy('name')
                ->values(),
        ]);
    }

    public function show(BlogPost $post): View
    {
        // Published (and past release time) posts only; staff preview via admin route.
        abort_unless($post->status === 'published'
            && ($post->published_at === null || $post->published_at->isPast()), 404);

        $related = BlogPost::published()
            ->where('id', '!=', $post->id)
            ->whereHas('tags', fn ($q) => $q->whereIn('blog_tags.id', $post->tags()->pluck('blog_tags.id')))
            ->limit(3)
            ->get();

        return view('storefront.blog.show', [
            'post'    => $post->load(['tags', 'author']),
            'related' => $related,
            'isPreview' => false,
        ]);
    }
}
