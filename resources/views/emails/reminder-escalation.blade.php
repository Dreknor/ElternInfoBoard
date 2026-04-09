<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Eskalation: {{ $typeLabel }} überfällig</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">

<div style="background-color: #fef2f2; border-left: 4px solid #ef4444; padding: 12px 16px; margin-bottom: 20px; border-radius: 4px;">
    <strong style="color: #991b1b;">🔴 Eskalation: {{ $typeLabel }} überfällig</strong>
</div>

<p>Liebe/r {{ $authorName }},</p>

<p>
    die {{ $typeLabel }} zum Thema
    <a href="{{ url('post/'.$postId) }}" style="color: #2563eb; text-decoration: underline;">„{{ $postTitle }}"</a>
    ist trotz mehrfacher Erinnerung noch nicht von <strong>{{ $userName }}</strong> beantwortet worden.
</p>

<p>
    Die Frist war am <strong>{{ $deadline }}</strong>.
</p>

<p>
    Sie können die ausstehenden Rückmeldungen hier einsehen:
</p>

<p style="margin-top: 16px;">
    <a href="{{ url('post/'.$postId) }}"
       style="display: inline-block; background-color: #dc2626; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;">
        Nachricht öffnen
    </a>
</p>

<p style="color: #6b7280; font-size: 0.875rem; margin-top: 24px;">
    Diese Eskalations-E-Mail wird automatisch vom {{ $boardName }} gesendet, wenn eine Pflicht-{{ $typeLabel }}
    nach Fristablauf nicht beantwortet wurde.
</p>

<p>
    Mit freundlichen Grüßen<br>
    {{ $boardName }}
</p>

<hr style="border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;">
<p style="font-size: 0.75rem; color: #9ca3af;">
    <a href="{{ config('app.url') }}" style="color: #6b7280;">{{ config('app.name') }}</a>
</p>

</body>
</html>

