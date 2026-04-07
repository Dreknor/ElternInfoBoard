<!DOCTYPE html>
<html>
<head>
    <title>Neues Bild</title>
</head>
<body>
<h2>
    {{$von}} hat ein neues Bild hochgeladen
</h2>

<p>Das Bild wurde der Nachricht "{{$betreff}}" hinzugefügt.</p>

<p>
    <a href="{{url('post/'.$postId)}}">Nachricht direkt ansehen</a>
</p>

<p>
    <a href="{{config('app.url')}}">{{config('app.name')}}</a>
</p>
</body>
</html>
