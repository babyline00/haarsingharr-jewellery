@extends('admin.layout')

@section('title', 'New Blog Post — Haarsingharr Admin')

@section('content')
    <x-admin.header title="New blog post"
                    subtitle="Write once, publish when ready — or schedule the story for a launch date.">
        <x-slot:actions>
            <a href="{{ route('admin.blog.index') }}" class="btn-ghost btn-sm">Back</a>
        </x-slot:actions>
    </x-admin.header>

    @include('admin.blog._form', ['statusOptions' => $statusOptions, 'allTags' => $allTags])
@endsection
