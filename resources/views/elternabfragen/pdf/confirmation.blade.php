<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Eingangsbestätigung</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        .muted { color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { text-align: left; vertical-align: top; padding: 6px; border-bottom: 1px solid #ddd; }
        th { width: 38%; color: #444; }
        .box { border: 1px solid #ccc; padding: 8px; margin-top: 12px; }
        .mono { font-family: DejaVu Sans Mono, monospace; font-size: 9px; word-break: break-all; }
    </style>
</head>
<body>
    <p class="muted">{{ config('app.name') }} · Eingangsbestätigung</p>
    <h1>{{ $inquiry->title }}</h1>
    <p>
        Kind: <strong>{{ $assignment->childName() }}</strong><br>
        Eingereicht von: {{ $response->user?->name ?? 'unbekannt' }}<br>
        Eingang: {{ $response->submitted_at->format('d.m.Y, H:i:s') }} Uhr<br>
        Antwortversion: {{ $response->version }} (Vorlage „{{ $response->templateVersion->title }}“, Version {{ $response->templateVersion->version }})
    </p>

    <table>
        @foreach($fields as $field)
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
        <p>Nachweis</p>
        <p class="mono">Formular: {{ $response->form_hash }}<br>Antwort: {{ $response->answer_hash }}</p>
    </div>

    <p class="muted" style="margin-top: 16px;">Erstellt am {{ now()->format('d.m.Y H:i') }}. Diese Bestätigung wurde automatisch erzeugt.</p>
</body>
</html>
