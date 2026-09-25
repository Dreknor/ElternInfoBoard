<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Familien-Resolver
    |--------------------------------------------------------------------------
    |
    | Steuert, wie Familien und der Zugriff auf Kinder bestimmt werden:
    |
    | - "legacy":        bisheriges Verhalten über users.sorg2 (Partner erbt
    |                    die Kinder des verknüpften Kontos)
    | - "child_centric": Familien über users.family_id, Zugriff auf Kinder nur
    |                    über eine direkte Beziehung (child_user) mit Rechten
    |
    | Umschalten ist ohne Deploy möglich und dient zugleich als Rollback.
    |
    | @see docs/kind-zentriertes-familienmodell-konzept.md §5.1, §11, §14
    */
    'resolver' => env('FAMILY_RESOLVER', 'legacy'),

    /*
    | Während der Übergangsphase wird users.sorg2 für Familien mit genau zwei
    | Mitgliedern mitgepflegt, damit ein Rollback auf "legacy" möglich bleibt.
    */
    'dual_write_sorg2' => env('FAMILY_DUAL_WRITE_SORG2', true),
];
