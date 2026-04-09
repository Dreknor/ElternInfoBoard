<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Erinnerung</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">

@if($level === 3)
    <div style="background-color: #fee2e2; border-left: 4px solid #ef4444; padding: 12px 16px; margin-bottom: 20px; border-radius: 4px;">
        <strong style="color: #991b1b;">⚠ Frist abgelaufen</strong>
    </div>
@elseif($level === 2)
    <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px 16px; margin-bottom: 20px; border-radius: 4px;">
        <strong style="color: #92400e;">⏰ Frist läuft bald ab</strong>
    </div>
@endif

<p>Liebe/r {{ $userName }},</p>

@if($type === 'rueckmeldung')
    <p>
        im {{ $boardName }} fehlt uns Ihre Rückmeldung zum Thema
        <a href="{{ url('post/'.$postId) }}" style="color: #2563eb; text-decoration: underline;">„{{ $postTitle }}"</a>.
    </p>

    @if($level === 1)
        <p>Wir möchten Sie freundlich daran erinnern, dass Ihre Rückmeldung bis zum <strong>{{ $deadline }}</strong> benötigt wird.</p>
    @elseif($level === 2)
        <p>Die Frist für Ihre Rückmeldung läuft am <strong>{{ $deadline }}</strong> ab. Bitte geben Sie Ihre Antwort zeitnah ab.</p>
    @else
        <p>Die Frist für Ihre Rückmeldung ist am <strong>{{ $deadline }}</strong> abgelaufen. Bitte geben Sie Ihre Antwort umgehend ab.</p>
    @endif

@elseif($type === 'lesebestaetigung')
    <p>
        im {{ $boardName }} fehlt uns Ihre Lesebestätigung zum Thema
        <a href="{{ url('post/'.$postId) }}" style="color: #2563eb; text-decoration: underline;">„{{ $postTitle }}"</a>.
    </p>

    @if($level === 1)
        <p>Bitte bestätigen Sie die Kenntnisnahme bis zum <strong>{{ $deadline }}</strong>.</p>
    @elseif($level === 2)
        <p>Die Frist für Ihre Lesebestätigung läuft am <strong>{{ $deadline }}</strong> ab. Bitte bestätigen Sie zeitnah.</p>
    @else
        <p>Die Frist ist am <strong>{{ $deadline }}</strong> abgelaufen. Bitte bestätigen Sie die Kenntnisnahme umgehend.</p>
    @endif

@elseif($type === 'anwesenheit')
    <p>
        im {{ $boardName }} gibt es eine offene Anwesenheitsabfrage, die Ihre Rückmeldung erfordert.
    </p>

    @if($level === 1)
        <p>Bitte geben Sie Ihre Antwort bis zum <strong>{{ $deadline }}</strong> ab.</p>
    @elseif($level === 2)
        <p>Die Frist für die Anwesenheitsabfrage läuft am <strong>{{ $deadline }}</strong> ab. Bitte antworten Sie zeitnah.</p>
    @else
        <p>Die Frist ist am <strong>{{ $deadline }}</strong> abgelaufen. Bitte geben Sie Ihre Antwort umgehend ab.</p>
    @endif
@endif

<p style="margin-top: 24px;">
    <a href="{{ url($type === 'anwesenheit' ? 'schickzeiten' : 'post/'.$postId) }}"
       style="display: inline-block; background-color: #2563eb; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;">
        @if($type === 'anwesenheit')
            Zur Anwesenheitsabfrage
        @elseif($type === 'lesebestaetigung')
            Lesebestätigung abgeben
        @else
            Rückmeldung abgeben
        @endif
    </a>
</p>

<p style="color: #6b7280; font-size: 0.875rem; margin-top: 24px;">
    Diese E-Mail wird automatisch erstellt, solange keine Rückmeldung über das {{ $boardName }} erfolgt.
    Rückmeldungen per E-Mail oder in anderer Form werden dabei nicht erfasst.
</p>

<p>
    Mit freundlichen Grüßen<br>
    Ihr Team des Evangelischen Schulzentrum Radebeul
</p>

<hr style="border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;">
<p style="font-size: 0.75rem; color: #9ca3af;">
    <a href="{{ config('app.url') }}" style="color: #6b7280;">{{ config('app.name') }}</a>
</p>

</body>
</html>

