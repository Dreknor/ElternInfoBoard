{{-- REFERENZ (Test): unveränderte Layout-1-View aus Commit 0b2305a – nicht ändern. Erklärungsquittung (§ 126b BGB). Deterministisch: keine aktuelle Uhrzeit, keine Einstellungen.
     Jede Änderung an dieser Datei erfordert ReceiptService::LAYOUT_VERSION + 1. --}}
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Erklärungsquittung {{ $receipt->uuid }}</title>
    <style>
        @page { margin: 18mm 16mm 26mm 16mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; line-height: 1.35; }
        h1 { font-size: 15px; margin: 0 0 2px 0; }
        h2 { font-size: 12px; margin: 14px 0 4px 0; border-bottom: 1px solid #999; padding-bottom: 2px; }
        p { margin: 0 0 4px 0; }
        .muted { color: #555; }
        .pre { white-space: pre-line; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; vertical-align: top; padding: 3px 4px; }
        table.meta th { width: 32%; font-weight: normal; color: #555; }
        .field { border-bottom: 1px solid #ddd; padding: 5px 0; page-break-inside: avoid; }
        .section { font-weight: bold; margin-top: 8px; }
        .answer { margin-top: 3px; padding: 3px 6px; background: #f2f2f2; }
        .consent { margin: 3px 0; padding: 4px 6px; border-left: 2px solid #999; }
        .note { border: 1px solid #999; padding: 6px; margin-top: 8px; }
        .mono { font-family: DejaVu Sans Mono, monospace; font-size: 8px; word-break: break-all; }
        .footer { position: fixed; bottom: -18mm; left: 0; right: 0; font-size: 7.5px; color: #555; border-top: 1px solid #999; padding-top: 3px; }
    </style>
</head>
<body>
    <div class="footer">
        Quittung {{ $receipt->uuid }} · Nr. {{ $receipt->sequence }} der Abfrage · Layout {{ $receipt->layout_version }}@if($receipt->backfilled) · nachträglich erstellt @endif<br>
        <span class="mono">Inhalt (content_hash): {{ $receipt->content_hash }}</span><br>
        <span class="mono">Wortlaut (wording_hash): {{ $receipt->wording_hash }}</span>
    </div>

    <p class="muted">{{ $receipt->school_name }} · Erklärungsquittung</p>
    <h1>{{ $inquiry->title }}</h1>
    <p class="muted">Vorlage „{{ $version->title }}“, Version {{ $version->version }}</p>

    @if($receipt->backfilled)
        <div class="note"><strong>Nachträglich erstellt:</strong> Diese Quittung wurde nachträglich aus den gespeicherten Daten der Erklärung erstellt.</div>
    @endif

    <h2>Erklärung</h2>
    <table class="meta">
        <tr><th>Art der Erklärung</th><td>{{ $kindLabel }}</td></tr>
        <tr><th>Kind</th><td>{{ $receipt->child_name }}</td></tr>
        <tr><th>Erklärende Person</th><td>{{ $receipt->declarant_name }}</td></tr>
        <tr><th>Zeitpunkt</th><td>{{ $declaredAt }} Uhr ({{ \App\Services\Elternabfragen\ReceiptService::TIMEZONE }})</td></tr>
        <tr><th>Übermittelt über</th><td>{{ $channelLabel }}</td></tr>
        <tr><th>Antwortversion</th><td>{{ $receipt->response_version }}</td></tr>
        <tr><th>Quittungsnummer</th><td>{{ $receipt->uuid }}</td></tr>
    </table>
    @if($receipt->kind === \App\Enums\InquiryReceiptKind::Confirmation)
        <p class="muted" style="margin-top: 4px;">Die erklärende Person hat die unten aufgeführten Angaben (Antwortversion {{ $receipt->response_version }}) als weitere sorgeberechtigte Person verbindlich bestätigt.</p>
    @elseif($receipt->kind === \App\Enums\InquiryReceiptKind::ChangeAccepted)
        <p class="muted" style="margin-top: 4px;">Die von der erklärenden Person gemeldete Änderung wurde von der Schule geprüft und als neue Antwortversion übernommen.</p>
    @endif

    <h2>Angaben der Schule</h2>
    @if(filled($inquiry->intro))
        <p class="pre">{{ $inquiry->intro }}</p>
    @endif
    <p><strong>Wofür werden die Angaben benötigt?</strong></p>
    <p class="pre">{{ $version->purpose }}</p>
    @if($version->requires_all_guardians)
        <p><strong>Gemeinsame Erklärung:</strong> Eine sorgeberechtigte Person füllt die Abfrage aus, alle weiteren Sorgeberechtigten bestätigen die Angaben anschließend.</p>
    @endif
    @if(filled($version->parent_info))
        <p><strong>Weitere Hinweise</strong></p>
        <p class="pre">{{ $version->parent_info }}</p>
    @endif
    <p><strong>Datenschutz und Aufbewahrung</strong></p>
    @if(filled($version->responsible_office))
        <p>Zuständige Stelle: {{ $version->responsible_office }}</p>
    @endif
    @if(filled($version->retention_note))
        <p class="pre">{{ $version->retention_note }}</p>
    @endif
    <p>{{ $retentionText }}</p>

    <h2>Fragen und Antworten</h2>
    @foreach($fields as $field)
        @if(! $field['is_input'])
            <div class="section">{{ $field['label'] }}</div>
            @if(filled($field['help']))<p class="pre muted">{{ $field['help'] }}</p>@endif
            @continue
        @endif
        <div class="field">
            <p><strong>{{ $field['label'] }}</strong>@if($field['required']) (Pflichtangabe)@endif <span class="muted">· {{ $field['type_label'] }}</span></p>
            @if(filled($field['help']))<p class="pre muted">{{ $field['help'] }}</p>@endif
            @if(filled($field['consent_text']))
                <div class="consent pre">{{ $field['consent_text'] }}</div>
            @endif
            @if($field['options'] !== [])
                <p class="muted">Antwortmöglichkeiten: {{ implode(' · ', $field['options']) }}</p>
            @endif
            <div class="answer">
                Antwort:
                @if($field['file'])
                    Datei „{{ $field['file']['name'] }}“@if($field['file']['sha256']) <span class="mono">(SHA-256 {{ $field['file']['sha256'] }})</span>@endif
                @else
                    <strong class="pre">{{ $field['answer'] }}</strong>
                @endif
            </div>
        </div>
    @endforeach

    <h2>Nachweis</h2>
    <p>Diese Quittung dokumentiert die oben genannte Erklärung in Textform (§ 126b BGB). Sie kann von der Schule jederzeit aus den gespeicherten Daten byteidentisch neu erzeugt werden. Die Prüfsummen belegen, dass Wortlaut, Antworten und Quittung unverändert sind.</p>
    <table class="meta">
        <tr><th>Wortlaut</th><td class="mono">{{ $receipt->wording_hash }}</td></tr>
        <tr><th>Formular</th><td class="mono">{{ $receipt->form_hash }}</td></tr>
        <tr><th>Antworten</th><td class="mono">{{ $receipt->answer_hash }}</td></tr>
        <tr><th>Vorherige Quittung</th><td class="mono">{{ $receipt->prev_hash ?? '–' }}</td></tr>
        <tr><th>Quittung</th><td class="mono">{{ $receipt->content_hash }}</td></tr>
    </table>
</body>
</html>
