<?php

return [

    'channels' => [
        'database' => [
            'driver' => 'custom',
            'via' => danielme85\LaravelLogToDB\LogToDbHandler::class,
        ],
    ],

];
