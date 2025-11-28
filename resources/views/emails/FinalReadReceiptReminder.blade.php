<!DOCTYPE html>
<html>
<head>
    <title>WICHTIG: Lesebestätigung erforderlich</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .important {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
        .content {
            background-color: #f8f9fa;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>

<p>Liebe/r {{$name}},</p>

<div class="important">
    <strong>⚠️ WICHTIG: Lesebestätigung fehlt</strong>
</div>

<p>
    Sie haben die Nachricht <strong>"{{$thema}}"</strong> im {{$BoardName}} noch nicht bestätigt.
    Die Frist zur Bestätigung läuft am <strong>{{$ende}}</strong> ab.
</p>

<div class="content">
    <h3>Inhalt der Nachricht:</h3>
    <p>{{$content}}</p>
</div>

<p>
    <a href="{{url('post/'.$theme_id)}}" class="button">Jetzt bestätigen</a>
</p>

<p>
    <strong>Hinweis:</strong> Bitte bestätigen Sie diese Nachricht über das Online-System.
    Diese E-Mail wird automatisch erstellt, solange keine Bestätigung online erfolgt.
    Rückmeldungen per E-Mail werden nicht erfasst.
</p>

<p>
    Mit freundlichen Grüßen<br>
    <a href="{{config('app.url')}}">{{config('app.name')}}</a>
</p>

</body>
</html>

