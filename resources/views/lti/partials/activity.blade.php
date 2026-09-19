{{--
    What the quiz script needs to know about this launch, and - for teachers
    only - a bar saying what the class sees and whether it is graded.
--}}
@php
    // Built here rather than inline: @json cannot take an array over several lines
    $mbLti = [
        'session' => $session->session_token,
        'report' => route('lti.quiz-attempts'),
        'graded' => $graded ?? false,
        'instructor' => $isInstructor ?? false,
    ];
@endphp
@push('scripts')
<script>
    window.MB_LTI = @json($mbLti);
</script>
@endpush

@if (($isInstructor ?? false) && ($activity ?? null))
    @php $questions = $activity->questionCount(); @endphp
    <div class="mb-teacher" role="note">
        <span class="mb-teacher__tag">Nur für Lehrpersonen sichtbar</span>
        <span class="mb-teacher__what">Deine Klasse sieht: <strong>{{ $activity->title() }}</strong></span>
        @if ($graded)
            <span class="mb-teacher__grade mb-teacher__grade--on">
                Wird bewertet – {{ $questions === 1 ? '1 Frage' : $questions.' Fragen' }}. Die Punkte erscheinen automatisch in den Moodle-Bewertungen.
            </span>
        @elseif ($questions)
            <span class="mb-teacher__grade">
                Wird nicht bewertet. Damit Moodle die Punkte übernimmt: Aktivität bearbeiten → „Inhalt auswählen“ → diesen Inhalt erneut wählen.
            </span>
        @endif
        @if ($canRebind ?? false)
            <a class="mb-teacher__change" href="{{ route('lti.bind', ['lti_session' => $session->session_token]) }}">Inhalt ändern</a>
        @endif
    </div>

    @push('styles')
    <style>
        .mb-teacher { display: flex; flex-wrap: wrap; align-items: center; gap: 0.375rem 1rem; padding: 0.75rem 1.5rem; background: #18181b; color: #e4e4e7; font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; font-size: 0.8125rem; line-height: 1.45; }
        .mb-teacher strong { color: #fff; font-weight: 600; }
        .mb-teacher__tag { padding: 0.0625rem 0.5rem; border-radius: 999px; background: #3f3f46; color: #fff; font-weight: 600; font-size: 0.75rem; }
        .mb-teacher__grade { color: #d4d4d8; }
        .mb-teacher__grade--on { color: #fff; }
        .mb-teacher__grade--on::before { content: ''; display: inline-block; width: 0.5rem; height: 0.5rem; border-radius: 999px; background: #ff0055; margin-right: 0.4rem; vertical-align: 0.05em; }
        .mb-teacher__change { margin-left: auto; color: #fff; font-weight: 600; text-decoration: underline; text-underline-offset: 3px; }
    </style>
    @endpush
@endif
