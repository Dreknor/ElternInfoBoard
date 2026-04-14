<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Ferienplan {{ $start->format('d.m.Y') }} – {{ $end->format('d.m.Y') }}</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10pt;
            color: #1a202c;
            margin: 0;
            padding: 20px;
        }
        h1 { font-size: 14pt; margin-bottom: 4px; color: #2d3748; }
        .subtitle { font-size: 9pt; color: #718096; margin-bottom: 16px; }
        .date-block { page-break-inside: avoid; margin-bottom: 20px; }
        .date-header {
            background-color: #4a6fa5;
            color: #fff;
            padding: 6px 10px;
            font-weight: bold;
            font-size: 11pt;
            border-radius: 3px 3px 0 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }
        thead tr {
            background-color: #ebf4ff;
        }
        th {
            text-align: left;
            padding: 5px 8px;
            border: 1px solid #bee3f8;
            font-weight: bold;
        }
        td {
            padding: 4px 8px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }
        tr:nth-child(even) td { background-color: #f7fafc; }
        .badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 10px;
            font-size: 8pt;
            background-color: #c6f6d5;
            color: #276749;
        }
        .summary-row td {
            background-color: #f0fff4;
            font-weight: bold;
            border-top: 2px solid #68d391;
        }
        .footer {
            margin-top: 20px;
            font-size: 8pt;
            color: #a0aec0;
            text-align: right;
        }
        .empty-msg {
            padding: 12px;
            color: #718096;
            font-style: italic;
            border: 1px solid #e2e8f0;
            border-top: none;
            text-align: center;
        }
        .group-badge {
            font-size: 8pt;
            color: #4a5568;
        }
        .schickzeit {
            font-size: 8pt;
            color: #3182ce;
        }
    </style>
</head>
<body>

<h1><i></i> Ferienplan</h1>
<div class="subtitle">
    Zeitraum: {{ $start->locale('de')->isoFormat('dddd, D. MMMM YYYY') }}
    – {{ $end->locale('de')->isoFormat('dddd, D. MMMM YYYY') }}
    &nbsp;·&nbsp; Erstellt am {{ now()->format('d.m.Y H:i') }} Uhr
    &nbsp;·&nbsp; Nur Kinder mit Anmeldung (should_be = Ja)
</div>

@forelse($checkIns as $date => $dayCheckIns)
    <div class="date-block">
        <div class="date-header">
            {{ \Carbon\Carbon::parse($date)->locale('de')->isoFormat('dddd, D. MMMM YYYY') }}
            &nbsp;·&nbsp; {{ $dayCheckIns->count() }} {{ $dayCheckIns->count() === 1 ? 'Kind' : 'Kinder' }}
        </div>

        @if($dayCheckIns->isNotEmpty())
            <table>
                <thead>
                    <tr>
                        <th style="width: 25%;">Name</th>
                        <th style="width: 20%;">Gruppe</th>
                        <th style="width: 20%;">Klasse</th>
                        <th style="width: 20%;">Schickzeit</th>
                        <th style="width: 15%;">Check-In</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dayCheckIns->sortBy('child.last_name') as $checkIn)
                        @php
                            $child = $checkIn->child;
                            // Spezifische Schickzeit für diesen Tag suchen
                            $schickzeit = $child->schickzeiten
                                ->where('weekday', null)
                                ->where('specific_date', $date)
                                ->first();
                            // Fallback: Wochentag-Schickzeit
                            if (!$schickzeit) {
                                $schickzeit = $child->schickzeiten
                                    ->where('weekday', \Carbon\Carbon::parse($date)->dayOfWeekIso)
                                    ->first();
                            }
                        @endphp
                        <tr>
                            <td><strong>{{ $child->last_name }}, {{ $child->first_name }}</strong></td>
                            <td class="group-badge">{{ $child->group?->name ?? '—' }}</td>
                            <td class="group-badge">{{ $child->class?->name ?? '—' }}</td>
                            <td class="schickzeit">
                                @if($schickzeit)
                                    @if($schickzeit->type === 'genau' && $schickzeit->time)
                                        Genau {{ \Carbon\Carbon::parse($schickzeit->time)->format('H:i') }} Uhr
                                    @elseif($schickzeit->time_ab)
                                        Ab {{ \Carbon\Carbon::parse($schickzeit->time_ab)->format('H:i') }}
                                        @if($schickzeit->time_spaet)
                                            bis {{ \Carbon\Carbon::parse($schickzeit->time_spaet)->format('H:i') }} Uhr
                                        @else
                                            Uhr
                                        @endif
                                    @else
                                        —
                                    @endif
                                @else
                                    <span style="color:#a0aec0;">nicht eingetragen</span>
                                @endif
                            </td>
                            <td>
                                @if($checkIn->checked_in)
                                    <span class="badge">eingecheckt</span>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="summary-row">
                        <td colspan="4">Gesamt angemeldet</td>
                        <td>{{ $dayCheckIns->count() }}</td>
                    </tr>
                </tfoot>
            </table>
        @else
            <div class="empty-msg">Keine Kinder für diesen Tag angemeldet.</div>
        @endif
    </div>
@empty
    <p style="color:#718096; font-style:italic;">
        Keine Anmeldungen für den gewählten Zeitraum vorhanden.
    </p>
@endforelse

<div class="footer">
    ElternInfoBoard – Evangelisches Schulzentrum Radebeul &nbsp;·&nbsp;
    Generiert am {{ now()->format('d.m.Y \u\m H:i') }} Uhr
</div>

</body>
</html>

