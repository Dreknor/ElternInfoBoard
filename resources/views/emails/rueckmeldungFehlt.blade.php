<!DOCTYPE html>
<html>
<head>
    <title>Rückmeldung fehlt</title>
</head>
<body>


<p>Liebe/r {{$name}}</p>
<p>
    Im ElternInfoBoard des Schulzentrums fehlt uns Ihre Rückmeldung zum Thema <a
        href="{{url('post/'.$theme_id)}}">"{{$thema}}"</a>. Wir benötigen die
    Rückmeldung bis spätestens zum {{$ende}}.
</p>
<p>
    Diese E-Mail wird automatisch erstellt, solange keine Rückmeldung über das ElternInfoBoard erfolgt. Rückmeldungen
    per E-Mail oder in anderer Form werden dabei nicht erfasst.
</p>
<p>
    Mit freundlichen Grüßen<br>
    Ihr Team des Evangelischen Schulzentrum Radebeul
</p>

<p>
    <a href="{{config('app.url')}}">{{config('app.name')}}</a>
</p>

</body>
</html>
