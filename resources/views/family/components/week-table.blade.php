{{--
    Wochentabelle für ein einzelnes Kind.
    Variablen: $childData (Array aus FamilyWeeklyService)
--}}

@php
    $dayNames      = [1 => 'Mo', 2 => 'Di', 3 => 'Mi', 4 => 'Do', 5 => 'Fr'];
    $dayNamesFull  = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag'];

    // Maximale Stundenzahl ermitteln
    $maxStunde = 0;
    foreach ($childData['days'] as $d) {
        if (!empty($d['stundenplan'])) {
            $maxStunde = max($maxStunde, max(array_keys($d['stundenplan'])));
        }
    }
    $maxStunde = max($maxStunde, 1);

    // Gibt es überhaupt GTA-Daten?
    $hasGTA = collect($childData['days'])->contains(fn($d) => $d['gtas']->isNotEmpty());

    // Gibt es Schickzeiten?
    $hasSchickzeiten = collect($childData['days'])->contains(fn($d) => $d['schickzeiten']->isNotEmpty());

    // Gibt es Anwesenheitsabfragen (ChildCheckIn mit lock_at)?
    $hasAbfrage = collect($childData['days'])->contains(fn($d) => $d['checkIn'] && $d['checkIn']->lock_at);

    // Gibt es Vertretungen die NICHT im Stundenplan abgedeckt sind?
    // (für separate Anzeige ohne Stundenplan-Abhängigkeit)
    $hasLooseVertretungen = collect($childData['days'])->contains(function($d) {
        if ($d['vertretungen']->isEmpty()) return false;
        // Falls kein Stundenplan: alle Vertretungen gelten als "lose"
        if (empty($d['stundenplan'])) return true;
        // Sonst: Vertretungen für Stunden ohne Stundenplan-Eintrag
        return $d['vertretungen']->contains(fn($v) => !isset($d['stundenplan'][$v->stunde]));
    });
@endphp

{{-- ── Zusammenfassung ──────────────────────────────────────────────── --}}
@if($childData['summary']['sick_days'] > 0 || $childData['summary']['has_vertretungen'] || $childData['summary']['termine_count'] > 0 || ($childData['summary']['pending_abfragen'] ?? 0) > 0)
<div class="d-flex gap-2 mb-3 flex-wrap">
    @if($childData['summary']['sick_days'] > 0)
        <span class="badge badge-danger px-3 py-2">
            <i class="fas fa-thermometer-half mr-1"></i>
            Krank {{ $childData['summary']['sick_days'] }} {{ $childData['summary']['sick_days'] == 1 ? 'Tag' : 'Tage' }}
        </span>
    @endif
    @if($childData['summary']['has_vertretungen'])
        <span class="badge badge-warning px-3 py-2 text-dark">
            <i class="fas fa-exchange-alt mr-1"></i> Vertretungen diese Woche
        </span>
    @endif
    @if(($childData['summary']['pending_abfragen'] ?? 0) > 0)
        <span class="badge badge-info px-3 py-2">
            <i class="fas fa-question-circle mr-1"></i>
            {{ $childData['summary']['pending_abfragen'] }} offene {{ $childData['summary']['pending_abfragen'] == 1 ? 'Abfrage' : 'Abfragen' }}
        </span>
    @endif
    @if($childData['summary']['termine_count'] > 0)
        <span class="badge badge-success px-3 py-2">
            <i class="far fa-calendar-alt mr-1"></i>
            {{ $childData['summary']['termine_count'] }} {{ $childData['summary']['termine_count'] == 1 ? 'Termin' : 'Termine' }}
        </span>
    @endif
</div>
@endif

{{-- ── Desktop: Tabelle ─────────────────────────────────────────────── --}}
<div class="d-none d-lg-block">
    <div class="bg-white rounded-lg shadow overflow-hidden mb-4">
        <table class="table table-bordered mb-0" style="font-size: 0.9rem;">
            <thead>
                <tr style="background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white;">
                    <th style="width: 70px;" class="text-center align-middle">Std.</th>
                    @foreach($childData['days'] as $day => $dayData)
                    <th class="text-center align-middle
                        {{ $dayData['is_holiday'] ? 'bg-yellow-600' : '' }}
                        {{ $dayData['krankmeldung'] ? 'bg-red-700' : '' }}"
                        style="{{ $dayData['is_holiday'] ? 'background: #d97706 !important;' : ($dayData['krankmeldung'] ? 'background: #b91c1c !important;' : '') }}">
                        {{ $dayNames[$day] }}
                        <br><small>{{ $dayData['date']->format('d.m.') }}</small>
                        @if($dayData['is_holiday'])
                            <br><small class="opacity-80">{{ $dayData['holiday_name'] }}</small>
                        @endif
                        @if($dayData['krankmeldung'])
                            <br><small class="opacity-80"><i class="fas fa-thermometer-half"></i> krank</small>
                        @endif
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                {{-- Stundenplan-Zeilen --}}
                @for($stunde = 1; $stunde <= $maxStunde; $stunde++)
                    @php
                        $hasData = false;
                        foreach($childData['days'] as $d) {
                            if(isset($d['stundenplan'][$stunde])) { $hasData = true; break; }
                        }
                    @endphp
                    @if($hasData)
                    <tr>
                        <td class="text-center font-weight-bold text-sm bg-light align-middle py-1">{{ $stunde }}.</td>
                        @foreach($childData['days'] as $day => $dayData)
                        @php
                            $cellClass = '';
                            if ($dayData['is_holiday'])   $cellClass = 'bg-yellow-50';
                            elseif ($dayData['krankmeldung']) $cellClass = 'bg-red-50';
                        @endphp
                        <td class="text-center align-middle p-1 {{ $cellClass }}">
                            @if($dayData['is_holiday'])
                                <span class="text-yellow-600 text-sm">—</span>
                            @elseif($dayData['krankmeldung'])
                                <span class="text-red-400 text-sm">—</span>
                            @elseif(isset($dayData['stundenplan'][$stunde]))
                                @php
                                    $entry      = $dayData['stundenplan'][$stunde];
                                    $vertretung = $dayData['vertretungen']->firstWhere('stunde', $stunde);
                                @endphp
                                @if($vertretung)
                                    <span class="badge badge-warning text-dark" style="font-size: 0.75rem;">
                                        {{ $vertretung->neuFach ?: $entry['fach'] }}
                                    </span>
                                    <br>
                                    <small class="text-warning" style="font-size: 0.7rem;">
                                        <i class="fas fa-exclamation-triangle"></i> Vertr.
                                    </small>
                                @else
                                    <span class="font-weight-semibold" style="font-size: 0.8rem;">{{ $entry['fach'] }}</span>
                                    @if(!empty($entry['raum']))
                                        <br><small class="text-muted" style="font-size: 0.7rem;">{{ implode(', ', (array)$entry['raum']) }}</small>
                                    @endif
                                @endif
                            @else
                                <span class="text-muted" style="font-size: 0.75rem;">—</span>
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @endif
                @endfor

                {{-- Vertretungen ohne Stundenplan-Entsprechung (separate Zeile) --}}
                @if($hasLooseVertretungen)
                <tr style="background-color: #fff7ed;">
                    <td class="text-center font-weight-bold align-middle" style="background: #fed7aa; font-size: 0.75rem;">⚠️ Vertr.</td>
                    @foreach($childData['days'] as $dayData)
                    <td class="text-center p-1 align-middle" style="background-color: #fff7ed;">
                        @foreach($dayData['vertretungen'] as $v)
                            @if(empty($dayData['stundenplan']) || !isset($dayData['stundenplan'][$v->stunde]))
                                <span class="badge badge-warning text-dark" style="font-size: 0.7rem;">
                                    {{ $v->stunde }}. {{ $v->neuFach ?: $v->altFach }}
                                </span>
                            @endif
                        @endforeach
                    </td>
                    @endforeach
                </tr>
                @endif

                {{-- Anwesenheitsabfrage-Zeile (ChildCheckIn mit lock_at) --}}
                @if($hasAbfrage)
                <tr style="background-color: #f0f9ff;">
                    <td class="text-center font-weight-bold align-middle" style="background: #bae6fd; font-size: 0.75rem;">📋 Abfrage</td>
                    @foreach($childData['days'] as $dayData)
                    <td class="text-center p-1 align-middle" style="background-color: #f0f9ff;">
                        @if($dayData['checkIn'] && $dayData['checkIn']->lock_at)
                            @if(is_null($dayData['checkIn']->should_be))
                                <span class="badge badge-light border text-dark" style="font-size: 0.7rem;">❓ Offen</span>
                            @elseif($dayData['checkIn']->should_be)
                                <span class="badge badge-success" style="font-size: 0.7rem;">✓ Ja</span>
                            @else
                                <span class="badge badge-secondary" style="font-size: 0.7rem;">✗ Nein</span>
                            @endif
                            <br>
                            <small class="text-muted" style="font-size: 0.65rem;">bis {{ $dayData['checkIn']->lock_at->format('d.m.') }}</small>
                            @if($dayData['checkIn']->comment)
                                <br>
                                <small class="text-primary" style="font-size: 0.65rem;" title="{{ $dayData['checkIn']->comment }}">
                                    💬 {{ \Illuminate\Support\Str::limit($dayData['checkIn']->comment, 30) }}
                                </small>
                            @endif
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endif

                {{-- GTA-Zeile --}}                @if($hasGTA)
                <tr style="background-color: #faf5ff;">
                    <td class="text-center font-weight-bold align-middle" style="background: #e9d5ff; font-size: 0.75rem;">🎨 GTA</td>
                    @foreach($childData['days'] as $dayData)
                    <td class="text-center p-1 align-middle" style="background-color: #faf5ff;">
                        @foreach($dayData['gtas'] as $gta)
                            <span class="badge" style="background: #7c3aed; color: white; font-size: 0.7rem;">{{ $gta->name }}</span>
                            @if($gta->start_time)
                                <br><small class="text-purple-600" style="font-size: 0.65rem;">
                                    {{ $gta->start_time->format('H:i') }}
                                    @if($gta->end_time)–{{ $gta->end_time->format('H:i') }}@endif
                                </small>
                            @endif
                        @endforeach
                    </td>
                    @endforeach
                </tr>
                @endif

                {{-- Schickzeiten-Zeile --}}
                @if($hasSchickzeiten)
                <tr style="background-color: #eff6ff;">
                    <td class="text-center font-weight-bold align-middle" style="background: #bfdbfe; font-size: 0.75rem;">🕐 Hort</td>
                    @foreach($childData['days'] as $dayData)
                    <td class="text-center p-1 align-middle" style="background-color: #eff6ff;">
                        @foreach($dayData['schickzeiten'] as $sz)
                            @if($sz->time)
                                <span class="text-blue-700 font-weight-semibold" style="font-size: 0.8rem;">
                                    {{ $sz->time->format('H:i') }}
                                </span>
                            @elseif($sz->time_ab)
                                <span class="text-blue-600" style="font-size: 0.75rem;">
                                    ab {{ $sz->time_ab->format('H:i') }}
                                    @if($sz->time_spaet)–{{ $sz->time_spaet->format('H:i') }}@endif
                                </span>
                            @endif
                        @endforeach
                    </td>
                    @endforeach
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

{{-- ── Mobil: Tageskarten ───────────────────────────────────────────── --}}
<div class="d-lg-none">
    @foreach($childData['days'] as $day => $dayData)
    <div class="bg-white rounded-lg shadow mb-3 overflow-hidden
        {{ $dayData['krankmeldung'] ? 'border-l-4 border-red-500' : '' }}
        {{ $dayData['is_holiday']   ? 'border-l-4 border-yellow-400' : '' }}">

        {{-- Tageskopf --}}
        <div class="px-3 py-2 d-flex justify-content-between align-items-center
            {{ $dayData['is_holiday'] ? 'bg-yellow-100' : ($dayData['krankmeldung'] ? 'bg-red-100' : 'bg-gray-50 border-bottom') }}">
            <span class="font-weight-bold
                {{ $dayData['is_holiday'] ? 'text-yellow-700' : ($dayData['krankmeldung'] ? 'text-red-700' : 'text-gray-800') }}">
                {{ $dayNamesFull[$day] }}, {{ $dayData['date']->format('d.m.') }}
            </span>
            <div class="d-flex gap-1">
                @if($dayData['is_holiday'])
                    <span class="badge badge-warning text-dark">{{ $dayData['holiday_name'] }}</span>
                @endif
                @if($dayData['krankmeldung'])
                    <span class="badge badge-danger"><i class="fas fa-thermometer-half"></i> krank</span>
                @endif
                @if($dayData['checkIn'])
                    @if($dayData['checkIn']->should_be)
                        <span class="badge badge-success">Hort ✓</span>
                    @elseif($dayData['checkIn']->should_be === false)
                        <span class="badge badge-secondary">Hort ✗</span>
                    @else
                        <span class="badge badge-light border">Hort ?</span>
                    @endif
                @endif
            </div>
        </div>

        @unless($dayData['is_holiday'])
        <div class="p-3">
            {{-- Stundenplan kompakt --}}
            @if(!empty($dayData['stundenplan']))
            <div class="d-flex flex-wrap gap-1 mb-2">
                @foreach($dayData['stundenplan'] as $stunde => $entry)
                    @php $vertretung = $dayData['vertretungen']->firstWhere('stunde', $stunde); @endphp
                    @if($vertretung)
                        <span class="badge badge-warning text-dark" style="font-size: 0.7rem;">
                            {{ $stunde }}. {{ $vertretung->neuFach ?: $entry['fach'] }}
                            <i class="fas fa-exclamation-triangle"></i>
                        </span>
                    @else
                        <span class="badge badge-light border" style="font-size: 0.7rem;">
                            {{ $stunde }}. {{ $entry['fach'] }}
                        </span>
                    @endif
                @endforeach
            </div>
            @endif

            {{-- Vertretungen ohne Stundenplan-Eintrag (falls Stundenplan fehlt) --}}
            @if($dayData['vertretungen']->isNotEmpty())
                @php
                    $looseVertr = $dayData['vertretungen']->filter(
                        fn($v) => empty($dayData['stundenplan']) || !isset($dayData['stundenplan'][$v->stunde])
                    );
                @endphp
                @if($looseVertr->isNotEmpty())
                <div class="mt-1">
                    <small class="font-weight-bold" style="color: #c2410c;">⚠️ Vertretungen:</small>
                    @foreach($looseVertr as $v)
                        <span class="badge badge-warning text-dark ml-1" style="font-size: 0.7rem;">
                            {{ $v->stunde }}. {{ $v->neuFach ?: $v->altFach }}
                        </span>
                    @endforeach
                </div>
                @endif
            @endif

            {{-- GTAs --}}
            @if($dayData['gtas']->isNotEmpty())
            <div class="mt-1">
                <small class="font-weight-bold" style="color: #7c3aed;">🎨 GTA:</small>
                @foreach($dayData['gtas'] as $gta)
                    <span class="badge ml-1" style="background: #7c3aed; color: white; font-size: 0.7rem;">{{ $gta->name }}</span>
                    @if($gta->start_time)
                        <small class="text-muted">{{ $gta->start_time->format('H:i') }}</small>
                    @endif
                @endforeach
            </div>
            @endif

            {{-- Schickzeiten --}}
            @if($dayData['schickzeiten']->isNotEmpty())
            <div class="mt-1">
                <small class="text-blue-600 font-weight-bold">🕐 Hort:</small>
                @foreach($dayData['schickzeiten'] as $sz)
                    @if($sz->time)
                        <strong class="text-blue-700">{{ $sz->time->format('H:i') }}</strong>
                    @elseif($sz->time_ab)
                        <span class="text-blue-600">ab {{ $sz->time_ab->format('H:i') }}
                            @if($sz->time_spaet)–{{ $sz->time_spaet->format('H:i') }}@endif
                        </span>
                    @endif
                @endforeach
            </div>
            @endif

            {{-- Anwesenheitsabfrage --}}
            @if($dayData['checkIn'] && $dayData['checkIn']->lock_at)
            <div class="mt-2 p-2 rounded" style="background: #f0f9ff; border-left: 3px solid #38bdf8;">
                <div class="d-flex align-items-center gap-1 flex-wrap">
                    <small class="font-weight-bold" style="color: #0284c7; font-size: 0.7rem;">📋 Anwesenheitsabfrage</small>
                    @if(is_null($dayData['checkIn']->should_be))
                        <span class="badge badge-light border text-dark" style="font-size: 0.65rem;">❓ Offen</span>
                    @elseif($dayData['checkIn']->should_be)
                        <span class="badge badge-success" style="font-size: 0.65rem;">✓ Ja</span>
                    @else
                        <span class="badge badge-secondary" style="font-size: 0.65rem;">✗ Nein</span>
                    @endif
                    <small class="text-muted" style="font-size: 0.65rem;">(bis {{ $dayData['checkIn']->lock_at->format('d.m.') }})</small>
                </div>
                @if($dayData['checkIn']->comment)
                    <div class="mt-1" style="font-size: 0.75rem; color: #0369a1;">
                        💬 {{ $dayData['checkIn']->comment }}
                    </div>
                @endif
            </div>
            @endif
        </div>
        @endunless
    </div>
    @endforeach
</div>

{{-- ── Termine der Woche ────────────────────────────────────────────── --}}
@if($childData['termine']->isNotEmpty())
<div class="bg-green-50 border-l-4 border-green-500 rounded-lg p-4 mt-4 mb-4">
    <h6 class="font-weight-bold text-success mb-2">
        <i class="far fa-calendar-alt"></i> Termine diese Woche
    </h6>
    @foreach($childData['termine'] as $termin)
    <div class="d-flex gap-2 align-items-center mb-1">
        <span class="badge badge-success">{{ $termin->start->locale('de')->isoFormat('ddd D.M.') }}</span>
        <span>{{ $termin->terminname }}</span>
        @if(!$termin->fullDay)
            <small class="text-muted">{{ $termin->start->format('H:i') }} Uhr</small>
        @else
            <small class="text-muted">ganztägig</small>
        @endif
    </div>
    @endforeach
</div>
@endif

