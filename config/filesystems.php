<?php

return [

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
            'serve' => true,
            'report' => false,
        ],

        // Mediendateien (Spatie MediaLibrary): außerhalb von public/, Abruf nur über
        // /image/{id} bzw. die App-API mit Rechteprüfung (App\Services\App\MediaAccess).
        'media' => [
            'driver' => 'local',
            'root' => storage_path('app/media'),
            'visibility' => 'private',
            'throw' => false,
            'report' => false,
        ],

        'img' => [
            'driver' => 'local',
            'root' => public_path(),
        ],
    ],

];
