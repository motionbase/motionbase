@extends('lti.layout')

@section('title', 'Inhalt aus MotionBase auswählen')

@section('content')
    @include('lti.partials.content-picker', [
        'action' => route('lti.deep-linking.return'),
        'heading' => 'Was soll deine Klasse sehen?',
        'intro' => 'Wähle einen ganzen Kurs, ein Kapitel oder eine einzelne Lektion.',
        'submitLabel' => 'Übernehmen',
    ])
@endsection
