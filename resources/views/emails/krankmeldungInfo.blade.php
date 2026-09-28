<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Krankmeldung eingegangen</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background-color: #f0f4f8; padding: 20px; color: #333; line-height: 1.6;">
    <div style="max-width: 620px; margin: 0 auto; background: #ffffff; border-radius: 10px; overflow: hidden;">
        <div style="background: #2563eb; color: #ffffff; padding: 24px 28px;">
            <h1 style="font-size: 20px; margin: 0;">Krankmeldung für {{ $child->first_name }} {{ $child->last_name }}</h1>
        </div>
        <div style="padding: 24px 28px;">
            <p>Hallo {{ $recipientName }},</p>
            <p>
                {{ $reporterName }} hat {{ $child->first_name }} für den Zeitraum <strong>{{ $zeitraum }}</strong> krankgemeldet.
                Die Schule ist bereits informiert – eine weitere Krankmeldung ist nicht nötig.
            </p>
            <p>
                <a href="{{ url('krankmeldung') }}" style="display: inline-block; background: #2563eb; color: #ffffff; padding: 10px 18px; border-radius: 6px; text-decoration: none;">Krankmeldungen ansehen</a>
            </p>
            <p style="font-size: 12px; color: #6b7280;">
                Sie erhalten diese Nachricht, weil Sie als Bezugsperson von {{ $child->first_name }} eingetragen sind.
            </p>
        </div>
    </div>
</body>
</html>
