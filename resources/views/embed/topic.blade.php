@extends('embed.layout')

@section('title', $topic->title)

@section('content')
<div class="flex flex-col lg:flex-row">
    {{-- Sidebar Navigation --}}
    <aside class="w-full lg:w-72 flex-shrink-0 border-b lg:border-b-0 lg:border-r border-zinc-100 bg-white">
        <div class="p-4 lg:p-6">
            <div class="mb-6 pb-4 border-b border-zinc-100">
                <h1 class="text-lg font-semibold text-zinc-900">{{ $topic->title }}</h1>
            </div>

            <nav class="space-y-4">
                @foreach($topic->chapters as $chapter)
                    <div>
                        <h3 class="text-xs font-semibold text-zinc-400 uppercase tracking-wider mb-2">
                            {{ $chapter->title }}
                        </h3>
                        <div class="space-y-1">
                            @foreach($chapter->sections as $section)
                                @php
                                    $isActive = $activeSection && $section->id === $activeSection->id;
                                    $url = route('embed.chapter.section', [
                                        'chapter' => $chapter->id,
                                        'section' => $section->id
                                    ]);
                                @endphp
                                <a
                                    href="{{ $url }}"
                                    class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ $isActive ? 'bg-zinc-900 text-white' : 'text-zinc-500 hover:bg-zinc-50 hover:text-zinc-900' }}"
                                >
                                    <span class="truncate">{{ $section->title }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </nav>
        </div>
    </aside>

    {{-- Main Content --}}
    <main class="flex-1 p-6 lg:p-10">
        @if($activeSection)
            <header class="mb-8 flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm text-zinc-400 mb-1">{{ $activeSection->chapter->title ?? '' }}</p>
                    <h2 class="text-3xl lg:text-4xl font-bold tracking-tight text-zinc-900">
                        {{ $activeSection->title }}
                    </h2>
                </div>
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

            <article class="prose prose-zinc max-w-none">
                @include('lti.partials.content-blocks', ['blocks' => $activeSection->content['blocks'] ?? []])
            </article>
        @else
            <div class="flex flex-col items-center justify-center h-full text-zinc-400">
                <p>Dieses Thema hat noch keine Inhalte.</p>
            </div>
        @endif
    </main>
</div>
@endsection
