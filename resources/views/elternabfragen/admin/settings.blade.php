@extends('layouts.app')

@section('title', '| Elternabfragen · Regeln')

@section('content')
@php
    $input = 'block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm';
    $numbers = [
        'first_reminder_after_hours' => ['Erste Erinnerung frühestens nach (Stunden)', 0, 720],
        'reminder_interval_hours' => ['Weitere Erinnerung höchstens alle (Stunden)', 1, 720],
        'reminder_days_before_deadline' => ['Automatische Erinnerung vor Fristende (Tage, 0 = aus)', 0, 60],
        'max_reminders' => ['Höchstzahl Erinnerungen je Kind', 0, 20],
        'correction_window_days' => ['Standard-Zeitfenster Selbstkorrektur (Tage)', 0, 365],
        'export_expiry_days' => ['Exportdateien löschen nach (Tage)', 1, 90],
        'default_retention_days' => ['Standard-Aufbewahrung nach Abschluss (Tage)', 1, 3650],
        'upload_max_kb' => ['Maximale Dateigröße Upload (KB)', 100, 51200],
    ];
@endphp
<div class="container-fluid px-4 py-6 max-w-3xl">
    @include('elternabfragen.admin._nav', ['title' => 'Regeln', 'subtitle' => 'Standardregeln für Erinnerungen, Korrekturen, Export und Aufbewahrung'])

    <form method="POST" action="{{ route('elternabfragen.admin.settings.update') }}" class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 grid gap-4 sm:grid-cols-2">
        @csrf @method('PUT')
        @foreach($numbers as $key => [$label, $min, $max])
            <div>
                <label for="{{ $key }}" class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
                <input type="number" id="{{ $key }}" name="{{ $key }}" min="{{ $min }}" max="{{ $max }}" required value="{{ old($key, $rules->{$key}) }}" class="{{ $input }}">
            </div>
        @endforeach
        <label class="sm:col-span-2 flex items-start gap-2 text-sm">
            <input type="checkbox" name="four_eyes" value="1" class="mt-1" @checked(old('four_eyes', $rules->four_eyes))>
            Vier-Augen-Prinzip: Vorlagen und Abfragen dürfen nicht von der Person freigegeben werden, die sie erstellt hat.
        </label>
        <div class="sm:col-span-2"><button class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold">Speichern</button></div>
    </form>
</div>
@endsection
