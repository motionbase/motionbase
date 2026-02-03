@extends('lti.layout')

@section('title', ($activeSection->title ?? $chapter->title) . ' - ' . $topic->title)

@section('content')
<div class="flex flex-col lg:flex-row min-h-screen">
    {{-- Sidebar Navigation (sticky) --}}
    <aside class="w-full lg:w-72 flex-shrink-0 border-b lg:border-b-0 lg:border-r border-zinc-100 bg-white">
        <div class="lg:sticky lg:top-0 lg:h-screen lg:overflow-y-auto p-4 lg:p-6">
            <div class="mb-6 pb-4 border-b border-zinc-100">
                <p class="text-xs text-zinc-400 mb-1">{{ $topic->title }}</p>
                <h1 class="text-lg font-semibold text-zinc-900">{{ $chapter->title }}</h1>
            </div>

            <nav class="space-y-1">
                @foreach($chapter->sections as $section)
                    @php
                        $isActive = $activeSection && $section->id === $activeSection->id;
                        $url = route('lti.embed.chapter.section', [
                            'topic' => $topic->slug,
                            'chapter' => $chapter->slug,
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
            </nav>
        </div>
    </aside>

    {{-- Main Content - Single section --}}
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

            {{-- Previous / Next Navigation --}}
            <nav class="mt-12 pt-8 border-t border-zinc-200 flex items-center justify-between gap-4">
                @if(isset($prevSection) && $prevSection)
                    <a
                        href="{{ route('lti.embed.chapter.section', ['topic' => $topic->slug, 'chapter' => $chapter->slug, 'section' => $prevSection->slug, 'lti_session' => $session->session_token]) }}"
                        class="flex items-center gap-2 px-4 py-3 rounded-xl border border-zinc-200 hover:border-zinc-900 hover:bg-zinc-50 transition-all group"
                    >
                        <svg class="w-5 h-5 text-zinc-400 group-hover:text-zinc-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        <div class="text-left">
                            <div class="text-xs text-zinc-400">Zurück</div>
                            <div class="text-sm font-medium text-zinc-900 truncate max-w-[150px]">{{ $prevSection->title }}</div>
                        </div>
                    </a>
                @else
                    <div></div>
                @endif

                @if(isset($nextSection) && $nextSection)
                    <a
                        href="{{ route('lti.embed.chapter.section', ['topic' => $topic->slug, 'chapter' => $chapter->slug, 'section' => $nextSection->slug, 'lti_session' => $session->session_token]) }}"
                        class="flex items-center gap-2 px-4 py-3 rounded-xl border border-zinc-200 hover:border-zinc-900 hover:bg-zinc-50 transition-all group"
                    >
                        <div class="text-right">
                            <div class="text-xs text-zinc-400">Weiter</div>
                            <div class="text-sm font-medium text-zinc-900 truncate max-w-[150px]">{{ $nextSection->title }}</div>
                        </div>
                        <svg class="w-5 h-5 text-zinc-400 group-hover:text-zinc-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                @else
                    <div></div>
                @endif
            </nav>
        @else
            <div class="flex flex-col items-center justify-center h-full text-zinc-400">
                <p>Dieses Kapitel hat noch keine Abschnitte.</p>
            </div>
        @endif
    </main>
</div>
@endsection
