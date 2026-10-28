@php
    $isEdit = isset($post);
    $formAction = $isEdit ? route('admin.blog.update', $post) : route('admin.blog.store');
    $method = $isEdit ? 'PUT' : 'POST';
@endphp

<form action="{{ $formAction }}" method="post" enctype="multipart/form-data">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="grid lg:grid-cols-[1fr_320px] gap-6">
        <div class="space-y-4">
            <x-admin.card>
                <div class="space-y-4">
                    <x-admin.field name="title" label="Title *">
                        <x-admin.input id="title" name="title" required :value="old('title', $post->title ?? '')"
                                       class="font-serif text-lg" />
                    </x-admin.field>

                    <x-admin.field name="slug" label="URL slug" hint="Leave blank to generate from the title.">
                        <x-admin.input id="slug" name="slug" :value="old('slug', $post->slug ?? '')"
                                       class="font-mono text-sm" />
                    </x-admin.field>

                    <x-admin.field name="excerpt" label="Excerpt" hint="Shown on the blog listing card.">
                        <textarea id="excerpt" name="excerpt" rows="2"
                                  placeholder="One or two sentences that make readers click">{{ old('excerpt', $post->excerpt ?? '') }}</textarea>
                    </x-admin.field>

                    <x-admin.field name="content" label="Content" hint="Paragraphs separated by blank lines; headings with ## ...">
                        <textarea id="content" name="content" rows="16"
                                  class="font-serif leading-relaxed"
                                  placeholder="Write the story of the piece...">{{ old('content', $post->content ?? '') }}</textarea>
                    </x-admin.field>
                </div>
            </x-admin.card>

            <x-admin.card>
                <h2 class="font-serif text-lg mb-3">SEO</h2>
                <div class="space-y-4">
                    <x-admin.field name="seo_title" label="SEO title" hint="Overrides the title in search results (max 200).">
                        <x-admin.input id="seo_title" name="seo_title" :value="old('seo_title', $post->seo_title ?? '')" />
                    </x-admin.field>
                    <x-admin.field name="seo_description" label="Meta description">
                        <textarea id="seo_description" name="seo_description" rows="2">{{ old('seo_description', $post->seo_description ?? '') }}</textarea>
                    </x-admin.field>
                </div>
            </x-admin.card>
        </div>

        <aside class="space-y-4">
            <x-admin.card>
                <h2 class="font-serif text-base mb-3">Publishing</h2>
                <div class="space-y-4">
                    <x-admin.field name="status" label="Status *">
                        <x-admin.select id="status" name="status" :selected="old('status', $post->status ?? 'draft')"
                                        :options="collect($statusOptions)->mapWithKeys(fn ($s) => [$s => ucfirst($s)])->all()" />
                    </x-admin.field>

                    <x-admin.field name="published_at" label="Publish date"
                                   hint="Future date + published status becomes a scheduled post.">
                        <x-admin.input id="published_at" name="published_at" type="datetime-local"
                                       :value="old('published_at', $post?->published_at?->format('Y-m-d\TH:i'))" />
                    </x-admin.field>

                    <x-admin.field name="tags" label="Tags"
                                   hint="Comma separated — new names are created automatically.">
                        <x-admin.input id="tags" name="tags" :value="old('tags', $post?->tags->pluck('name')->implode(', '))"
                                       placeholder="styling, bridal" />
                    </x-admin.field>
                </div>
            </x-admin.card>

            <x-admin.card>
                <h2 class="font-serif text-base mb-3">Cover image</h2>
                <div class="space-y-3">
                    @if (($post?->featured_image) || old('featured_image'))
                        <img src="{{ \App\Support\Media::url(old('featured_image', $post->featured_image), \App\Support\Media::WIDE_PLACEHOLDER) }}"
                             alt="Current cover" class="w-full max-h-36 object-cover rounded border border-charcoal/10">
                        <p class="text-xs text-muted">Uploading a new file replaces the image.</p>
                    @endif
                    <label for="featured_image_file">Upload cover (jpg/png/webp, ≤ 5&nbsp;MB)</label>
                    <input type="file" id="featured_image_file" name="featured_image_file" accept="image/*" class="block w-full text-sm">
                    @error('featured_image_file') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                    <x-admin.field name="featured_image" label="…or image path">
                        <x-admin.input id="featured_image" name="featured_image"
                                       :value="old('featured_image', $post->featured_image ?? '')" />
                    </x-admin.field>
                </div>
            </x-admin.card>

            <div class="flex gap-2">
                @if ($isEdit && auth()->user()->can('blog.view'))
                    <a href="{{ route('admin.blog.preview', $post) }}" target="_blank" rel="noopener" class="btn-outline btn-sm">Preview</a>
                @endif
                <div class="flex gap-2">
                    <x-admin.button type="submit" variant="gold" icon="save">
                        {{ $isEdit ? 'Save changes' : 'Create post' }}
                    </x-admin.button>
                    <a href="{{ route('admin.blog.index') }}" class="btn-ghost btn-sm">Cancel</a>
                </div>
            </div>
        </aside>
    </div>
</form>
