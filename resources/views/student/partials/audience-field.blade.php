{{-- „Sichtbar für“ (docs/konzept-schuelerkonten.md §9). Nur bei aktivem Modul und mit Recht „create student posts“
     (oder dem übergebenen `$permission`, z. B. „edit links“). `$hint` ersetzt den Hinweistext. --}}
@if(\App\Support\AudienceField::available(auth()->user(), $permission ?? null))
    @php $current = old('zielgruppe', $value ?? \App\Support\StudentAccounts::ZIELGRUPPE_ELTERN); @endphp
    <fieldset class="mt-4 rounded-lg border p-3" style="border-color: var(--color-card-border);">
        <legend class="px-1 text-sm font-semibold"><i class="fas fa-eye mr-1"></i>Sichtbar für</legend>
        <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:gap-4">
            @foreach(\App\Support\AudienceField::LABELS as $key => $label)
                <label class="flex items-center gap-2 text-sm">
                    <input type="radio" name="zielgruppe" value="{{ $key }}" @checked($current === $key)>
                    {{ $label }}
                </label>
            @endforeach
        </div>
        <p class="mt-2 text-xs" style="color: var(--color-text-secondary);">
            @isset($hint){{ $hint }}@else
            Schüler sehen den Eintrag, wenn er an ihre Klasse oder Gruppe geht. „nur Schüler“ sehen Eltern nie, auch nicht per Mail oder Push;
            Lesebestätigung, Reaktionen und Rückmeldungen gibt es dann nicht.
            @endisset
        </p>
    </fieldset>
@endif
