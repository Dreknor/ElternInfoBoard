<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Zugangsdaten Import</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; color: #222; }
        h1 { font-size: 16pt; margin-bottom: 4px; color: #3a5a8c; }
        p.meta { font-size: 9pt; color: #666; margin-top: 0; margin-bottom: 24px; }
        .page {
            page-break-after: always;
        }
        .page:last-child {
            page-break-after: auto;
        }
        .letter p { margin: 0 0 12px 0; line-height: 1.5; }
        .credentials-box {
            margin: 24px 0;
            border: 1px solid #cbd5e0;
            border-left: 4px solid #3a5a8c;
            border-radius: 4px;
            padding: 16px 20px;
            background-color: #f5f7fa;
        }
        .credentials-box h2 { font-size: 12pt; margin: 0 0 12px 0; color: #3a5a8c; }
        .credential-item { margin: 8px 0; }
        .credential-label { font-weight: bold; color: #4a5568; font-size: 10pt; }
        .credential-value {
            font-family: DejaVu Sans Mono, monospace;
            font-size: 11pt;
            background: #fff;
            border: 1px solid #cbd5e0;
            border-radius: 3px;
            padding: 5px 8px;
            display: inline-block;
            margin-top: 3px;
        }
        .hint { margin-top: 24px; font-size: 9pt; color: #555; border-top: 1px solid #ccc; padding-top: 8px; }
    </style>
</head>
<body>
    @if(count($users) > 0)
        @foreach($users as $i => $u)
            <div class="page">
                <h1>Ihre Zugangsdaten für {{ config('app.name') }}</h1>
                <p class="meta">Import-Typ: {{ $importType }} &nbsp;|&nbsp; {{ \Carbon\Carbon::now()->format('d.m.Y H:i') }} Uhr</p>

                <div class="letter">
                    <p>Hallo {{ $u['name'] }},</p>
                    <p>
                        für Sie wurde ein neues Benutzerkonto bei {{ config('app.name') }} angelegt.
                        Nachfolgend finden Sie Ihre persönlichen Zugangsdaten für die erste Anmeldung.
                    </p>
                </div>

                <div class="credentials-box">
                    <h2>Ihre Zugangsdaten</h2>
                    <div class="credential-item">
                        <div class="credential-label">Benutzername / E-Mail:</div>
                        <div class="credential-value">{{ $u['email'] }}</div>
                    </div>
                    <div class="credential-item">
                        <div class="credential-label">Startkennwort:</div>
                        <div class="credential-value">{{ $u['password'] }}</div>
                    </div>
                </div>

                <div class="letter">
                    <p>
                        Bitte melden Sie sich zeitnah an und ändern Sie Ihr Kennwort bei der ersten
                        Anmeldung. Geben Sie Ihr Kennwort niemals an Dritte weiter.
                    </p>
                    <p>Mit freundlichen Grüßen,<br>{{ config('app.name') }}</p>
                </div>

                <p class="hint">
                    Bitte dieses Dokument nach Aushändigung der Zugangsdaten vertraulich behandeln
                    und anschließend vernichten.
                </p>
            </div>
        @endforeach
    @else
        <p>Es wurden keine neuen Benutzer angelegt.</p>
    @endif
</body>
</html>
