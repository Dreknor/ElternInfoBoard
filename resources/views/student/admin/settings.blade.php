@extends('layouts.app')
@section('title') - Schülerkonten: Einstellungen @endsection

@php
    $flags = [
        'login_sso' => ['Login über SSO', 'Schülerinnen und Schüler melden sich mit dem Schul-Login an (UCS@school oder Keycloak).'],
        'login_benutzername' => ['Login mit Benutzername', 'Anmeldung mit Benutzername und Passwort auf einer eigenen Seite. Den Erstzugang gibt es über einen Einmal-Code.'],
        'jit_anlage' => ['Konto beim ersten SSO-Login anlegen', 'Nur für Kinder der unten freigegebenen Klassen, zugeordnet über den UCS-Benutzernamen des Kindes.'],
        'ucs_provisionierung' => ['Konten per UCS-Abgleich anlegen', 'Der nächtliche UCS-Abgleich legt Konten für Kinder der freigegebenen Klassen an.'],
        'eltern_zustimmung' => ['Zustimmung der Eltern nötig', 'Ein Konto wird erst aktiv, wenn eine sorgeberechtigte Bezugsperson zugestimmt hat. Ein Widerspruch sperrt es wieder.'],
        'eltern_codes' => ['Eltern dürfen Codes erzeugen', 'Bezugspersonen mit Verwaltungsrecht sehen im Bereich „Kinder“ einen Knopf für einen neuen Einmal-Code.'],
        'vertretung_abwesenheiten' => ['Abwesenheiten von Lehrkräften zeigen', 'Im Vertretungsplan der Schüler erscheinen abwesende Lehrkräfte.'],
    ];
@endphp

@section('content')
<div class="container-fluid">
    <div class="card max-w-3xl">
        <div class="card-header border-bottom">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h5 class="card-title mb-0"><i class="fas fa-cog" style="color: var(--color-primary);"></i> Schülerkonten: Einstellungen</h5>
                <a href="{{ route('schuelerkonten.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left"></i> Zurück</a>
            </div>
        </div>
        <form method="POST" action="{{ route('schuelerkonten.settings.update') }}" class="card-body space-y-4">
            @csrf @method('PUT')
            @foreach($flags as $key => [$label, $hint])
                @if(in_array($key, ['ucs_provisionierung'], true) && ! $ucsAvailable)
                    <input type="hidden" name="{{ $key }}" value="0">
                    @continue
                @endif
                <label class="flex items-start gap-3">
                    <input type="hidden" name="{{ $key }}" value="0">
                    <input type="checkbox" name="{{ $key }}" value="1" class="mt-1" @checked($settings->{$key})>
                    <span><span class="font-medium">{{ $label }}</span><br><span class="text-sm" style="color: var(--color-text-secondary);">{{ $hint }}</span></span>
                </label>
            @endforeach

            <div>
                <label class="font-medium" for="freigegebene_gruppen">Klassen für die automatische Anlage</label>
                <p class="text-sm" style="color: var(--color-text-secondary);">Gilt nur für SSO-Anlage und UCS-Abgleich. Leer heißt: keine automatische Anlage. Anlegen in der Verwaltung und der Import sind davon unabhängig.</p>
                <select id="freigegebene_gruppen" name="freigegebene_gruppen[]" multiple size="8" class="form-control mt-1">
                    @foreach($groups as $group)
                        <option value="{{ $group->id }}" @selected(in_array($group->id, $settings->freigegebene_gruppen, true))>{{ $group->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="font-medium" for="loeschen_nach_tagen">Löschfrist nach Sperre (Tage)</label>
                <p class="text-sm" style="color: var(--color-text-secondary);">Konten, deren Kind ausgeschieden ist, werden sofort gesperrt und nach dieser Frist gelöscht.</p>
                <input id="loeschen_nach_tagen" type="number" min="1" max="365" name="loeschen_nach_tagen" value="{{ $settings->loeschen_nach_tagen }}" class="form-control mt-1 w-32">
            </div>

            <button class="btn btn-primary w-full sm:w-auto"><i class="fas fa-save"></i> Speichern</button>
        </form>
    </div>
</div>
@endsection
