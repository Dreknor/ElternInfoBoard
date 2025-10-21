<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktuelle Informationen</title>
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
        .header p {
            font-size: 16px;
            opacity: 0.95;
        }
        .content {
            padding: 30px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 25px;
            color: #2d3748;
        }
        .section {
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }
        .section-header {
            background-color: #f7fafc;
            padding: 15px 20px;
            border-bottom: 2px solid #667eea;
        }
        .section-header h2 {
            font-size: 18px;
            font-weight: 600;
            color: #667eea;
            display: flex;
            align-items: center;
        }
        .section-header h2:before {
            content: "📋";
            margin-right: 10px;
            font-size: 20px;
        }
        .section-header.external h2:before {
            content: "🌐";
        }
        .section-header.discussions h2:before {
            content: "💬";
        }
        .section-header.lists h2:before {
            content: "📝";
        }
        .section-header.events h2:before {
            content: "📅";
        }
        .section-header.gta h2:before {
            content: "🎯";
        }
        .section-body {
            padding: 0;
        }
        .item {
            padding: 15px 20px;
            border-bottom: 1px solid #e2e8f0;
            transition: background-color 0.2s;
        }
        .item:last-child {
            border-bottom: none;
        }
        .item:hover {
            background-color: #f7fafc;
        }
        .item-title {
            font-weight: 500;
            color: #2d3748;
            margin-bottom: 5px;
        }
        .item-date {
            font-size: 14px;
            color: #718096;
        }
        .cta-section {
            margin-top: 30px;
            padding: 25px;
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border-radius: 8px;
            text-align: center;
        }
        .cta-text {
            font-size: 15px;
            color: #4a5568;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            padding: 14px 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .footer {
            padding: 25px 30px;
            background-color: #f7fafc;
            text-align: center;
            font-size: 14px;
            color: #718096;
        }
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
        @media only screen and (max-width: 600px) {
            .email-container {
                border-radius: 0;
            }
            .header {
                padding: 30px 20px;
            }
            .content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>📬 Aktuelle Informationen</h1>
            <p>Ihre Benachrichtigung von {{config('app.name')}}</p>
        </div>

        <div class="content">
            <div class="greeting">
                Liebe/r {{$name}},
            </div>

            @if(count($nachrichten) > 0)
            <div class="section">
                <div class="section-header">
                    <h2>Neue Nachrichten</h2>
                </div>
                <div class="section-body">
                    @foreach($nachrichten as $nachricht)
                    <div class="item">
                        <div class="item-title">{{$nachricht->header}}</div>
                        @if(isset($nachricht->created_at))
                        <div class="item-date">{{ \Carbon\Carbon::parse($nachricht->created_at)->format('d.m.Y H:i') }} Uhr</div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if(count($nachrichten_extern) > 0)
            <div class="section">
                <div class="section-header external">
                    <h2>Externe Angebote</h2>
                </div>
                <div class="section-body">
                    @foreach($nachrichten_extern as $nachricht)
                    <div class="item">
                        <div class="item-title">{{$nachricht->header}}</div>
                        @if(isset($nachricht->created_at))
                        <div class="item-date">{{ \Carbon\Carbon::parse($nachricht->created_at)->format('d.m.Y H:i') }} Uhr</div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if(count($discussionen) > 0)
            <div class="section">
                <div class="section-header discussions">
                    <h2>Elternratsbereich</h2>
                </div>
                <div class="section-body">
                    @foreach($discussionen as $Diskussion)
                    <div class="item">
                        <div class="item-title">{{$Diskussion->header}}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if(isset($listen) && count($listen) > 0)
            <div class="section">
                <div class="section-header lists">
                    <h2>Veröffentlichte Listen</h2>
                </div>
                <div class="section-body">
                    @foreach($listen as $liste)
                    <div class="item">
                        <div class="item-title">{{$liste->listenname}}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if(isset($termine) && count($termine) > 0)
            <div class="section">
                <div class="section-header events">
                    <h2>Neue Termine</h2>
                </div>
                <div class="section-body">
                    @foreach($termine as $termin)
                    <div class="item">
                        <div class="item-title">{{$termin->terminname}}</div>
                        <div class="item-date">
                            @if($termin->start->day != $termin->ende->day)
                                {{$termin->start->format('d.m.')}} - {{$termin->ende->format('d.m.Y')}}
                            @else
                                {{$termin->start->format('d.m.Y')}}
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if(isset($gta) && count($gta) > 0)
            <div class="section">
                <div class="section-header gta">
                    <h2>GTA Angebote</h2>
                </div>
                <div class="section-body">
                    @foreach($gta as $g)
                    <div class="item">
                        <div class="item-title">{{$g->name}}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="cta-section">
                <p class="cta-text">
                    🔐 Melden Sie sich an, um alle Details und weitere Funktionen zu nutzen.
                </p>
                <a href="{{config('app.url')}}" class="btn">Jetzt anmelden</a>
            </div>
        </div>

        <div class="footer">
            <p>
                Diese E-Mail wurde automatisch versendet von<br>
                <a href="{{config('app.url')}}">{{config('app.name')}}</a>
            </p>
            <p style="margin-top: 15px; font-size: 12px;">
                © {{ date('Y') }} {{config('app.name')}}. Alle Rechte vorbehalten.
            </p>
        </div>
    </div>
</body>
</html>
