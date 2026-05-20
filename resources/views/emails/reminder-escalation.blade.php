<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Eskalation: {{ $typeLabel }} überfällig</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">

<div style="background-color: #fef2f2; border-left: 4px solid #ef4444; padding: 12px 16px; margin-bottom: 20px; border-radius: 4px;">
    <strong style="color: #991b1b;">🔴 Erinnerung: {{ count($userNames) }} ausstehende {{ $typeLabel }}{{ count($userNames) !== 1 ? 'en' : '' }}</strong>
</div>

<p>Liebe/r {{ $authorName }},</p>

<p>
    für den Beitrag
    <a href="{{ url('post/'.$postId) }}" style="color: #2563eb; text-decoration: underline;">„{{ $postTitle }}"</a>
    haben trotz mehrfacher Erinnerung noch <strong>{{ count($userNames) }} {{ count($userNames) === 1 ? 'Person' : 'Personen' }}</strong>
    keine {{ $typeLabel }} eingereicht.
    Die Frist war am <strong>{{ $deadline }}</strong>.
</p>

<p><strong>Ausstehende {{ $typeLabel }}en:</strong></p>

<table style="width: 100%; border-collapse: collapse; margin: 8px 0 20px;">
    @foreach ($userNames as $index => $name)
        <tr style="background-color: {{ $index % 2 === 0 ? '#fafafa' : '#ffffff' }};">
            <td style="padding: 8px 12px; border: 1px solid #e5e7eb;">{{ $index + 1 }}.</td>
            <td style="padding: 8px 12px; border: 1px solid #e5e7eb;">{{ $name }}</td>
        </tr>
    @endforeach
</table>

<p style="margin-top: 16px;">
    <a href="{{ url('post/'.$postId) }}"
       style="display: inline-block; background-color: #dc2626; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;">
        Nachricht öffnen &amp; Übersicht einsehen
    </a>
</p>

<p style="color: #6b7280; font-size: 0.875rem; margin-top: 24px;">
    Diese Erinnerungs-E-Mail wird automatisch vom {{ $boardName }} gesendet, wenn Pflicht-{{ $typeLabel }}en
    nach Fristablauf nicht beantwortet wurden. Sie erhalten pro Beitrag nur eine E-Mail mit allen ausstehenden Personen.
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
