<?php

return [

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'img' => [
            'driver' => 'local',
            'root' => public_path(),
        ],
    ],

];
