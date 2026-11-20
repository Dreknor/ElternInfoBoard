<?php

namespace App\Support;

/**
 * Pfad, unter dem die Anwendung im Browser erreichbar ist – leer bei Betrieb in der
 * Domain-Wurzel, sonst z. B. „/elterninfoboard/public“ (ohne abschließenden Schrägstrich).
 *
 * Ermittelt aus der aktuellen Anfrage (SCRIPT_NAME), damit es auch hinter einem
 * Rewrite stimmt. In der Konsole leitet Laravel die Anfrage aus APP_URL ab.
 */
class BasePath
{
    public static function get(): string
    {
        return rtrim((string) request()->getBasePath(), '/');
    }

    /**
     * URL des Livewire-Scripts. Livewire bindet es sonst fest als „/livewire/livewire.min.js“
     * (Domain-Wurzel) ein; die Route selbst bleibt unverändert.
     */
    public static function livewireScriptUrl(): string
    {
        return static::get().(config('app.debug') ? '/livewire/livewire.js' : '/livewire/livewire.min.js');
    }
}
