@extends('lti.layout')

@section('title', 'Inhalt auswählen')

@section('content')
<div class="min-h-screen bg-zinc-50 p-6 lg:p-10">
    <div class="max-w-4xl mx-auto">
        <header class="mb-8">
            <h1 class="text-2xl font-bold text-zinc-900">Inhalt auswählen</h1>
            <p class="text-zinc-500 mt-1">Wähle ein Thema oder eine Lektion aus.</p>
        </header>

        <div class="space-y-6">
            @forelse($topics as $topic)
                <div class="bg-white rounded-xl border border-zinc-200 overflow-hidden">
                    <div class="p-5 border-b border-zinc-100">
                        <h2 class="text-lg font-semibold text-zinc-900">{{ $topic->title }}</h2>
                    </div>

                    <div class="divide-y divide-zinc-100">
                        @foreach($topic->chapters as $chapter)
                            @foreach($chapter->sections as $section)
                                @php
                                    $url = route('lti.embed.section', [
                                        'topic' => $topic->slug,
                                        'section' => $section->slug,
                                        'lti_session' => $session->session_token
                                    ]);
                                @endphp
                                <a
                                    href="{{ $url }}"
                                    class="flex items-center gap-4 px-5 py-4 hover:bg-zinc-50 transition-colors"
                                >
                                    <div class="w-8 h-8 rounded-lg bg-zinc-100 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium text-zinc-900 truncate">{{ $section->title }}</div>
                                        <div class="text-xs text-zinc-500">{{ $chapter->title }}</div>
                                    </div>
                                    <svg class="w-5 h-5 text-zinc-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            @endforeach
                        @endforeach
                    </div>

                    {{-- Chat option --}}
                    @php
                        $chatUrl = route('lti.embed.chat', [
                            'topic' => $topic->slug,
                            'lti_session' => $session->session_token
                        ]);
                    @endphp
                    <a
                        href="{{ $chatUrl }}"
                        class="flex items-center gap-4 px-5 py-4 bg-zinc-900 text-white hover:bg-zinc-800 transition-colors"
                    >
                        <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium">KI-Assistent</div>
                            <div class="text-xs text-white/70">Stelle Fragen zu diesem Thema</div>
                        </div>
                        <svg class="w-5 h-5 text-white/50 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            @empty
                <div class="bg-white rounded-xl border border-zinc-200 p-10 text-center">
                    <p class="text-zinc-500">Keine Themen verfügbar.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
