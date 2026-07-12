<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Zugangsdaten – {{ $importTypeLabel }}</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #2d3748; line-height: 1.6;">
    <p>Hallo,</p>

    <p>
        im Anhang finden Sie die PDF mit den Zugangsdaten der neu angelegten Benutzer
        aus dem <strong>{{ $importTypeLabel }}</strong> ({{ $userCount }} {{ $userCount === 1 ? 'neuer Benutzer' : 'neue Benutzer' }}).
    </p>

    <p>
        Bitte behandeln Sie das Dokument vertraulich und löschen Sie es nach der Weitergabe
        der Zugangsdaten an die betroffenen Benutzer.
    </p>

    <p style="margin-top: 30px;">
        Mit freundlichen Grüßen,<br>
        {{ config('app.name') }}
    </p>
</body>
</html>
