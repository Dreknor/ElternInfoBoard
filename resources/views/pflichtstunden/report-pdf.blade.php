<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Pflichtstunden Report</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            color: #1f2937;
            margin: 20px;
        }

        h1, h2, h3, p {
            margin: 0 0 8px;
        }

        h1 {
            font-size: 24px;
            margin-bottom: 12px;
        }

        h2 {
            font-size: 16px;
            margin-top: 18px;
            margin-bottom: 8px;
            color: #111827;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 4px;
        }

        .meta {
            margin-bottom: 12px;
            color: #4b5563;
        }

        .kpis {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .kpis td {
            width: 25%;
            border: 1px solid #d1d5db;
            background: #f9fafb;
            padding: 8px;
            vertical-align: top;
        }

        .kpis .label {
            display: block;
            font-size: 9px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 4px;
        }

        .kpis .value {
            font-size: 18px;
            font-weight: 700;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            page-break-inside: avoid;
        }

        thead {
            display: table-header-group;
        }

        th, td {
            border: 1px solid #d1d5db;
            padding: 6px 8px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #eef2ff;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #374151;
        }

        .table-small td, .table-small th {
            font-size: 9px;
            padding: 5px 6px;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 999px;
            font-size: 8px;
            font-weight: 700;
            background: #dcfce7;
            color: #166534;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .stats-grid {
            width: 100%;
            margin-bottom: 12px;
        }

        .stats-grid td {
            width: 50%;
            vertical-align: top;
            padding: 0 8px 0 0;
            border: none;
        }

        .muted {
            color: #6b7280;
        }

        @page {
            size: A4 portrait;
            margin: 15mm 12mm;
        }
    </style>
</head>
<body>
    @php
        $formatMinutes = function (int $minutes): string {
            $sign = $minutes < 0 ? '-' : '';
            $abs = abs($minutes);
            $hours = intdiv($abs, 60);
            $mins = $abs % 60;
            return $sign . $hours . 'h ' . $mins . 'm';
        };
    @endphp

    <h1>Pflichtstunden-Report</h1>
    <p class="meta">Zeitraum: {{ $period_start->format('d.m.Y') }} bis {{ $period_end->format('d.m.Y') }} | Sortierung: {{ $sort === 'highest_debt' ? 'Höchste Stundenschuld' : 'Nachname A-Z' }} | Anonymisiert: {{ $anonymized ? 'Ja' : 'Nein' }}</p>

    <h2>Teil 1: Management Summary &amp; Plausibilität</h2>
    <table class="kpis">
        <tr>
            <td>
                <span class="label">Freigegebene Stunden</span>
                <span class="value">{{ number_format((float) $summary['total_approved_hours'], 2, ',', '.') }}h</span>
            </td>
            <td>
                <span class="label">Wartende Einträge</span>
                <span class="value">{{ $summary['pending_entries_count'] }}</span>
            </td>
            <td>
                <span class="label">Abgelehnte Einträge</span>
                <span class="value">{{ $summary['rejected_entries_count'] }}</span>
            </td>
            <td>
                <span class="label">Auffällige Einträge</span>
                <span class="value">{{ $error_entries->count() }}</span>
            </td>
        </tr>
    </table>

    @if($error_entries->isNotEmpty())
        <table class="table-small">
            <thead>
            <tr>
                <th>Familie</th>
                <th>Start</th>
                <th>Ende</th>
                <th>Dauer</th>
                <th>Bereich</th>
                <th>Beschreibung</th>
            </tr>
            </thead>
            <tbody>
            @foreach($error_entries as $entry)
                <tr>
                    <td>{{ $entry['family_name'] }}</td>
                    <td>{{ $entry['start']->format('d.m.Y H:i') }}</td>
                    <td>{{ $entry['end']->format('d.m.Y H:i') }}</td>
                    <td><span class="badge badge-warning">{{ number_format((float) $entry['duration_hours'], 2, ',', '.') }}h</span></td>
                    <td>{{ $entry['bereich'] }}</td>
                    <td>{{ $entry['description'] ?? '-' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @else
        <p class="muted">Keine auffälligen Einträge (> 12 Stunden) im gewählten Zeitraum.</p>
    @endif

    <h2>Teil 2: Zeitliche Verteilung &amp; Ressourcen</h2>
    <table class="table-small">
        <thead>
        <tr>
            <th>Bereich</th>
            <th>Stunden</th>
        </tr>
        </thead>
        <tbody>
        @foreach($areas as $label => $hours)
            <tr>
                <td>{{ $label }}</td>
                <td>{{ number_format((float) $hours, 2, ',', '.') }}h</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="table-small">
        <thead>
        <tr>
            <th>Wochentag</th>
            <th>Stunden</th>
        </tr>
        </thead>
        <tbody>
        @foreach($weekday_distribution as $label => $hours)
            <tr>
                <td>{{ $label }}</td>
                <td>{{ number_format((float) $hours, 2, ',', '.') }}h</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="table-small">
        <thead>
        <tr>
            <th>Tageszeit</th>
            <th>Stunden</th>
        </tr>
        </thead>
        <tbody>
        @foreach($time_distribution as $label => $hours)
            <tr>
                <td>{{ $label }}</td>
                <td>{{ number_format((float) $hours, 2, ',', '.') }}h</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="table-small">
        <thead>
        <tr>
            <th>Monat</th>
            <th>Stunden</th>
        </tr>
        </thead>
        <tbody>
        @foreach($monthly_distribution as $month => $data)
            <tr>
                <td>{{ $data['label'] }}</td>
                <td>{{ number_format((float) $data['hours'], 2, ',', '.') }}h</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <h2>Teil 3: Controlling des Freigabeprozesses</h2>
    <table class="table-small">
        <tr>
            <th>Durchschnittliche Bearbeitungsdauer</th>
            <td>{{ number_format((float) $process_metrics['avg_approval_days'], 2, ',', '.') }} Tage</td>
        </tr>
        <tr>
            <th>Häufigster Ablehnungsgrund</th>
            <td>{{ $process_metrics['most_common_rejection_reason']['reason'] }} ({{ $process_metrics['most_common_rejection_reason']['count'] }})</td>
        </tr>
        <tr>
            <th>Anzahl abgelehnter Einträge</th>
            <td>{{ $process_metrics['rejection_count'] }}</td>
        </tr>
    </table>

    <table class="table-small">
        <thead>
        <tr>
            <th>Admin</th>
            <th>Genehmigt</th>
            <th>Abgelehnt</th>
            <th>Gesamt</th>
        </tr>
        </thead>
        <tbody>
        @foreach($process_metrics['workload'] as $entry)
            <tr>
                <td>{{ $entry['admin_name'] }}</td>
                <td>{{ $entry['approved'] }}</td>
                <td>{{ $entry['rejected'] }}</td>
                <td>{{ $entry['total'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <h2>Teil 4: Familien-Abrechnung</h2>

    <table class="table-small">
        <thead>
        <tr>
            <th>Top-Helfer</th>
            <th>Erfüllt</th>
            <th>Überschuss</th>
        </tr>
        </thead>
        <tbody>
        @foreach($top_helpers as $helper)
            <tr>
                <td>{{ $helper['family_name'] }}</td>
                <td>{{ number_format((float) $helper['approved_hours'], 2, ',', '.') }}h</td>
                <td>{{ number_format((float) $helper['extra_hours'], 2, ',', '.') }}h</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <table class="table-small">
        <thead>
        <tr>
            <th>Familie</th>
            <th>Soll</th>
            <th>Geleistet</th>
            <th>Ausstehend</th>
            <th>Differenz</th>
            <th>Erfüllung</th>
        </tr>
        </thead>
        <tbody>
        @foreach($family_rows as $family)
            @php
                $alertClass = $family['difference_minutes'] < 0 ? 'badge-danger' : 'badge';
            @endphp
            <tr>
                <td>{{ $family['family_name'] }}</td>
                <td>{{ number_format((float) $family['required_hours'], 2, ',', '.') }}h</td>
                <td>{{ number_format((float) $family['approved_hours'], 2, ',', '.') }}h</td>
                <td>{{ number_format((float) $family['pending_hours'], 2, ',', '.') }}h</td>
                <td><span class="badge {{ $alertClass }}">{{ $formatMinutes($family['difference_minutes']) }}</span></td>
                <td>{{ number_format((float) $family['percent'], 2, ',', '.') }}%</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>
