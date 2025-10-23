<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ihr Zugang zu {{ config('app.name') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f5f7fa;
            color: #2d3748;
            line-height: 1.6;
            padding: 20px;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .header-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 25px;
            color: #2d3748;
            font-weight: 500;
        }
        .credentials-box {
            background: linear-gradient(135deg, #f6f8fb 0%, #e9ecef 100%);
            border-left: 4px solid #667eea;
            border-radius: 8px;
            padding: 25px;
            margin: 30px 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        .credentials-box h2 {
            color: #667eea;
            font-size: 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .credential-item {
            margin: 15px 0;
            display: flex;
            flex-direction: column;
        }
        .credential-label {
            font-weight: 600;
            color: #4a5568;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .credential-value {
            font-size: 16px;
            color: #2d3748;
            font-family: 'Courier New', monospace;
            background: #ffffff;
            padding: 12px 15px;
            border-radius: 6px;
            border: 1px solid #cbd5e0;
            word-break: break-all;
        }
        .button-container {
            text-align: center;
            margin: 35px 0;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            text-decoration: none;
            padding: 15px 40px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 4px 6px rgba(102, 126, 234, 0.3);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 10px rgba(102, 126, 234, 0.4);
        }
        .info-box {
            background-color: #fff5e6;
            border-left: 4px solid #ffa726;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        .info-box h3 {
            color: #e65100;
            font-size: 16px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .info-box ul {
            list-style: none;
            padding-left: 0;
        }
        .info-box li {
            padding: 8px 0;
            padding-left: 25px;
            position: relative;
            color: #5d4037;
        }
        .info-box li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #ffa726;
            font-weight: bold;
        }
        .footer {
            background-color: #f7fafc;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        .footer p {
            color: #718096;
            font-size: 14px;
            margin: 8px 0;
        }
        .footer a {
            color: #667eea;
            text-decoration: none;
            word-break: break-all;
        }
        .divider {
            height: 1px;
            background: linear-gradient(to right, transparent, #cbd5e0, transparent);
            margin: 30px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="header-icon">🎉</div>
            <h1>Willkommen bei {{ config('app.name') }}!</h1>
            <p>Ihr Benutzerkonto wurde erfolgreich erstellt</p>
        </div>

        <div class="content">
            <div class="greeting">
                Hallo {{ $user->name }},
            </div>

            <p style="margin-bottom: 20px;">
                {!! $welcomeText !!}
            </p>

            <div class="credentials-box">
                <h2>🔐 Ihre Zugangsdaten</h2>
                <div class="credential-item">
                    <span class="credential-label">Benutzername / E-Mail:</span>
                    <span class="credential-value">{{ $user->email }}</span>
                </div>
                <div class="credential-item">
                    <span class="credential-label">Startkennwort:</span>
                    <span class="credential-value">{{ $password }}</span>
                </div>
            </div>

            <div class="button-container">
                <a href="{{ url('/login') }}" class="button">
                    Jetzt anmelden →
                </a>
            </div>

            <div class="divider"></div>

            <div class="info-box">
                <h3>⚠️ Wichtige Hinweise</h3>
                <ul>
                    <li>Bitte ändern Sie Ihr Kennwort nach der ersten Anmeldung</li>
                    <li>Bewahren Sie Ihre Zugangsdaten sicher auf</li>
                    <li>Geben Sie Ihr Kennwort niemals an Dritte weiter</li>
                    <li>Bei Problemen wenden Sie sich an den Administrator</li>
                </ul>
            </div>

            <p style="margin-top: 30px; color: #4a5568;">
                Bei Fragen oder Problemen stehen wir Ihnen gerne zur Verfügung.
            </p>

            <p style="margin-top: 20px; font-weight: 500;">
                Mit freundlichen Grüßen,<br>
                Das Team von {{ config('app.name') }}
            </p>
        </div>

        <div class="footer">
            <p><strong>{{ config('app.name') }}</strong></p>
            <p>Falls der Button nicht funktioniert, kopieren Sie bitte diese URL in Ihren Browser:</p>
            <p><a href="{{ url('/login') }}">{{ url('/login') }}</a></p>
        </div>
    </div>
</body>
</html>

