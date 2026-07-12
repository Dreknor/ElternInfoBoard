<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Zugangsdaten Import</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; color: #222; }
        h1 { font-size: 14pt; margin-bottom: 4px; }
        p.meta { font-size: 9pt; color: #666; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th { background-color: #3a5a8c; color: #fff; padding: 7px 10px; text-align: left; font-size: 10pt; }
        td { padding: 6px 10px; border-bottom: 1px solid #ddd; font-size: 10pt; }
        tr:nth-child(even) td { background-color: #f5f7fa; }
        .hint { margin-top: 18px; font-size: 9pt; color: #555; border-top: 1px solid #ccc; padding-top: 8px; }
    </style>
</head>
<body>
    <h1>Zugangsdaten – Import vom {{ \Carbon\Carbon::now()->format('d.m.Y H:i') }} Uhr</h1>
    <p class="meta">Import-Typ: {{ $importType }} &nbsp;|&nbsp; Neue Benutzer: {{ count($users) }}</p>

    @if(count($users) > 0)
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>E-Mail / Benutzername</th>
                    <th>Erstkennwort</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $i => $u)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $u['name'] }}</td>
                        <td>{{ $u['email'] }}</td>
                        <td><strong>{{ $u['password'] }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>Es wurden keine neuen Benutzer angelegt.</p>
    @endif

    <p class="hint">
        Die Benutzer müssen ihr Kennwort beim ersten Login ändern.
        Bitte dieses Dokument vertraulich behandeln und nach Aushändigung vernichten.
    </p>
</body>
</html>
