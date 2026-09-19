{{-- For teachers only: a bar saying what the class sees in this activity. --}}
@if (($isInstructor ?? false) && ($activity ?? null))
    <div class="mb-teacher" role="note">
        <span class="mb-teacher__tag">Nur für Lehrpersonen sichtbar</span>
        <span class="mb-teacher__what">Deine Klasse sieht: <strong>{{ $activity->title() }}</strong></span>
        @if ($canRebind ?? false)
            <a class="mb-teacher__change" href="{{ route('lti.bind', ['lti_session' => $session->session_token]) }}">Inhalt ändern</a>
        @endif
    </div>

    @push('styles')
    <style>
        .mb-teacher { display: flex; flex-wrap: wrap; align-items: center; gap: 0.375rem 1rem; padding: 0.75rem 1.5rem; background: #18181b; color: #e4e4e7; font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; font-size: 0.8125rem; line-height: 1.45; }
        .mb-teacher strong { color: #fff; font-weight: 600; }
        .mb-teacher__tag { padding: 0.0625rem 0.5rem; border-radius: 999px; background: #3f3f46; color: #fff; font-weight: 600; font-size: 0.75rem; }
        .mb-teacher__change { margin-left: auto; color: #fff; font-weight: 600; text-decoration: underline; text-underline-offset: 3px; }
    </style>
    @endpush
@endif
