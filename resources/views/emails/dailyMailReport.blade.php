<!DOCTYPE html>
<html>
<head>
    <title>Übersicht Nachrichten</title>
</head>
<body>
<p>Folgende Nachrichten wurden versandt:</p>
<p>
    @foreach($mails as $mail)
        {{$mail->created_at->format('d.m.Y H:i')}}: <br>
        <b>Von:</b> {{$mail->sender->name}} <br>
        <b>An:</b> {{$mail->to}} <br>
        <b>Betreff:</b> {{$mail->subject}}<br><br>
    @endforeach
</p>

</body>
</html>
