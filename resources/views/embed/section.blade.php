@extends('embed.layout')

@section('title', $section->title . ' - ' . $topic->title)

@section('content')
<div class="p-6 lg:p-10">
    {{-- Breadcrumb --}}
    <div class="mb-4 text-sm text-zinc-400">
        <span>{{ $topic->title }}</span>
        <span class="mx-2">›</span>
        <span>{{ $chapter->title }}</span>
    </div>

    {{-- Header --}}
    <header class="mb-8 flex items-start justify-between gap-4">
        <h1 class="text-3xl lg:text-4xl font-bold tracking-tight text-zinc-900">
            {{ $section->title }}
        </h1>
        <a
            href="{{ url()->current() }}"
            target="_blank"
            class="flex-shrink-0 flex items-center gap-2 px-3 py-2 text-xs font-medium text-zinc-500 hover:text-zinc-900 hover:bg-zinc-100 rounded-lg transition-colors"
            title="In neuem Tab öffnen"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
            </svg>
            <span class="hidden sm:inline">Vollbild</span>
        </a>
    </header>

    {{-- Content --}}
    <article class="prose prose-zinc max-w-none">
        @include('lti.partials.content-blocks', ['blocks' => $section->content['blocks'] ?? []])
    </article>
</div>
@endsection
