@extends('lti.layout')

@section('title', 'Chat - ' . $topic->title)

@section('content')
<div class="flex flex-col" style="min-height: 500px;">
    {{-- Header --}}
    <header class="border-b border-zinc-100 bg-white px-4 py-3">
        <h1 class="text-lg font-semibold text-zinc-900">
            KI-Assistent: {{ $topic->title }}
        </h1>
        <p class="text-sm text-zinc-500">
            Stelle Fragen zum Thema und erhalte Antworten basierend auf dem Kursinhalt.
        </p>
    </header>

    {{-- Chat Container --}}
    <div id="chat-container" class="flex-1 flex flex-col bg-zinc-50">
        {{-- Messages --}}
        <div id="messages" class="flex-1 overflow-y-auto p-4 space-y-4">
            <div class="flex gap-3">
                <div class="w-8 h-8 rounded-full bg-zinc-900 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <div class="bg-white rounded-xl px-4 py-3 shadow-sm max-w-[80%]">
                    <p class="text-zinc-700">
                        Hallo! Ich bin dein KI-Assistent für dieses Thema. Stelle mir eine Frage und ich helfe dir gerne weiter.
                    </p>
                </div>
            </div>
        </div>

        {{-- Input --}}
        <div class="border-t border-zinc-200 bg-white p-4">
            <form id="chat-form" class="flex gap-3">
                <input
                    type="text"
                    id="chat-input"
                    name="message"
                    placeholder="Stelle eine Frage..."
                    class="flex-1 rounded-xl border border-zinc-200 px-4 py-3 text-sm focus:border-zinc-400 focus:outline-none focus:ring-0"
                    autocomplete="off"
                >
                <button
                    type="submit"
                    id="send-button"
                    class="rounded-xl bg-zinc-900 px-5 py-3 text-sm font-medium text-white transition hover:bg-zinc-800 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Senden
                </button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const chatForm = document.getElementById('chat-form');
    const chatInput = document.getElementById('chat-input');
    const messagesContainer = document.getElementById('messages');
    const sendButton = document.getElementById('send-button');
    const topicSlug = @json($topic->slug);
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function addMessage(content, isUser = false) {
        const messageDiv = document.createElement('div');
        messageDiv.className = 'flex gap-3' + (isUser ? ' justify-end' : '');

        if (isUser) {
            messageDiv.innerHTML = `
                <div class="bg-zinc-900 text-white rounded-xl px-4 py-3 max-w-[80%]">
                    <p>${escapeHtml(content)}</p>
                </div>
                <div class="w-8 h-8 rounded-full bg-zinc-200 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
            `;
        } else {
            messageDiv.innerHTML = `
                <div class="w-8 h-8 rounded-full bg-zinc-900 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <div class="bg-white rounded-xl px-4 py-3 shadow-sm max-w-[80%]">
                    <div class="text-zinc-700 prose prose-sm">${content}</div>
                </div>
            `;
        }

        messagesContainer.appendChild(messageDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function setLoading(loading) {
        sendButton.disabled = loading;
        chatInput.disabled = loading;
        sendButton.textContent = loading ? 'Lädt...' : 'Senden';
    }

    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const message = chatInput.value.trim();
        if (!message) return;

        addMessage(message, true);
        chatInput.value = '';
        setLoading(true);

        try {
            const response = await fetch(`/themen/${topicSlug}/chat`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ question: message }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Anfrage fehlgeschlagen');
            }

            addMessage(data.answer || 'Keine Antwort erhalten.');
        } catch (error) {
            addMessage('Es ist ein Fehler aufgetreten. Bitte versuche es erneut.');
            console.error('Chat error:', error);
        } finally {
            setLoading(false);
        }
    });
</script>
@endpush
@endsection
