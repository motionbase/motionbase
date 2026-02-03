@extends('lti.layout')

@section('title', 'Inhalt für Moodle auswählen')

@section('content')
<div class="min-h-screen bg-zinc-50 p-6 lg:p-10">
    <div class="max-w-4xl mx-auto">
        <header class="mb-8">
            <h1 class="text-2xl font-bold text-zinc-900">Inhalt für Moodle auswählen</h1>
            <p class="text-zinc-500 mt-1">Wähle die Inhalte aus, die du in Moodle einbetten möchtest.</p>
        </header>

        <form id="deep-linking-form" action="{{ route('lti.deep-linking.return') }}" method="POST">
            @csrf
            <input type="hidden" name="lti_session" value="{{ $session->session_token }}">

            <div class="space-y-6 mb-8">
                @forelse($topics as $topic)
                    <div class="bg-white rounded-xl border border-zinc-200 overflow-hidden">
                        <div class="p-5 border-b border-zinc-100 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-zinc-900">{{ $topic->title }}</h2>

                            {{-- Select entire topic --}}
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="items[]"
                                    value="{{ json_encode([
                                        'type' => 'topic',
                                        'topic_slug' => $topic->slug,
                                        'title' => $topic->title,
                                    ]) }}"
                                    class="w-4 h-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500"
                                >
                                <span class="text-sm text-zinc-600">Ganzes Thema</span>
                            </label>
                        </div>

                        <div class="divide-y divide-zinc-100">
                            @foreach($topic->chapters as $chapter)
                                @foreach($chapter->sections as $section)
                                    <label class="flex items-center gap-4 px-5 py-4 hover:bg-zinc-50 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            name="items[]"
                                            value="{{ json_encode([
                                                'type' => 'section',
                                                'topic_slug' => $topic->slug,
                                                'section_slug' => $section->slug,
                                                'title' => $section->title . ' (' . $topic->title . ')',
                                            ]) }}"
                                            class="w-4 h-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500"
                                        >
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-medium text-zinc-900">{{ $section->title }}</div>
                                            <div class="text-xs text-zinc-500">{{ $chapter->title }}</div>
                                        </div>
                                    </label>
                                @endforeach
                            @endforeach

                            {{-- Chat option --}}
                            <label class="flex items-center gap-4 px-5 py-4 bg-zinc-900 text-white hover:bg-zinc-800 cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="items[]"
                                    value="{{ json_encode([
                                        'type' => 'chat',
                                        'topic_slug' => $topic->slug,
                                        'title' => 'KI-Assistent: ' . $topic->title,
                                    ]) }}"
                                    class="w-4 h-4 rounded border-white/30 text-white bg-white/10 focus:ring-white"
                                >
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium">KI-Assistent</div>
                                    <div class="text-xs text-white/70">Interaktiver Chat zu diesem Thema</div>
                                </div>
                            </label>
                        </div>
                    </div>
                @empty
                    <div class="bg-white rounded-xl border border-zinc-200 p-10 text-center">
                        <p class="text-zinc-500">Keine Themen verfügbar.</p>
                    </div>
                @endforelse
            </div>

            <div class="flex items-center justify-between bg-white rounded-xl border border-zinc-200 p-4">
                <div id="selection-count" class="text-sm text-zinc-500">
                    Keine Inhalte ausgewählt
                </div>
                <button
                    type="submit"
                    id="submit-button"
                    disabled
                    class="rounded-xl bg-zinc-900 px-6 py-3 text-sm font-medium text-white transition hover:bg-zinc-800 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Auswahl zu Moodle hinzufügen
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    const form = document.getElementById('deep-linking-form');
    const submitButton = document.getElementById('submit-button');
    const selectionCount = document.getElementById('selection-count');
    const checkboxes = form.querySelectorAll('input[type="checkbox"]');

    function updateSelectionCount() {
        const checked = form.querySelectorAll('input[type="checkbox"]:checked');
        const count = checked.length;

        if (count === 0) {
            selectionCount.textContent = 'Keine Inhalte ausgewählt';
            submitButton.disabled = true;
        } else if (count === 1) {
            selectionCount.textContent = '1 Inhalt ausgewählt';
            submitButton.disabled = false;
        } else {
            selectionCount.textContent = `${count} Inhalte ausgewählt`;
            submitButton.disabled = false;
        }
    }

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectionCount);
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const checkedItems = Array.from(form.querySelectorAll('input[name="items[]"]:checked'))
            .map(input => JSON.parse(input.value));

        if (checkedItems.length === 0) {
            return;
        }

        // Create a hidden form to submit
        const submitForm = document.createElement('form');
        submitForm.method = 'POST';
        submitForm.action = form.action;

        // Add CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = document.querySelector('input[name="_token"]').value;
        submitForm.appendChild(csrfInput);

        // Add session token
        const sessionInput = document.createElement('input');
        sessionInput.type = 'hidden';
        sessionInput.name = 'lti_session';
        sessionInput.value = document.querySelector('input[name="lti_session"]').value;
        submitForm.appendChild(sessionInput);

        // Add selected items
        checkedItems.forEach((item, index) => {
            Object.keys(item).forEach(key => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `selected[${index}][${key}]`;
                input.value = item[key] || '';
                submitForm.appendChild(input);
            });
        });

        document.body.appendChild(submitForm);
        submitForm.submit();
    });
</script>
@endpush
@endsection
