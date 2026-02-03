@extends('lti.layout')

@section('title', $topic->title . ' - ' . ($activeSection->title ?? 'Inhalt'))

@section('content')
<div class="flex flex-col lg:flex-row">
    {{-- Sidebar Navigation --}}
    <aside class="w-full lg:w-72 border-b lg:border-b-0 lg:border-r border-zinc-100 bg-white p-4 lg:p-6">
        <div class="mb-6 pb-4 border-b border-zinc-100">
            <h1 class="text-lg font-semibold text-zinc-900">{{ $topic->title }}</h1>
        </div>

        <nav class="space-y-2">
            @foreach($topic->chapters as $chapter)
                <div class="space-y-1">
                    <div class="text-sm font-semibold text-zinc-700 px-3 py-2">
                        {{ $loop->iteration }}. {{ $chapter->title }}
                    </div>
                    <div class="ml-3 pl-3 border-l border-zinc-100 space-y-1">
                        @foreach($chapter->sections as $section)
                            @php
                                $isActive = $activeSection && $section->id === $activeSection->id;
                                $url = route('lti.embed.section', [
                                    'topic' => $topic->slug,
                                    'section' => $section->slug,
                                    'lti_session' => $session->session_token
                                ]);
                            @endphp
                            <a
                                href="{{ $url }}"
                                class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ $isActive ? 'bg-zinc-900 text-white' : 'text-zinc-500 hover:bg-zinc-50 hover:text-zinc-900' }}"
                            >
                                <span class="flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-bold {{ $isActive ? 'bg-white/20 text-white' : 'bg-zinc-100 text-zinc-500' }}">
                                    {{ $loop->iteration }}
                                </span>
                                <span class="truncate">{{ $section->title }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>
    </aside>

    {{-- Main Content --}}
    <main class="flex-1 p-6 lg:p-10">
        @if($activeSection)
            <header class="mb-8">
                <h2 class="text-3xl lg:text-4xl font-bold tracking-tight text-zinc-900">
                    {{ $activeSection->title }}
                </h2>
            </header>

            <article class="prose prose-zinc max-w-none">
                @include('lti.partials.content-blocks', ['blocks' => $activeSection->content['blocks'] ?? []])
            </article>
        @else
            <div class="flex flex-col items-center justify-center h-full text-zinc-400">
                <p>Kein Inhalt ausgewählt.</p>
            </div>
        @endif
    </main>
</div>
@endsection
