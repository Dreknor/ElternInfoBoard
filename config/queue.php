<?php

return [

    'use_cronjob' => env('USE_CRONJOB', false),

    /*
    | Speicherlimit (MB) des per Cronjob gestarteten Workers. Beim Erreichen beendet
    | sich der Worker mit Exit-Code 12; offene Jobs übernimmt der nächste Lauf.
    */
    'cronjob_memory' => (int) env('QUEUE_WORKER_MEMORY', 256),

];
