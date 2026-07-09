<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neue Abholvollmacht</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f0f4f8;
            padding: 20px;
            line-height: 1.6;
            color: #333;
        }
        .email-container {
            max-width: 620px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }
        .email-header {
            background: linear-gradient(135deg, #2d6a4f 0%, #40916c 100%);
            color: #ffffff;
            padding: 32px 28px;
            text-align: center;
        }
        .email-header .badge {
            display: inline-block;
            background-color: rgba(255,255,255,0.2);
            color: #fff;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 14px;
        }
        .email-header h1 {
            font-size: 22px;
            font-weight: 700;
            margin: 0;
            line-height: 1.4;
        }
        .email-body {
            padding: 32px 28px;
        }
        .intro-text {
            font-size: 15px;
            color: #555;
            margin-bottom: 24px;
        }
        .section-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #40916c;
            margin-bottom: 10px;
            margin-top: 24px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .info-table tr:first-child th,
        .info-table tr:first-child td {
            border-top: none;
        }
        .info-table th {
            background-color: #f8fafb;
            color: #555;
            font-weight: 600;
            padding: 11px 16px;
            text-align: left;
            width: 40%;
            border-top: 1px solid #e2e8f0;
            white-space: nowrap;
        }
        .info-table td {
            padding: 11px 16px;
            color: #333;
            border-top: 1px solid #e2e8f0;
        }
        .info-table tr:nth-child(even) td {
            background-color: #fdfdfd;
        }
        .highlight-row th,
        .highlight-row td {
            background-color: #f0fdf4 !important;
            font-weight: 600;
        }
        .divider {
            border: none;
            border-top: 1px solid #e9ecef;
            margin: 28px 0;
        }
        .email-footer {
            background-color: #f8fafb;
            padding: 22px 28px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        .footer-text {
            font-size: 12px;
            color: #999;
            line-height: 1.6;
        }
        .footer-text strong {
            color: #555;
        }
        @media only screen and (max-width: 600px) {
            body { padding: 10px; }
            .email-header, .email-body, .email-footer { padding: 20px 16px; }
            .email-header h1 { font-size: 19px; }
            .info-table th { width: 45%; }
        }
    </style>
</head>
<body>
<div class="email-container">

    <div class="email-header">
        <div class="badge">&#128100; Abholvollmacht</div>
        <h1>Neue Abholvollmacht eingetragen</h1>
    </div>

    <div class="email-body">
        <p class="intro-text">
            Im <strong>{{ config('app.name') }}</strong> wurde soeben eine neue Abholvollmacht erfasst.
            Bitte prüfen Sie die nachfolgenden Angaben.
        </p>

        <div class="section-title">Kind</div>
        <table class="info-table">
            <tr class="highlight-row">
                <th>Name</th>
                <td>{{ $child->first_name }} {{ $child->last_name }}</td>
            </tr>
            @if($child->group)
            <tr>
                <th>Gruppe</th>
                <td>{{ $child->group->name }}</td>
            </tr>
            @endif
        </table>

        <div class="section-title">Bevollmächtigte Person</div>
        <table class="info-table">
            <tr class="highlight-row">
                <th>Name</th>
                <td>{{ $mandate->mandate_name }}</td>
            </tr>
            @if($mandate->mandate_description)
            <tr>
                <th>Beschreibung / Hinweis</th>
                <td>{{ $mandate->mandate_description }}</td>
            </tr>
            @endif
        </table>

        <div class="section-title">Erfassung</div>
        <table class="info-table">
            <tr>
                <th>Eingetragen am</th>
                <td>{{ $mandate->created_at->format('d.m.Y') }} um {{ $mandate->created_at->format('H:i') }} Uhr</td>
            </tr>
            @if($creator)
            <tr>
                <th>Erfasst von</th>
                <td>{{ $creator->name }}</td>
            </tr>
            @if($creator->email)
            <tr>
                <th>E-Mail</th>
                <td><a href="mailto:{{ $creator->email }}" style="color:#40916c;">{{ $creator->email }}</a></td>
            </tr>
            @endif
            @endif
        </table>
    </div>

    <div class="email-footer">
        <p class="footer-text">
            Diese Benachrichtigung wurde automatisch durch <strong>{{ config('app.name') }}</strong> versandt.<br>
            Bitte antworten Sie nicht direkt auf diese E-Mail.
        </p>
    </div>

</div>
</body>
</html>
