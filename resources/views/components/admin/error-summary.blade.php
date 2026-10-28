@if ($errors->any())
    <div role="alert" tabindex="-1" x-data x-init="$el.focus()"
         {{ $attributes->merge(['class' => 'alert alert-danger mb-5']) }}>
        <x-admin.icon name="alert-circle" :size="20" class="mt-0.5" />

        <div class="min-w-0">
            <p class="font-medium">
                Please fix {{ $errors->count() }} {{ \Illuminate\Support\Str::plural('error', $errors->count()) }} before continuing.
            </p>
            <ul class="mt-1.5 space-y-0.5 text-sm list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
