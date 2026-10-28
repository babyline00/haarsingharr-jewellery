@extends('admin.layout')

@section('title', 'Edit: '.$post->title.' — Haarsingharr Admin')

@section('content')
    <x-admin.header :title="$post->title ?: 'Blog post'"
                    :subtitle="'/blog/'.$post->slug.' — last updated '.$post->updated_at?->format('d M Y H:i')">
        <x-slot:actions>
            <a href="{{ route('admin.blog.index') }}" class="btn-ghost btn-sm">Back</a>
        </x-slot:actions>
    </x-admin.header>

    @include('admin.blog._form', ['statusOptions' => $statusOptions, 'allTags' => $allTags])
@endsection
