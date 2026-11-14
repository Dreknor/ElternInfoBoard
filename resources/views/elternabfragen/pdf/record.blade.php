<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Ablage Schülerakte</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .muted { color: #666; }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { text-align: left; vertical-align: top; padding: 6px; border-bottom: 1px solid #ddd; }
        th { width: 38%; color: #444; }
        .meta td { border: 0; padding: 2px 6px 2px 0; }
        .meta td:first-child { width: 28%; color: #444; }
        .box { border: 1px solid #ccc; padding: 8px; margin-top: 12px; }
        .mono { font-family: DejaVu Sans Mono, monospace; font-size: 9px; word-break: break-all; }
        .filing td { border: 0; padding: 14px 6px 0 0; }
        .line { border-bottom: 1px solid #444; }
    </style>
</head>
<body>
@foreach($assignments as $assignment)
    @php
        $response = $assignment->currentResponse;
        $version = $response->templateVersion;
    @endphp
    <div class="page">
        <p class="muted">{{ config('app.name') }} · Elternabfrage · Ablage in der Schülerakte</p>
        <h1>{{ $inquiry->title }}</h1>

        <table class="meta">
            <tr><td>Kind</td><td><strong>{{ $assignment->childName() }}</strong></td></tr>
            <tr><td>Klasse</td><td>{{ $assignment->group?->name ?? '–' }}</td></tr>
            <tr><td>Eingereicht von</td><td>{{ $response->user?->name ?? 'unbekannt' }} am {{ $response->submitted_at->format('d.m.Y, H:i') }} Uhr ({{ $response->channel === 'app' ? 'App' : 'Web' }})</td></tr>
            @foreach($response->confirmations as $confirmation)
                <tr><td>Bestätigt von</td><td>{{ $confirmation->user?->name ?? 'unbekannt' }} am {{ $confirmation->confirmed_at->format('d.m.Y, H:i') }} Uhr</td></tr>
            @endforeach
            <tr><td>Antwortversion</td><td>{{ $response->version }} · Vorlage „{{ $version->title }}“, Version {{ $version->version }}</td></tr>
            @if($response->correction_reason)
                <tr><td>Korrekturgrund</td><td>{{ $response->correction_reason }}</td></tr>
            @endif
        </table>

        <table>
            @foreach($version->fields as $field)
                @continue($field['type'] === 'info')
                <tr>
                    <th>{{ $field['label'] }}</th>
                    <td>{{ $schema->display($field, $response->answers[$field['key']] ?? null) }}</td>
                </tr>
                @if($field['type'] === 'consent')
                    <tr>
                        <td colspan="2" class="muted">Einwilligungstext: {{ $field['consent_text'] }}</td>
                    </tr>
                @endif
            @endforeach
        </table>

        <div class="box">
            <p style="margin-top: 0;">Die Angaben wurden elektronisch über das ElternInfoBoard abgegeben. Nachweis über die Prüfsummen:</p>
            <p class="mono" style="margin-bottom: 0;">Formular: {{ $response->form_hash }}<br>Antwort: {{ $response->answer_hash }}</p>
        </div>

        <table class="filing">
            <tr>
                <td style="width: 22%;">Zur Schülerakte genommen am</td><td class="line" style="width: 28%;">&nbsp;</td>
                <td style="width: 14%; padding-left: 12px;">Handzeichen</td><td class="line">&nbsp;</td>
            </tr>
        </table>

        <p class="muted" style="margin-top: 16px;">Gedruckt am {{ now()->format('d.m.Y H:i') }} von {{ $printedBy->name }}.</p>
    </div>
@endforeach
</body>
</html>
