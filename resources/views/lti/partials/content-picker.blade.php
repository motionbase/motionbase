{{--
    Content picker for teachers - used in Moodle's "Inhalt auswählen" dialog
    and when a teacher opens an activity saved without content.

    One form with radio buttons rather than a form per row: the choice is
    visible before it is sent, can be changed, and works with the keyboard
    like any other form.
--}}
@php
    $lessons = fn (int $n) => $n === 1 ? '1 Lektion' : $n.' Lektionen';
@endphp

<form method="POST" action="{{ $action }}" class="mb-picker" id="mb-picker">
    <input type="hidden" name="lti_session" value="{{ $session->session_token }}">

    <header class="mb-picker__head">
        <h1 class="mb-picker__title">{{ $heading }}</h1>
        <p class="mb-picker__intro">{{ $intro }}</p>

        @if ($errors->has('choice'))
            <p class="mb-picker__error" role="alert">{{ $errors->first('choice') }}</p>
        @endif

        @if (count($catalog) > 0)
            <label class="mb-picker__search">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <span class="sr-only">Inhalte durchsuchen</span>
                <input type="search" id="mb-picker-search" placeholder="Suchen, z. B. „Easing“" autocomplete="off">
            </label>
        @endif
    </header>

    <div class="mb-picker__list">
        @forelse ($catalog as $topic)
            <section class="mb-topic" data-topic>
                <div class="mb-topic__head">
                    <h2 class="mb-topic__title">{{ $topic['title'] }}</h2>
                    <span class="mb-meta">
                        {{ $lessons($topic['lessons']) }}
                    </span>
                </div>

                <label class="mb-option mb-option--whole" data-option data-search="{{ mb_strtolower($topic['title']) }}">
                    <input type="radio" name="choice" value="topic:{{ $topic['id'] }}"
                        data-label="{{ $topic['title'] }} – ganzer Kurs"
                        data-note="{{ $lessons($topic['lessons']) }} · alle Kapitel, mit Navigation"
                        @checked(($current ?? null) === 'topic:'.$topic['id'])>
                    <span class="mb-radio" aria-hidden="true"></span>
                    <span class="mb-option__text">
                        <span class="mb-option__name">Ganzer Kurs</span>
                        <span class="mb-meta">Alle Kapitel, mit Navigation</span>
                    </span>
                </label>

                @foreach ($topic['chapters'] as $chapter)
                    <div class="mb-chapter" data-chapter>
                        <label class="mb-option" data-option data-search="{{ mb_strtolower($topic['title'].' '.$chapter['title']) }}">
                            <input type="radio" name="choice" value="chapter:{{ $chapter['id'] }}"
                                data-label="{{ $topic['title'] }} – {{ $chapter['title'] }}"
                                data-note="Kapitel · {{ $lessons($chapter['lessons']) }}"
                                @checked(($current ?? null) === 'chapter:'.$chapter['id'])>
                            <span class="mb-radio" aria-hidden="true"></span>
                            <span class="mb-option__text">
                                <span class="mb-option__name">{{ $chapter['title'] }}</span>
                                <span class="mb-meta">
                                    Kapitel · {{ $lessons($chapter['lessons']) }}
                                </span>
                            </span>
                        </label>

                        @if ($chapter['lessons'] > 1)
                            <details class="mb-lessons">
                                <summary>Nur eine Lektion daraus wählen</summary>
                                @foreach ($chapter['sections'] as $section)
                                    <label class="mb-option mb-option--lesson" data-option data-search="{{ mb_strtolower($topic['title'].' '.$chapter['title'].' '.$section['title']) }}">
                                        <input type="radio" name="choice" value="section:{{ $section['id'] }}"
                                            data-label="{{ $topic['title'] }} – {{ $section['title'] }}"
                                            data-note="Eine einzelne Lektion, ohne den Rest des Kapitels"
                                            @checked(($current ?? null) === 'section:'.$section['id'])>
                                        <span class="mb-radio" aria-hidden="true"></span>
                                        <span class="mb-option__text">
                                            <span class="mb-option__name">{{ $section['title'] }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </details>
                        @endif
                    </div>
                @endforeach

                <label class="mb-option mb-option--assistant" data-option data-search="{{ mb_strtolower($topic['title'].' ki assistent chat') }}">
                    <input type="radio" name="choice" value="chat:{{ $topic['id'] }}"
                        data-label="{{ $topic['title'] }} – KI-Assistent"
                        data-note="Beantwortet Fragen der Klasse zu diesem Kurs"
                        @checked(($current ?? null) === 'chat:'.$topic['id'])>
                    <span class="mb-radio" aria-hidden="true"></span>
                    <span class="mb-option__text">
                        <span class="mb-option__name">KI-Assistent</span>
                        <span class="mb-meta">Beantwortet Fragen zu {{ $topic['title'] }}</span>
                    </span>
                </label>
            </section>
        @empty
            <div class="mb-empty">
                <p class="mb-empty__title">Noch keine Inhalte veröffentlicht</p>
                <p class="mb-meta">Sobald in MotionBase ein Kapitel veröffentlicht ist, erscheint es hier.</p>
            </div>
        @endforelse

        <div class="mb-empty" id="mb-picker-none" hidden>
            <p class="mb-empty__title">Nichts gefunden</p>
            <p class="mb-meta">Versuch es mit einem anderen Wort.</p>
        </div>
    </div>

    <footer class="mb-picker__foot" id="mb-picker-foot" hidden>
        <div class="mb-picker__chosen">
            <span class="mb-picker__chosen-label" id="mb-picker-label"></span>
            <span class="mb-meta" id="mb-picker-note"></span>
        </div>
        <button type="submit" class="mb-picker__submit" id="mb-picker-submit">{{ $submitLabel }}</button>
    </footer>
</form>

@push('styles')
<style>
    .mb-picker { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; font-feature-settings: 'ss01','ss02','cv01','cv02'; color: #18181b; padding-bottom: 6.5rem; }
    .mb-picker__head { padding: 1.5rem 1.5rem 1rem; background: #fff; border-bottom: 1px solid #e4e4e7; }
    .mb-picker__title { font-size: 1.375rem; font-weight: 700; letter-spacing: -0.02em; margin: 0; }
    .mb-picker__intro { margin: 0.375rem 0 0; color: #52525b; font-size: 0.9375rem; line-height: 1.5; max-width: 42rem; }
    .mb-picker__error { margin: 0.75rem 0 0; padding: 0.625rem 0.875rem; border-radius: 0.75rem; background: #fff1f2; border: 1px solid #fecdd3; color: #9f1239; font-size: 0.875rem; }
    .mb-picker__search { margin-top: 1rem; display: flex; align-items: center; gap: 0.5rem; padding: 0 0.875rem; border: 1px solid #e4e4e7; border-radius: 0.75rem; background: #fafafa; color: #a1a1aa; max-width: 28rem; }
    .mb-picker__search:focus-within { border-color: #18181b; background: #fff; color: #18181b; }
    .mb-picker__search input { flex: 1; border: 0; background: transparent; padding: 0.625rem 0; font: inherit; font-size: 0.9375rem; color: #18181b; outline: none; }
    .mb-picker__list { padding: 1.25rem 1.5rem; display: grid; gap: 1rem; background: #fafafa; }
    .mb-topic { background: #fff; border: 1px solid #e4e4e7; border-radius: 1rem; padding: 1rem; display: grid; gap: 0.375rem; }
    .mb-topic__head { display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: 0.25rem 1rem; padding: 0.125rem 0.25rem 0.5rem; }
    .mb-topic__title { margin: 0; font-size: 1.0625rem; font-weight: 700; letter-spacing: -0.01em; }
    .mb-meta { font-size: 0.8125rem; color: #71717a; display: inline-flex; flex-wrap: wrap; align-items: center; gap: 0.25rem 0.5rem; }
    .mb-option { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 0.875rem; border: 1px solid #e4e4e7; border-radius: 0.75rem; cursor: pointer; transition: border-color 120ms, background-color 120ms; }
    .mb-option:hover { border-color: #a1a1aa; }
    .mb-option input { position: absolute; opacity: 0; pointer-events: none; }
    .mb-option:has(input:checked) { border-color: #18181b; background: #f4f4f5; box-shadow: inset 0 0 0 1px #18181b; }
    .mb-option:has(input:focus-visible) { outline: 2px solid #18181b; outline-offset: 2px; }
    .mb-option--whole { background: #fafafa; }
    .mb-option--lesson { margin-top: 0.375rem; }
    .mb-option--assistant .mb-option__name::before { content: ''; display: inline-block; width: 0.5rem; height: 0.5rem; border-radius: 999px; background: #ff0055; margin-right: 0.5rem; vertical-align: 0.1em; }
    .mb-radio { flex: none; width: 1.125rem; height: 1.125rem; border-radius: 999px; border: 2px solid #d4d4d8; background: #fff; }
    .mb-option:has(input:checked) .mb-radio { border: 5px solid #18181b; }
    .mb-option__text { display: grid; gap: 0.125rem; min-width: 0; }
    .mb-option__name { font-weight: 600; font-size: 0.9375rem; }
    .mb-chapter { display: grid; }
    .mb-lessons { margin: 0.25rem 0 0.25rem 2.625rem; }
    .mb-lessons summary { cursor: pointer; font-size: 0.8125rem; font-weight: 500; color: #52525b; padding: 0.25rem 0; width: fit-content; }
    .mb-lessons summary:hover { color: #18181b; }
    .mb-empty { text-align: center; padding: 2.5rem 1rem; background: #fff; border: 1px dashed #d4d4d8; border-radius: 1rem; }
    .mb-empty__title { margin: 0 0 0.25rem; font-weight: 600; }
    .mb-picker__foot { position: fixed; left: 0; right: 0; bottom: 0; z-index: 10; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem 1rem; padding: 0.875rem 1.5rem; background: #fff; border-top: 1px solid #e4e4e7; box-shadow: 0 -8px 24px rgba(24, 24, 27, 0.06); }
    .mb-picker__chosen { display: grid; gap: 0.125rem; min-width: 0; }
    .mb-picker__chosen-label { font-weight: 600; font-size: 0.9375rem; }
    .mb-picker__submit { flex: none; font: inherit; font-weight: 600; font-size: 0.9375rem; padding: 0.75rem 1.5rem; border: 0; border-radius: 0.75rem; background: #18181b; color: #fff; cursor: pointer; }
    .mb-picker__submit:hover { background: #3f3f46; }
    .mb-picker__submit:disabled { opacity: 0.6; cursor: wait; }
    .mb-picker [hidden] { display: none !important; }
    .sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        var form = document.getElementById('mb-picker');
        var foot = document.getElementById('mb-picker-foot');
        var label = document.getElementById('mb-picker-label');
        var note = document.getElementById('mb-picker-note');
        var search = document.getElementById('mb-picker-search');
        var none = document.getElementById('mb-picker-none');

        function showChoice() {
            var picked = form.querySelector('input[name="choice"]:checked');
            foot.hidden = !picked;
            if (!picked) return;
            label.textContent = picked.dataset.label;
            note.textContent = picked.dataset.note;
            // A lesson picked from a closed list stays visible
            var list = picked.closest('details');
            if (list) list.open = true;
        }

        form.addEventListener('change', showChoice);
        form.addEventListener('submit', function (e) {
            if (!form.querySelector('input[name="choice"]:checked')) { e.preventDefault(); return; }
            var button = document.getElementById('mb-picker-submit');
            button.disabled = true;
            button.textContent = 'Wird übernommen…';
        });
        showChoice();

        if (!search) return;

        search.addEventListener('input', function () {
            var term = search.value.trim().toLowerCase();
            var anyTopic = false;

            form.querySelectorAll('[data-topic]').forEach(function (topic) {
                var anyOption = false;
                topic.querySelectorAll('[data-option]').forEach(function (option) {
                    var match = !term || option.dataset.search.indexOf(term) !== -1;
                    option.hidden = !match;
                    anyOption = anyOption || match;
                });
                // A match on a single lesson opens its list so it can be seen
                topic.querySelectorAll('details').forEach(function (list) {
                    var hit = term && Array.prototype.some.call(list.querySelectorAll('[data-option]'), function (o) { return !o.hidden; });
                    list.open = hit || !!list.querySelector('input:checked');
                    list.hidden = !!term && !hit;
                });
                // A lesson that matches keeps its chapter in view, for context
                topic.querySelectorAll('[data-chapter]').forEach(function (chapter) {
                    var list = chapter.querySelector('details');
                    if (list && !list.hidden) chapter.querySelector('[data-option]').hidden = false;
                });
                topic.hidden = !anyOption;
                anyTopic = anyTopic || anyOption;
            });

            none.hidden = anyTopic;
        });
    })();
</script>
@endpush
