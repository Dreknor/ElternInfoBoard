<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{$header}}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
            line-height: 1.6;
            color: #333;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        .urgent-badge {
            display: inline-block;
            background-color: #ff4444;
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 15px;
        }
        .email-header h1 {
            font-size: 24px;
            font-weight: 600;
            margin: 0;
            line-height: 1.3;
        }
        .email-body {
            padding: 30px 25px;
        }
        .message-content {
            font-size: 15px;
            line-height: 1.8;
            color: #444;
            margin-bottom: 25px;
        }
        .message-content p {
            margin-bottom: 15px;
        }
        .message-content ul,
        .message-content ol {
            margin-left: 20px;
            margin-bottom: 15px;
        }
        .message-content a {
            color: #667eea;
            text-decoration: none;
        }
        .message-content a:hover {
            text-decoration: underline;
        }
        .attachments-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }
        .attachments-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        .attachments-title svg {
            width: 20px;
            height: 20px;
            margin-right: 8px;
        }
        .attachment-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            background-color: #f8f9fa;
            border-radius: 6px;
            margin-bottom: 10px;
            transition: background-color 0.2s;
        }
        .attachment-item:hover {
            background-color: #e9ecef;
        }
        .attachment-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            flex-shrink: 0;
        }
        .attachment-icon svg {
            width: 20px;
            height: 20px;
            fill: white;
        }
        .attachment-info {
            flex-grow: 1;
        }
        .attachment-name {
            font-weight: 500;
            color: #333;
            font-size: 14px;
            word-break: break-word;
        }
        .attachment-size {
            font-size: 12px;
            color: #666;
            margin-top: 2px;
        }
        .email-footer {
            background-color: #f8f9fa;
            padding: 25px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        .footer-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff !important;
            padding: 14px 32px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 15px;
            margin-bottom: 15px;
            transition: transform 0.2s;
        }
        .footer-button:hover {
            transform: translateY(-2px);
        }
        .footer-text {
            font-size: 13px;
            color: #666;
            margin-top: 15px;
        }
        .meta-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #666;
        }
        .meta-info strong {
            color: #333;
        }
        @media only screen and (max-width: 600px) {
            body {
                padding: 10px;
            }
            .email-header {
                padding: 20px 15px;
            }
            .email-header h1 {
                font-size: 20px;
            }
            .email-body {
                padding: 20px 15px;
            }
            .footer-button {
                padding: 12px 24px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <div class="urgent-badge">
                ⚠ Dringende Nachricht
            </div>
            <h1>{{$header}}</h1>
        </div>

        <!-- Body -->
        <div class="email-body">
            @if($post->autor)
            <div class="meta-info">
                <strong>Von:</strong> {{$post->autor->name}}<br>
                <strong>Datum:</strong> {{$post->created_at->format('d.m.Y H:i')}} Uhr
            </div>
            @endif

            <div class="message-content">
                {!! $nachricht !!}
            </div>

            @if($post->getMedia('images')->isNotEmpty())
            <div class="attachments-section">
                <div class="attachments-title">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19.5 21a3 3 0 003-3v-4.5a3 3 0 00-3-3h-15a3 3 0 00-3 3V18a3 3 0 003 3h15zM1.5 10.146V6a3 3 0 013-3h5.379a2.25 2.25 0 011.59.659l2.122 2.121c.14.141.331.22.53.22H19.5a3 3 0 013 3v1.146A4.483 4.483 0 0019.5 9h-15a4.483 4.483 0 00-3 1.146z"/>
                    </svg>
                    Angehängte Dateien ({{$post->getMedia('images')->count()}})
                </div>
                @foreach($post->getMedia('images') as $media)
                <div class="attachment-item">
                    <div class="attachment-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625z"/>
                            <path d="M12.971 1.816A5.23 5.23 0 0114.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 013.434 1.279 9.768 9.768 0 00-6.963-6.963z"/>
                        </svg>
                    </div>
                    <div class="attachment-info">
                        <div class="attachment-name">{{$media->file_name}}</div>
                        <div class="attachment-size">{{$media->human_readable_size}}</div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <a href="{{config('app.url')}}/#{{'post-'.$post->id}}" class="footer-button">
                Nachricht online ansehen
            </a>
            <div class="footer-text">
                Diese Nachricht wurde über <strong>{{config('app.name')}}</strong> versendet
            </div>
        </div>
    </div>
</body>
</html>
