<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Termin-Erinnerung</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #f97316 0%, #dc2626 100%);
            color: white;
            padding: 30px;
            border-radius: 10px 10px 0 0;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            background: #f9fafb;
            padding: 30px;
            border: 1px solid #e5e7eb;
            border-top: none;
        }
        .event-details {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #f97316;
        }
        .event-details h2 {
            margin-top: 0;
            color: #111827;
        }
        .detail-row {
            margin: 10px 0;
            display: flex;
            align-items: start;
        }
        .detail-row .icon {
            width: 24px;
            margin-right: 10px;
            color: #f97316;
        }
        .reminder-badge {
            background: #fef3c7;
            color: #92400e;
            padding: 8px 16px;
            border-radius: 20px;
            display: inline-block;
            font-weight: bold;
            margin: 10px 0;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #f97316 0%, #dc2626 100%);
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            color: #6b7280;
            font-size: 12px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>⏰ Termin-Erinnerung</h1>
    </div>

    <div class="content">
        <p>Hallo,</p>

        <div class="reminder-badge">
            📅 In {{ $hoursUntil }} Stunden
        </div>

        <p>Dies ist eine Erinnerung an den bevorstehenden Termin:</p>

        <div class="event-details">
            <h2>{{ $event->title }}</h2>

            @if($event->description)
                <p style="color: #4b5563;">{{ $event->description }}</p>
            @endif

            <div class="detail-row">
                <span class="icon">📅</span>
                <div>
                    <strong>Datum:</strong><br>
                    {{ $event->start_time->format('d.m.Y') }}
                </div>
            </div>

            <div class="detail-row">
                <span class="icon">🕐</span>
                <div>
                    <strong>Uhrzeit:</strong><br>
                    {{ $event->start_time->format('H:i') }} - {{ $event->end_time->format('H:i') }} Uhr
                </div>
            </div>

            @if($event->location)
                <div class="detail-row">
                    <span class="icon">📍</span>
                    <div>
                        <strong>Ort:</strong><br>
                        {{ $event->location }}
                    </div>
                </div>
            @endif

            <div class="detail-row">
                <span class="icon">👤</span>
                <div>
                    <strong>Organisator:</strong><br>
                    {{ $event->creator->name }}
                </div>
            </div>
        </div>

        <center>
            <a href="{{ url('elternrat/events') }}" class="button">
                Zum Terminkalender
            </a>
        </center>

        <p style="margin-top: 30px; font-size: 14px; color: #6b7280;">
            <strong>Teilnahmestatus:</strong><br>
            Falls Sie noch nicht zugesagt haben, können Sie dies direkt im Terminkalender tun.
        </p>
    </div>

    <div class="footer">
        <p>Diese E-Mail wurde automatisch vom Elternrat-System versendet.</p>
        <p>{{ config('app.name') }}</p>
    </div>
</body>
</html>

