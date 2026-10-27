<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
</head>
<body style="margin: 0; padding: 20px; background-color: #f5f7fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: #2d3748; line-height: 1.6;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; padding: 32px 30px; text-align: center;">
            <h1 style="font-size: 24px; font-weight: 700; margin: 0;">
                {{ $change === \App\Mail\VereinMembershipChangedMail::ADDED ? 'Willkommen im Vereinsbereich' : 'Änderung Ihrer Vereinsmitgliedschaft' }}
            </h1>
        </div>
        <div style="padding: 32px 30px;">
            <p style="font-size: 18px; font-weight: 500; margin: 0 0 20px 0;">Hallo {{ $user->name }},</p>
            <p style="margin: 0 0 24px 0;">{!! nl2br(e($text)) !!}</p>
            @if($change === \App\Mail\VereinMembershipChangedMail::ADDED)
                <div style="text-align: center; margin: 30px 0;">
                    <a href="{{ url('/login') }}" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; padding: 14px 36px; border-radius: 8px; font-weight: 600;">
                        Jetzt anmelden →
                    </a>
                </div>
            @endif
            <p style="margin: 24px 0 0 0; font-weight: 500;">
                Mit freundlichen Grüßen,<br>
                Das Team von {{ config('app.name') }}
            </p>
        </div>
        <div style="background-color: #f7fafc; padding: 20px 30px; text-align: center; border-top: 1px solid #e2e8f0; color: #718096; font-size: 13px;">
            {{ config('app.name') }} · <a href="{{ url('/') }}" style="color: #667eea; text-decoration: none;">{{ url('/') }}</a>
        </div>
    </div>
</body>
</html>
