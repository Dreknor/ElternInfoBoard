@extends('layouts.app')
@section('title') - Reinigungsplan automatisch erstellen @endsection

@section('content')
    <div class="w-full max-w-4xl mx-auto px-2 sm:px-4 py-4 sm:py-6 space-y-4">
        <a href="{{url('reinigung')}}"
           class="inline-flex items-center gap-2 px-4 py-2 text-white font-medium rounded-lg transition-colors duration-200"
           style="background: var(--color-widget-primary-from);"
           onmouseover="this.style.background=getComputedStyle(document.documentElement).getPropertyValue('--color-widget-primary-to')"
           onmouseout="this.style.background=getComputedStyle(document.documentElement).getPropertyValue('--color-widget-primary-from')">
            <i class="fas fa-arrow-left"></i>
            zurück
        </a>

        <div class="rounded-lg shadow-lg overflow-hidden" style="background: var(--color-card-bg);">
            <div class="px-4 py-3 border-b"
                 style="background: linear-gradient(to right, var(--color-widget-primary-from), var(--color-widget-primary-to)); border-color: var(--color-widget-primary-border);">
                <h5 class="text-lg font-bold flex items-center gap-2 mb-0" style="color: var(--color-widget-header-text);">
                    <i class="fas fa-magic"></i>
                    Automatisch Reinigungsplan erstellen
                </h5>
            </div>
            <div class="p-4">
                <div class="p-3 mb-3 rounded-lg d-flex align-items-start gap-2" style="background: var(--color-widget-warning-bg); border: 1px solid var(--color-widget-warning-border);">
                    <i class="fas fa-exclamation-triangle mt-1" style="color: var(--color-widget-warning-from);"></i>
                    <p class="mb-0" style="color: var(--color-text-primary);">
                        Achtung! Die automatische Reinigungserstellung ist eine experimentelle Funktion. Es wird empfohlen, die erstellten Pläne zu überprüfen.
                    </p>
                </div>
                <p style="color: var(--color-text-secondary);">
                    Die automatische Reinigungserstellung erstellt für den gewählten Zeitraum einen Reinigungsplan.
                    Dabei wird für jede Woche ein Eintrag erstellt, der die Reinigungsaufgabe und die Familie enthält.
                </p>
                <p style="color: var(--color-text-secondary);">
                    Wähle den Zeitraum, für den der Reinigungsplan erstellt werden soll. Dann lege fest, welche Aufgaben verteilt werden sollen und ob bestimmte Gruppen ausgeschlossen werden sollen. Die Auswahl mehrerer Aufgaben und ausgeschlossener Gruppen ist möglich.
                </p>

                <form id="createForm" action="{{url('reinigung/'.$bereichName.'/auto')}}" method="post" class="space-y-3 mt-4">
                    @csrf
                    <div>
                        <label class="d-block mb-1" style="color: var(--color-widget-warning-from);">
                            Startdatum*
                        </label>
                        <input class="w-100 px-3 py-2 rounded-lg outline-none" name="start" type="date" value="{{now()->format('Y-m-d')}}"
                               style="border: 2px solid var(--color-input-border); background: var(--color-input-bg); color: var(--color-text-primary);">
                    </div>
                    <div>
                        <label class="d-block mb-1" style="color: var(--color-widget-warning-from);">
                            Enddatum*
                        </label>
                        <input class="w-100 px-3 py-2 rounded-lg outline-none" name="end" type="date"
                               value="{{\Carbon\Carbon::createFromFormat('d.m', '01.08')->addYear()->format('Y-m-d')}}"
                               style="border: 2px solid var(--color-input-border); background: var(--color-input-bg); color: var(--color-text-primary);">
                    </div>
                    <div>
                        <label class="d-block mb-1" style="color: var(--color-widget-warning-from);">
                            Aufgaben*
                        </label>
                        <select name="aufgaben[]" class="w-100 px-3 py-2 rounded-lg outline-none" multiple
                                style="border: 2px solid var(--color-input-border); background: var(--color-input-bg); color: var(--color-text-primary);">
                            @foreach($aufgaben as $task)
                                <option value="{{$task->id}}">
                                    {{$task->task}}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="d-block mb-1" style="color: var(--color-text-primary);">
                            Ausgeschlossene Gruppen
                        </label>
                        <select name="exclude[]" class="w-100 px-3 py-2 rounded-lg outline-none" multiple size="{{$bereich->count()+1}}"
                                style="border: 2px solid var(--color-input-border); background: var(--color-input-bg); color: var(--color-text-primary);">
                            <option value="" selected>
                                keine
                            </option>
                            @foreach($bereich as $group)
                                <option value="{{$group->id}}">
                                    {{$group->name}}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
            <div class="px-4 py-3 border-t" style="border-color: var(--color-card-border);">
                <button type="submit" form="createForm"
                        class="w-100 inline-flex items-center justify-content-center gap-2 px-4 py-2 text-white font-medium rounded-lg transition-colors duration-200"
                        style="background: var(--color-widget-success-from);"
                        onmouseover="this.style.background=getComputedStyle(document.documentElement).getPropertyValue('--color-widget-success-to')"
                        onmouseout="this.style.background=getComputedStyle(document.documentElement).getPropertyValue('--color-widget-success-from')">
                    <i class="fas fa-magic"></i>
                    generieren
                </button>
            </div>
        </div>
    </div>
@endsection

