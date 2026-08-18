<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Erinnerung Reinigungsdienst</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">

<p>Liebe/r {{ $userName }},</p>

<p>
    im {{ $boardName }} erinnern wir Sie an Ihren anstehenden Reinigungsdienst
    in der Woche <strong>{{ $woche }}</strong>{{ $bereich && $bereich !== \App\Model\Reinigung::BEREICH_GESAMT ? ' im Bereich „'.$bereich.'"' : '' }}.
</p>

<p>
    Aufgabe: <strong>{{ $aufgabe }}</strong>
</p>

<p style="margin-top: 24px;">
    <a href="{{ url('reinigung') }}"
       style="display: inline-block; background-color: #2563eb; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;">
        Zum Reinigungsplan
    </a>
</p>

<p>
    Mit freundlichen Grüßen<br>
    <a href="{{ config('app.url') }}" style="color: #2563eb; text-decoration: underline;">{{ config('app.name') }}</a>
</p>

<hr style="border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;">
<p style="font-size: 0.75rem; color: #9ca3af;">
    <a href="{{ config('app.url') }}" style="color: #6b7280;">{{ config('app.name') }}</a>
</p>

</body>
</html>
