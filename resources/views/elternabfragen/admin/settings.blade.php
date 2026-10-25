@extends('layouts.app')

@section('title', '| Elternabfragen · Regeln')

@section('content')
@php
    $groups = [
        ['icon' => 'fas fa-bell', 'title' => 'Erinnerungen', 'fields' => [
            'first_reminder_after_hours' => ['Erste Erinnerung frühestens nach (Stunden)', 0, 720],
            'reminder_interval_hours' => ['Weitere Erinnerung höchstens alle (Stunden)', 1, 720],
            'reminder_days_before_deadline' => ['Automatische Erinnerung vor Fristende (Tage, 0 = aus)', 0, 60],
            'max_reminders' => ['Höchstzahl Erinnerungen je Kind', 0, 20],
        ]],
        ['icon' => 'fas fa-pen', 'title' => 'Korrekturen und Uploads', 'fields' => [
            'correction_window_days' => ['Standard-Zeitfenster Selbstkorrektur (Tage)', 0, 365],
            'upload_max_kb' => ['Maximale Dateigröße Upload (KB)', 100, 51200],
        ]],
        ['icon' => 'fas fa-archive', 'title' => 'Export und Aufbewahrung', 'fields' => [
            'export_expiry_days' => ['Exportdateien löschen nach (Tage)', 1, 90],
            'default_retention_days' => ['Standard-Aufbewahrung nach Abschluss (Tage)', 1, 3650],
        ]],
    ];
@endphp
<div class="container-fluid px-4 py-6 max-w-3xl">
    @include('elternabfragen.admin._nav', ['title' => 'Regeln', 'subtitle' => 'Standardregeln für Erinnerungen, Korrekturen, Export und Aufbewahrung', 'icon' => 'fas fa-sliders-h'])

    <form method="POST" action="{{ route('elternabfragen.admin.settings.update') }}" class="space-y-6">
        @csrf @method('PUT')
        @foreach($groups as $group)
            <section class="ea-card">
                <div class="ea-card-header">
                    <div class="ea-card-icon"><i class="{{ $group['icon'] }}" aria-hidden="true"></i></div>
                    <h2 class="ea-card-title">{{ $group['title'] }}</h2>
                </div>
                <div class="ea-card-body grid gap-5 sm:grid-cols-2">
                    @foreach($group['fields'] as $key => [$label, $min, $max])
                        <div>
                            <label for="{{ $key }}" class="ea-label">{{ $label }}</label>
                            <input type="number" id="{{ $key }}" name="{{ $key }}" min="{{ $min }}" max="{{ $max }}" required value="{{ old($key, $rules->{$key}) }}" class="ea-input">
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

        <section class="ea-card">
            <div class="ea-card-header">
                <div class="ea-card-icon"><i class="fas fa-user-lock" aria-hidden="true"></i></div>
                <h2 class="ea-card-title">Freigaben</h2>
            </div>
            <div class="ea-card-body">
                <label class="ea-toggle-row text-sm ea-text">
                    <input type="checkbox" name="four_eyes" value="1" class="ea-check mt-0.5" @checked(old('four_eyes', $rules->four_eyes))>
                    <span><strong>Vier-Augen-Prinzip:</strong> Vorlagen und Abfragen dürfen nicht von der Person freigegeben werden, die sie erstellt hat.</span>
                </label>
            </div>
        </section>

        <div><button class="ea-btn ea-btn-primary"><i class="fas fa-save" aria-hidden="true"></i> Speichern</button></div>
    </form>
</div>
@endsection
