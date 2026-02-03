@extends('lti.layout')

@section('title', 'Inhalt für Moodle auswählen')

@section('content')
<div class="bg-zinc-50">
    {{-- Header --}}
    <header class="bg-white border-b border-zinc-200 px-6 py-4">
        <h1 class="text-xl font-bold text-zinc-900">Inhalt für Moodle auswählen</h1>
        <p class="text-sm text-zinc-500 mt-1">Wähle ein Thema und dann ein Kapitel aus.</p>
    </header>

    <div class="flex flex-col lg:flex-row">
        {{-- Topics List (Left) --}}
        <div class="w-full lg:w-80 border-b lg:border-b-0 lg:border-r border-zinc-200 bg-white overflow-y-auto">
            <div class="p-4">
                <h2 class="text-xs font-semibold text-zinc-400 uppercase tracking-wider mb-3">Themen</h2>
                <div class="space-y-1">
                    @forelse($topics as $topic)
                        <button
                            type="button"
                            data-topic-id="{{ $topic->id }}"
                            class="topic-btn w-full flex items-center gap-3 px-4 py-3 rounded-xl text-left transition-all hover:bg-zinc-50 border border-transparent"
                        >
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-zinc-900 truncate">{{ $topic->title }}</div>
                                <div class="text-xs text-zinc-400">{{ $topic->chapters->count() }} Kapitel</div>
                            </div>
                            <svg class="w-5 h-5 text-zinc-300 chevron-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    @empty
                        <p class="text-zinc-400 text-sm px-4 py-3">Keine Themen verfügbar.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Chapters Panel (Right) --}}
        <div class="flex-1 p-6 overflow-y-auto">
            {{-- Empty State --}}
            <div id="empty-state" class="flex flex-col items-center justify-center py-12 text-center">
                <div class="w-16 h-16 rounded-full bg-zinc-100 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-zinc-900 mb-1">Thema auswählen</h3>
                <p class="text-sm text-zinc-500">Wähle links ein Thema aus, um die Kapitel zu sehen.</p>
            </div>

            {{-- Chapter Lists (hidden by default) --}}
            @foreach($topics as $topic)
                <div id="chapters-{{ $topic->id }}" class="chapters-panel hidden">
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-zinc-900">{{ $topic->title }}</h2>
                        <p class="text-zinc-500 mt-1">Wähle ein Kapitel zum Einbetten aus.</p>
                    </div>

                    <div class="grid gap-3">
                        @foreach($topic->chapters as $chapter)
                            <form action="{{ route('lti.deep-linking.return') }}" method="POST" class="chapter-form">
                                @csrf
                                <input type="hidden" name="lti_session" value="{{ $session->session_token }}">
                                <input type="hidden" name="selected[0][type]" value="chapter">
                                <input type="hidden" name="selected[0][topic_slug]" value="{{ $topic->slug }}">
                                <input type="hidden" name="selected[0][chapter_slug]" value="{{ $chapter->slug }}">
                                <input type="hidden" name="selected[0][title]" value="{{ $chapter->title }} ({{ $topic->title }})">

                                <button
                                    type="submit"
                                    class="w-full flex items-center gap-4 p-5 rounded-xl bg-white border border-zinc-200 text-left transition-all hover:border-zinc-900 hover:shadow-lg group"
                                >
                                    <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-zinc-100 text-zinc-600 font-bold group-hover:bg-zinc-900 group-hover:text-white transition-colors">
                                        {{ $loop->iteration }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-semibold text-zinc-900">{{ $chapter->title }}</div>
                                        <div class="text-sm text-zinc-500">{{ $chapter->sections->count() }} Abschnitte</div>
                                    </div>
                                    <div class="flex items-center gap-2 text-sm font-medium text-zinc-400 group-hover:text-zinc-900 transition-colors">
                                        <span>Einbetten</span>
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                    </div>
                                </button>
                            </form>
                        @endforeach

                        {{-- AI Chat Option --}}
                        <form action="{{ route('lti.deep-linking.return') }}" method="POST" class="chapter-form mt-4">
                            @csrf
                            <input type="hidden" name="lti_session" value="{{ $session->session_token }}">
                            <input type="hidden" name="selected[0][type]" value="chat">
                            <input type="hidden" name="selected[0][topic_slug]" value="{{ $topic->slug }}">
                            <input type="hidden" name="selected[0][title]" value="KI-Assistent: {{ $topic->title }}">

                            <button
                                type="submit"
                                class="w-full flex items-center gap-4 p-5 rounded-xl bg-zinc-900 text-white text-left transition-all hover:bg-zinc-800 group"
                            >
                                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-white/10">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold">KI-Assistent</div>
                                    <div class="text-sm text-white/70">Interaktiver Chat zu diesem Thema</div>
                                </div>
                                <div class="flex items-center gap-2 text-sm font-medium text-white/70 group-hover:text-white transition-colors">
                                    <span>Einbetten</span>
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                </div>
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

@push('scripts')
<script>
    const topicButtons = document.querySelectorAll('.topic-btn');
    const chapterPanels = document.querySelectorAll('.chapters-panel');
    const emptyState = document.getElementById('empty-state');

    topicButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const topicId = this.getAttribute('data-topic-id');

            // Update button states
            topicButtons.forEach(b => {
                b.classList.remove('bg-zinc-900', 'border-zinc-900');
                b.classList.add('hover:bg-zinc-50', 'border-transparent');
                b.querySelector('.font-medium').classList.remove('text-white');
                b.querySelector('.font-medium').classList.add('text-zinc-900');
                b.querySelector('.text-xs').classList.remove('text-zinc-300');
                b.querySelector('.text-xs').classList.add('text-zinc-400');
                b.querySelector('.chevron-icon').classList.remove('text-white');
                b.querySelector('.chevron-icon').classList.add('text-zinc-300');
            });

            this.classList.add('bg-zinc-900', 'border-zinc-900');
            this.classList.remove('hover:bg-zinc-50', 'border-transparent');
            this.querySelector('.font-medium').classList.add('text-white');
            this.querySelector('.font-medium').classList.remove('text-zinc-900');
            this.querySelector('.text-xs').classList.add('text-zinc-300');
            this.querySelector('.text-xs').classList.remove('text-zinc-400');
            this.querySelector('.chevron-icon').classList.add('text-white');
            this.querySelector('.chevron-icon').classList.remove('text-zinc-300');

            // Show chapters
            emptyState.classList.add('hidden');
            chapterPanels.forEach(panel => panel.classList.add('hidden'));
            document.getElementById('chapters-' + topicId).classList.remove('hidden');

            // Update iframe height after content change
            if (typeof window.sendHeight === 'function') {
                setTimeout(function() { window.sendHeight(true); }, 50);
            }
        });
    });
</script>
@endpush
@endsection
