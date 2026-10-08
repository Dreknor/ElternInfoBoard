<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>{{ $betreff }}</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">

<p>Liebe/r {{ $userName }},</p>

<p>{{ $einleitung }}</p>

<table style="width: 100%; border-collapse: collapse; margin: 16px 0;">
    <tr>
        <th style="text-align: left; border-bottom: 2px solid #e5e7eb; padding: 6px;">Woche</th>
        <th style="text-align: left; border-bottom: 2px solid #e5e7eb; padding: 6px;">Aufgabe</th>
    </tr>
    @foreach($einsaetze as $einsatz)
        <tr>
            <td style="vertical-align: top; border-bottom: 1px solid #e5e7eb; padding: 6px; white-space: nowrap;">{{ $einsatz['woche'] }}</td>
            <td style="vertical-align: top; border-bottom: 1px solid #e5e7eb; padding: 6px;">
                <strong>{{ $einsatz['aufgabe'] }}</strong>
                @foreach($einsatz['bemerkungen'] as $punkt)
                    <br>&#9744; {{ $punkt }}
                @endforeach
            </td>
        </tr>
    @endforeach
</table>

<p style="margin-top: 24px;">
    <a href="{{ url('reinigung') }}"
       style="display: inline-block; background-color: #2563eb; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;">
        Zum Reinigungsplan
    </a>
</p>

<p>
    Mit freundlichen Grüßen<br>
    <a href="{{ config('app.url') }}" style="color: #2563eb; text-decoration: underline;">{{ $boardName ?: config('app.name') }}</a>
</p>

<hr style="border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;">
<p style="font-size: 0.75rem; color: #9ca3af;">
    Sie erhalten diese E-Mail, weil Benachrichtigungen per E-Mail für „Reinigung, Pflichtstunden &amp; AGs" aktiviert sind.
    Dies können Sie in Ihren Einstellungen unter Benachrichtigungen ändern.
</p>

</body>
</html>
