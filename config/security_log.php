<?php

/*
|--------------------------------------------------------------------------
| Sicherheitsprotokoll
|--------------------------------------------------------------------------
|
| Zweckbindung: Das Protokoll dient AUSSCHLIESSLICH der Datensicherheit
| (Art. 32 DSGVO / § 27 DSG-EKD, berechtigtes Interesse): Erkennen und
| Nachweisen unbefugter Zugriffe, z. B. fehlgeschlagene Anmeldungen, Exporte
| und Downloads. Es wird nicht zur Leistungs- oder Verhaltenskontrolle
| ausgewertet. Es werden keine Passwörter, Tokens oder Inhalte gespeichert.
|
*/

return [

    /** Protokollierung insgesamt ein-/ausschalten. */
    'enabled' => (bool) env('SECURITY_LOG_ENABLED', true),

    /** Aufbewahrung in Tagen; danach werden Einträge gelöscht (security-log:cleanup). */
    'retention_days' => (int) env('SECURITY_LOG_RETENTION_DAYS', 90),

    /** Nach dieser Zeit wird die IP-Adresse gekürzt und der User-Agent entfernt. */
    'anonymize_ip_after_days' => (int) env('SECURITY_LOG_ANONYMIZE_IP_AFTER_DAYS', 30),

];
