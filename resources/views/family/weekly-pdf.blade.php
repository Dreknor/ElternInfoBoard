<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Wochenplan {{ $week_label }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 8pt;
            color: #1a202c;
            padding: 15px;
        }
        h1 { font-size: 13pt; margin-bottom: 3px; color: #1e40af; }
        .subtitle {
            font-size: 8pt;
            color: #6b7280;
            margin-bottom: 12px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 6px;
        }
        .child-section { margin-bottom: 20px; page-break-inside: avoid; }
        .child-header {
            background: #1e40af;
            color: white;
            padding: 5px 10px;
            font-size: 10pt;
            font-weight: bold;
            border-radius: 3px 3px 0 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
        }
        th {
            background: #dbeafe;
            text-align: center;
            padding: 4px 3px;
            border: 1px solid #93c5fd;
            font-weight: bold;
        }
        th.holiday-col { background: #fef3c7; color: #92400e; }
        th.sick-col    { background: #fee2e2; color: #991b1b; }
        td {
            padding: 3px 4px;
            border: 1px solid #e5e7eb;
            vertical-align: top;
            text-align: center;
        }
        td.holiday-cell { background: #fffbeb; color: #b45309; }
        td.sick-cell    { background: #fff1f2; }
        td.label-col {
            background: #f9fafb;
            font-weight: bold;
            text-align: center;
            width: 50px;
            color: #374151;
        }
        .gta-row td { background: #f5f3ff; }
        .gta-row td.label-col { background: #ede9fe; color: #5b21b6; }
        .hort-row td { background: #eff6ff; }
        .hort-row td.label-col { background: #dbeafe; color: #1d4ed8; }
        .badge-warning {
            display: inline-block;
            background: #fbbf24;
            color: #78350f;
            padding: 0 4px;
            border-radius: 3px;
            font-size: 7pt;
        }
        .badge-purple {
            display: inline-block;
            background: #7c3aed;
            color: white;
            padding: 0 4px;
            border-radius: 3px;
            font-size: 7pt;
        }
        .termin-block {
            margin-top: 8px;
            background: #f0fdf4;
            border-left: 3px solid #16a34a;
            padding: 5px 8px;
            border-radius: 0 3px 3px 0;
        }
        .termin-block h4 {
            font-size: 8pt;
            color: #166534;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .termin-row {
            display: inline-block;
            margin-right: 10px;
            font-size: 7.5pt;
            color: #374151;
        }
        .holiday-banner {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 3px;
            padding: 4px 8px;
            margin-bottom: 8px;
            font-size: 8pt;
            color: #92400e;
        }
        .summary {
            font-size: 7pt;
            color: #6b7280;
            margin-top: 3px;
        }
        .footer {
            margin-top: 15px;
            font-size: 7pt;
            color: #9ca3af;
            text-align: right;
            border-top: 1px solid #e5e7eb;
            padding-top: 5px;
        }
    </style>
</head>
<body>

<h1>Wochenplan – {{ $week_label }}</h1>
<div class="subtitle">
    Evangelisches Schulzentrum Radebeul &nbsp;·&nbsp;
    Erstellt am {{ now()->format('d.m.Y H:i') }} Uhr
</div>

{{-- Ferien-Banner --}}
@if($holidays->isNotEmpty())
<div class="holiday-banner">
    🏖 Ferien/Feiertage:
    @foreach($holidays as $h)
        <strong>{{ $h->name }}</strong>
        ({{ $h->start->format('d.m.') }}–{{ $h->end->format('d.m.Y') }}){{ !$loop->last ? ', ' : '' }}
    @endforeach
</div>
@endif

@forelse($children as $childData)
@php
    $dayNames  = [1 => 'Mo', 2 => 'Di', 3 => 'Mi', 4 => 'Do', 5 => 'Fr'];
    $maxStunde = 0;
    foreach ($childData['days'] as $d) {
        if (!empty($d['stundenplan'])) {
            $maxStunde = max($maxStunde, max(array_keys($d['stundenplan'])));
        }
    }
    $maxStunde = max($maxStunde, 1);
    $hasGTA          = collect($childData['days'])->contains(fn($d) => $d['gtas']->isNotEmpty());
    $hasSchickzeiten = collect($childData['days'])->contains(fn($d) => $d['schickzeiten']->isNotEmpty());
@endphp

<div class="child-section">
    <div class="child-header">
        {{ $childData['child']->first_name }} {{ $childData['child']->last_name }}
        @if($childData['klasse'])
            – Klasse: {{ $childData['klasse'] }}
        @endif
        @if($childData['summary']['sick_days'] > 0)
            | ⚕ Krank: {{ $childData['summary']['sick_days'] }} Tag(e)
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 50px;"></th>
                @foreach($childData['days'] as $day => $dayData)
                <th class="{{ $dayData['is_holiday'] ? 'holiday-col' : ($dayData['krankmeldung'] ? 'sick-col' : '') }}">
                    {{ $dayNames[$day] }} {{ $dayData['date']->format('d.m.') }}
                    @if($dayData['is_holiday'])
                        <br><small>{{ $dayData['holiday_name'] }}</small>
                    @elseif($dayData['krankmeldung'])
                        <br><small>krank</small>
                    @endif
                </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            {{-- Stundenzeilen --}}
            @for($stunde = 1; $stunde <= $maxStunde; $stunde++)
                @php
                    $hasRow = false;
                    foreach ($childData['days'] as $d) {
                        if (isset($d['stundenplan'][$stunde])) { $hasRow = true; break; }
                    }
                @endphp
                @if($hasRow)
                <tr>
                    <td class="label-col">{{ $stunde }}.</td>
                    @foreach($childData['days'] as $dayData)
                    <td class="{{ $dayData['is_holiday'] ? 'holiday-cell' : ($dayData['krankmeldung'] ? 'sick-cell' : '') }}">
                        @if($dayData['is_holiday'] || $dayData['krankmeldung'])
                            —
                        @elseif(isset($dayData['stundenplan'][$stunde]))
                            @php
                                $entry      = $dayData['stundenplan'][$stunde];
                                $vertretung = $dayData['vertretungen']->firstWhere('stunde', $stunde);
                            @endphp
                            @if($vertretung)
                                <span class="badge-warning">{{ $vertretung->neuFach ?: $entry['fach'] }} ⚠</span>
                            @else
                                {{ $entry['fach'] }}
                                @if(!empty($entry['raum']))
                                    <br><small style="color:#6b7280;">{{ implode(', ', (array)$entry['raum']) }}</small>
                                @endif
                            @endif
                        @else
                            &nbsp;
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endif
            @endfor

            {{-- GTA --}}
            @if($hasGTA)
            <tr class="gta-row">
                <td class="label-col">GTA</td>
                @foreach($childData['days'] as $dayData)
                <td>
                    @foreach($dayData['gtas'] as $gta)
                        <span class="badge-purple">{{ $gta->name }}</span>
                        @if($gta->start_time)
                            <small> {{ $gta->start_time->format('H:i') }}</small>
                        @endif
                    @endforeach
                </td>
                @endforeach
            </tr>
            @endif

            {{-- Schickzeiten --}}
            @if($hasSchickzeiten)
            <tr class="hort-row">
                <td class="label-col">Hort</td>
                @foreach($childData['days'] as $dayData)
                <td>
                    @foreach($dayData['schickzeiten'] as $sz)
                        @if($sz->time)
                            <strong>{{ $sz->time->format('H:i') }}</strong>
                        @elseif($sz->time_ab)
                            ab {{ $sz->time_ab->format('H:i') }}
                            @if($sz->time_spaet)–{{ $sz->time_spaet->format('H:i') }}@endif
                        @endif
                    @endforeach
                </td>
                @endforeach
            </tr>
            @endif
        </tbody>
    </table>

    {{-- Termine --}}
    @if($childData['termine']->isNotEmpty())
    <div class="termin-block">
        <h4>📅 Termine diese Woche:</h4>
        @foreach($childData['termine'] as $termin)
            <span class="termin-row">
                <strong>{{ $termin->start->locale('de')->isoFormat('ddd D.M.') }}:</strong>
                {{ $termin->terminname }}
                @if(!$termin->fullDay)
                    ({{ $termin->start->format('H:i') }} Uhr)
                @endif
            </span>
        @endforeach
    </div>
    @endif
</div>
@empty
    <p style="color: #9ca3af; font-style: italic;">Keine Kinder verknüpft.</p>
@endforelse

<div class="footer">
    ElternInfoBoard – Evangelisches Schulzentrum Radebeul &nbsp;·&nbsp;
    Generiert am {{ now()->format('d.m.Y \u\m H:i') }} Uhr
</div>

</body>
</html>

