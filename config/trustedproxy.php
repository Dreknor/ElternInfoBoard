<?php

/*
|--------------------------------------------------------------------------
| Vertrauenswürdige Proxys
|--------------------------------------------------------------------------
|
| Hinter einem Reverse-Proxy oder Load-Balancer sieht die Anwendung sonst nur
| dessen IP-Adresse (z. B. im Sicherheitsprotokoll und beim Rate-Limit).
| TRUSTED_PROXIES: kommagetrennte IP-Adressen/CIDR-Bereiche oder „*“
| (jedem direkten Gegenüber vertrauen – nur, wenn die Anwendung ausschließlich
| über den Proxy erreichbar ist). Leer = keinem Proxy vertrauen (Standard).
|
| Angewendet in bootstrap/app.php (->booted), da .env beim Aufbau der
| Middleware noch nicht geladen ist.
|
*/

$proxies = array_values(array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', '')))));

return [
    'proxies' => in_array('*', $proxies, true) ? '*' : $proxies,
];
