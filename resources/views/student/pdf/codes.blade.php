<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Zugangscodes</title>
    <style>
        @page { margin: 12mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #111; }
        .slip { border: 1px dashed #888; padding: 8mm; margin-bottom: 6mm; page-break-inside: avoid; height: 62mm; }
        .slip table { width: 100%; border-collapse: collapse; }
        .slip td { vertical-align: top; }
        h2 { font-size: 13pt; margin: 0 0 2mm; }
        .muted { color: #555; font-size: 9pt; }
        .label { color: #555; font-size: 8.5pt; }
        .value { font-size: 12pt; font-weight: bold; font-family: DejaVu Sans Mono, monospace; }
        .qr { width: 38mm; text-align: right; }
        .qr img { width: 36mm; height: 36mm; }
    </style>
</head>
<body>
@foreach($slips as $slip)
    <div class="slip">
        <table>
            <tr>
                <td>
                    <h2>{{ $appName }} – dein Zugang</h2>
                    <div class="muted">{{ $slip['name'] }} · {{ \Illuminate\Support\Str::startsWith((string) $slip['klasse'], 'Klasse') ? $slip['klasse'] : 'Klasse '.$slip['klasse'] }}</div>
                    <p style="margin: 4mm 0 1mm" class="label">Benutzername</p>
                    <div class="value">{{ $slip['username'] }}</div>
                    <p style="margin: 3mm 0 1mm" class="label">Einmal-Code (gültig bis {{ $slip['valid_until'] }})</p>
                    <div class="value">{{ $slip['code'] }}</div>
                    <p class="muted" style="margin-top: 4mm">QR-Code scannen oder {{ $loginUrl }} öffnen, Code eingeben und ein eigenes Passwort festlegen. Den Code nicht weitergeben.</p>
                </td>
                <td class="qr"><img src="{{ $slip['qr'] }}" alt=""></td>
            </tr>
        </table>
    </div>
@endforeach
</body>
</html>
