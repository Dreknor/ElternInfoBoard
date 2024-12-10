<!DOCTYPE html>
<html>
<head>
    <title>Lesebestätigung fehlt</title>
</head>
<body>


<p>Liebe/r {{$name}}</p>
<p>
    Im {{$BoardName}} fehlt uns Ihre Lesebestätigung zum Thema <a
        href="{{url('post/'.$theme_id)}}">"{{$thema}}"</a>. Wir benötigen die
    Lesebestätigung bis spätestens zum {{$ende}}.
</p>
<p>
    Diese E-Mail wird automatisch erstellt, solange keine Rückmeldung online erfolgt. Rückmeldungen
    per E-Mail oder in anderer Form werden dabei nicht erfasst.
</p>

<p>
    <a href="{{config('app.url')}}">{{config('app.name')}}</a>
</p>

</body>
</html>
