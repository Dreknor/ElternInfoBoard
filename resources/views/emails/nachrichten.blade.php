<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="de">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Aktuelle Informationen</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f5f7fa; font-family: Arial, Helvetica, sans-serif;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f5f7fa;">
        <tr>
            <td style="padding: 20px 10px;">
                <!-- Container -->
                <table align="center" border="0" cellpadding="0" cellspacing="0" width="600" style="border-collapse: collapse; background-color: #ffffff; max-width: 600px;">
                    <!-- Header -->
                    <tr>
                        <td bgcolor="#667eea" style="padding: 40px 30px; text-align: center; color: #ffffff;">
                            <h1 style="margin: 0; padding: 0; font-size: 28px; font-weight: bold;">📬 Aktuelle Informationen</h1>
                            <p style="margin: 10px 0 0 0; font-size: 16px;">Ihre Benachrichtigung von {{config('app.name')}}</p>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 30px;">
                            <!-- Greeting -->
                            <p style="margin: 0 0 25px 0; font-size: 18px; color: #2d3748;">Liebe/r {{$name}},</p>

                            @if(count($nachrichten) > 0)
                            <!-- Neue Nachrichten Section -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 30px; border: 1px solid #e2e8f0;">
                                <tr>
                                    <td bgcolor="#f7fafc" style="padding: 15px 20px; border-bottom: 2px solid #667eea;">
                                        <h2 style="margin: 0; padding: 0; font-size: 18px; font-weight: 600; color: #667eea;">📋 Neue Nachrichten</h2>
                                    </td>
                                </tr>
                                @foreach($nachrichten as $nachricht)
                                <tr>
                                    <td style="padding: 15px 20px; border-bottom: 1px solid #e2e8f0;">
                                        <div style="font-weight: 500; color: #2d3748; margin-bottom: 5px;">
                                            <a href="{{ url('post/'.$nachricht->id) }}" target="_blank" style="color: #667eea; text-decoration: none;">{{$nachricht->header}}</a>
                                        </div>
                                        @if(isset($nachricht->created_at))
                                        <div style="font-size: 14px; color: #718096;">{{ \Carbon\Carbon::parse($nachricht->created_at)->format('d.m.Y H:i') }} Uhr</div>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </table>
                            @endif

                            @if(count($nachrichten_extern) > 0)
                            <!-- Externe Angebote Section -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 30px; border: 1px solid #e2e8f0;">
                                <tr>
                                    <td bgcolor="#f7fafc" style="padding: 15px 20px; border-bottom: 2px solid #667eea;">
                                        <h2 style="margin: 0; padding: 0; font-size: 18px; font-weight: 600; color: #667eea;">🌐 Externe Angebote</h2>
                                    </td>
                                </tr>
                                @foreach($nachrichten_extern as $nachricht)
                                <tr>
                                    <td style="padding: 15px 20px; border-bottom: 1px solid #e2e8f0;">
                                        <div style="font-weight: 500; color: #2d3748; margin-bottom: 5px;">
                                            <a href="{{ url('post/'.$nachricht->id) }}" target="_blank" style="color: #667eea; text-decoration: none;">{{$nachricht->header}}</a>
                                        </div>
                                        @if(isset($nachricht->created_at))
                                        <div style="font-size: 14px; color: #718096;">{{ \Carbon\Carbon::parse($nachricht->created_at)->format('d.m.Y H:i') }} Uhr</div>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </table>
                            @endif

                            @if(count($discussionen) > 0)
                            <!-- Elternratsbereich Section -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 30px; border: 1px solid #e2e8f0;">
                                <tr>
                                    <td bgcolor="#f7fafc" style="padding: 15px 20px; border-bottom: 2px solid #667eea;">
                                        <h2 style="margin: 0; padding: 0; font-size: 18px; font-weight: 600; color: #667eea;">💬 Elternratsbereich</h2>
                                    </td>
                                </tr>
                                @foreach($discussionen as $Diskussion)
                                <tr>
                                    <td style="padding: 15px 20px; border-bottom: 1px solid #e2e8f0;">
                                        <div style="font-weight: 500; color: #2d3748;">{{$Diskussion->header}}</div>
                                    </td>
                                </tr>
                                @endforeach
                            </table>
                            @endif

                            @if(isset($listen) && count($listen) > 0)
                            <!-- Listen Section -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 30px; border: 1px solid #e2e8f0;">
                                <tr>
                                    <td bgcolor="#f7fafc" style="padding: 15px 20px; border-bottom: 2px solid #667eea;">
                                        <h2 style="margin: 0; padding: 0; font-size: 18px; font-weight: 600; color: #667eea;">📝 Veröffentlichte Listen</h2>
                                    </td>
                                </tr>
                                @foreach($listen as $liste)
                                <tr>
                                    <td style="padding: 15px 20px; border-bottom: 1px solid #e2e8f0;">
                                        <div style="font-weight: 500; color: #2d3748;">{{$liste->listenname}}</div>
                                    </td>
                                </tr>
                                @endforeach
                            </table>
                            @endif

                            @if(isset($termine) && count($termine) > 0)
                            <!-- Termine Section -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 30px; border: 1px solid #e2e8f0;">
                                <tr>
                                    <td bgcolor="#f7fafc" style="padding: 15px 20px; border-bottom: 2px solid #667eea;">
                                        <h2 style="margin: 0; padding: 0; font-size: 18px; font-weight: 600; color: #667eea;">📅 Neue Termine</h2>
                                    </td>
                                </tr>
                                @foreach($termine as $termin)
                                <tr>
                                    <td style="padding: 15px 20px; border-bottom: 1px solid #e2e8f0;">
                                        <div style="font-weight: 500; color: #2d3748; margin-bottom: 5px;">{{$termin->terminname}}</div>
                                        <div style="font-size: 14px; color: #718096;">
                                            @if($termin->start->day != $termin->ende->day)
                                                {{$termin->start->format('d.m.')}} - {{$termin->ende->format('d.m.Y')}}
                                            @else
                                                {{$termin->start->format('d.m.Y')}}
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </table>
                            @endif

                            @if(isset($gta) && count($gta) > 0)
                            <!-- GTA Section -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 30px; border: 1px solid #e2e8f0;">
                                <tr>
                                    <td bgcolor="#f7fafc" style="padding: 15px 20px; border-bottom: 2px solid #667eea;">
                                        <h2 style="margin: 0; padding: 0; font-size: 18px; font-weight: 600; color: #667eea;">🎯 GTA Angebote</h2>
                                    </td>
                                </tr>
                                @foreach($gta as $g)
                                <tr>
                                    <td style="padding: 15px 20px; border-bottom: 1px solid #e2e8f0;">
                                        <div style="font-weight: 500; color: #2d3748;">{{$g->name}}</div>
                                    </td>
                                </tr>
                                @endforeach
                            </table>
                            @endif

                            <!-- CTA Section -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-top: 30px;">
                                <tr>
                                    <td bgcolor="#f7fafc" style="padding: 25px; text-align: center;">
                                        <p style="margin: 0 0 20px 0; font-size: 15px; color: #4a5568;">🔐 Melden Sie sich an, um alle Details und weitere Funktionen zu nutzen.</p>
                                        <table border="0" cellpadding="0" cellspacing="0" align="center">
                                            <tr>
                                                <td bgcolor="#667eea" style="padding: 14px 32px; text-align: center;">
                                                    <a href="{{url('/nachrichten')}}" target="_blank" style="font-size: 16px; font-weight: 600; color: #ffffff; text-decoration: none; display: inline-block;">Alle Nachrichten ansehen</a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td bgcolor="#f7fafc" style="padding: 25px 30px; text-align: center;">
                            <p style="margin: 0; font-size: 14px; color: #718096;">
                                Diese E-Mail wurde automatisch versendet von<br>
                                <a href="{{config('app.url')}}" style="color: #667eea; text-decoration: none;">{{config('app.name')}}</a>
                            </p>
                            <p style="margin: 15px 0 0 0; font-size: 12px; color: #718096;">
                                &copy; {{ date('Y') }} {{config('app.name')}}. Alle Rechte vorbehalten.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
