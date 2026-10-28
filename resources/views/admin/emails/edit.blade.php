@extends('admin.layout')

@section('title', 'Template: '.$template->name.' — Haarsingharr Admin')

@section('content')
    <x-admin.header :title="$template->name"
                    :subtitle="'Event key: '.$template->key.' — placeholders per the variables palette are replaced with order/customer data at send time.'">
        <x-slot:actions>
            <a href="{{ route('admin.emails.preview', $template) }}" target="_blank" rel="noopener" class="btn-outline btn-sm">Preview</a>
            <a href="{{ route('admin.emails.index') }}" class="btn-ghost btn-sm">Back</a>
        </x-slot:actions>
    </x-admin.header>

    <div class="grid lg:grid-cols-[1fr_300px] gap-6">
        <x-admin.card>
            <form action="{{ route('admin.emails.update', $template) }}" method="post">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    <x-admin.field name="subject" label="Subject *"
                                   hint="Tokens like &#123;&#123;customer_name&#125;&#125; are replaced at send time.">
                        <x-admin.input id="subject" name="subject" required
                                       :value="old('subject', $template->subject)" />
                    </x-admin.field>

                    <x-admin.field name="variables" label="Variables (comma separated)"
                                   hint="Used for the palette, the sample preview and validation depth.">
                        <x-admin.input id="variables" name="variables"
                                       :value="old('variables', collect($template->variables ?? [])->map(fn ($v) => is_array($v) ? ($v['name'] ?? '') : $v)->implode(', '))" />
                    </x-admin.field>

                    <x-admin.field name="body" label="Body *"
                                   hint="HTML supported. Plain paragraphs render as-is in preview emails.">
                        <textarea id="body" name="body" rows="18" required
                                  class="font-mono text-xs leading-relaxed">{{ old('body', $template->body) }}</textarea>
                    </x-admin.field>
                </div>

                <div class="flex items-center gap-4 mt-5">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $template->is_active))>
                        Template enabled
                    </label>

                    <div class="flex gap-2 ml-auto">
                        @can('email_templates.update')
                            <form action="{{ route('admin.emails.test-send', $template) }}" method="post" class="inline">
                                @csrf
                                <x-admin.button type="submit" variant="outline" icon="send">
                                    Send test to me
                                </x-admin.button>
                            </form>
                        @endcan
                        <x-admin.button type="submit" variant="gold" icon="save">Save template</x-admin.button>
                    </div>
                </div>
            </form>
        </x-admin.card>

        <aside class="space-y-4">
            <x-admin.card>
                <h2 class="font-serif text-base mb-3">Variable palette</h2>
                <p class="text-xs text-muted mb-3">Click to send yourself a rendered sample using these values.</p>
                <dl class="text-xs space-y-1.5">
                    @foreach ($sample as $name => $value)
                        <div class="flex justify-between gap-3 border-b border-charcoal/5 pb-1.5">
                            <dt class="font-mono text-muted">&#123;&#123;{{ $name }}&#125;&#125;</dt>
                            <dd class="text-charcoal-soft text-right">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </x-admin.card>

            <x-admin.card>
                <h2 class="font-serif text-base mb-3">Rendered preview</h2>
                <div class="text-xs text-muted mb-1">Subject</div>
                <p class="text-sm font-medium mb-3">{{ $previewRenders['subject'] }}</p>
                <div class="text-xs text-muted mb-1">Body</div>
                <div class="card max-h-[320px] overflow-auto whitespace-pre-wrap text-xs font-serif leading-relaxed bg-ivory-alt/30">{!! $previewRenders['body'] !!}</div>
            </x-admin.card>

            <x-admin.card>
                <h2 class="font-serif text-base mb-2">Enable / disable</h2>
                <p class="text-xs text-muted mb-3">
                    Disabled templates skip their event entirely — no email is sent for that action.
                </p>
                <form action="{{ route('admin.emails.toggle', $template) }}" method="post">
                    @csrf
                    <x-admin.button type="submit" variant="outline" size="sm" icon="power">
                        {{ $template->is_active ? 'Disable template' : 'Enable template' }}
                    </x-admin.button>
                </form>
            </x-admin.card>
        </aside>
    </div>
@endsection
