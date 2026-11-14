<?php

/*
|--------------------------------------------------------------------------
| Datei-Uploads
|--------------------------------------------------------------------------
| Erlaubte Dateitypen für Uploads von Nutzern (Beiträge, Rückmeldungen,
| Messenger, Krankmeldungen, Downloads, Elternrat, Infoseiten).
|
| Geprüft werden die Endung des Dateinamens UND der aus dem Inhalt ermittelte
| Typ (App\Support\Uploads). Aktive Inhalte (HTML, SVG, Skripte, ausführbare
| Dateien) sind bewusst nicht enthalten. Zusätzlich sperrt App\Support\UploadGuard
| solche Dateien beim Anlegen jedes Media-Eintrags und härtet die Auslieferung.
*/

return [

    // Bilder, die hochgeladen werden dürfen
    'images' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'heic', 'heif'],

    // Weitere Dokumente/Medien (zusätzlich zu den Bildern), anpassbar über UPLOAD_ALLOWED_EXTENSIONS
    'documents' => array_values(array_filter(array_map('trim', explode(',', (string) env(
        'UPLOAD_ALLOWED_EXTENSIONS',
        'pdf,doc,docx,dot,dotx,xls,xlsx,ppt,pptx,pps,ppsx,odt,ods,odp,odg,rtf,txt,csv,ics,vcf,zip,'
        .'mp3,m4a,wav,ogg,mp4,m4v,mov,avi,webm,tif,tiff,epub,pages,numbers,key'
    ))))),
];
