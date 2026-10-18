<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>{{ $inquiryTitle }}</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">

<p>Guten Tag {{ $userName }},</p>

@if($kind === \App\Mail\ElternabfrageMail::KIND_CONFIRMATION)
    <p>
        vielen Dank! Ihre Angaben zur Abfrage <strong>„{{ $inquiryTitle }}“</strong> für <strong>{{ $childName }}</strong>
        sind am <strong>{{ $submittedAt }}</strong> eingegangen (Antwortversion {{ $version }}).
    </p>
    <p>Die eingereichte Zusammenfassung und eine PDF-Bestätigung finden Sie jederzeit im {{ $boardName }}.</p>
@elseif($kind === \App\Mail\ElternabfrageMail::KIND_REMINDER)
    <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px 16px; margin-bottom: 20px; border-radius: 4px;">
        <strong style="color: #92400e;">Ihre Rückmeldung fehlt noch</strong>
    </div>
    <p>
        im {{ $boardName }} wartet die Abfrage <strong>„{{ $inquiryTitle }}“</strong> für <strong>{{ $childName }}</strong>
        noch auf Ihre Angaben. Bitte füllen Sie sie bis zum <strong>{{ $deadline }}</strong> aus.
    </p>
    <p style="color: #6b7280; font-size: 0.875rem;">Wurde die Abfrage inzwischen vom anderen Elternteil beantwortet, ist nichts weiter zu tun.</p>
@else
    <p>
        im {{ $boardName }} gibt es eine neue Abfrage <strong>„{{ $inquiryTitle }}“</strong> für <strong>{{ $childName }}</strong>.
        Bitte füllen Sie sie bis zum <strong>{{ $deadline }}</strong> aus.
    </p>
    @if($purpose)
        <p style="background-color: #f3f4f6; padding: 12px 16px; border-radius: 4px;">
            <strong>Wofür werden die Angaben benötigt?</strong><br>
            {!! nl2br(e($purpose)) !!}
        </p>
    @endif
@endif

<p style="margin-top: 24px;">
    <a href="{{ $url }}"
       style="display: inline-block; background-color: #2563eb; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;">
        {{ $kind === \App\Mail\ElternabfrageMail::KIND_CONFIRMATION ? 'Eingereichte Angaben ansehen' : 'Zur Abfrage' }}
    </a>
</p>

<p style="color: #6b7280; font-size: 0.875rem; margin-top: 24px;">
    Der Link führt nach der Anmeldung direkt zur Abfrage. Antworten per E-Mail werden nicht erfasst.
</p>

<p>
    Mit freundlichen Grüßen<br>
    <a href="{{ config('app.url') }}" style="color: #2563eb; text-decoration: underline;">{{ $boardName }}</a>
</p>
</body>
</html>
