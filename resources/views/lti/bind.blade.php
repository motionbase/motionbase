@extends('lti.layout')

@section('title', 'Inhalt für diese Aktivität wählen')

@section('content')
    @include('lti.partials.content-picker', [
        'action' => route('lti.bind.store'),
        'heading' => $current ? 'Anderen Inhalt wählen' : 'Diese Aktivität zeigt noch nichts',
        'intro' => $current
            ? 'Wähle, was deine Klasse hier stattdessen sehen soll.'
            : 'Beim Anlegen wurde kein Inhalt ausgewählt. Wähle jetzt, was deine Klasse hier sehen soll – das gilt ab sofort für alle.',
        'submitLabel' => 'Für die Klasse übernehmen',
    ])
@endsection
