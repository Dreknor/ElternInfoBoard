<?php

namespace App\Services\CustomScripts;

use App\Model\CustomScript;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Wird eine Rolle gelöscht und war sie die letzte Rolle eines auf Rollen begrenzten
 * Skripts, wird das Skript deaktiviert – sonst würde es beim erneuten Speichern
 * versehentlich allen angemeldeten Konten ausgespielt.
 *
 * Auch ohne dieses Ereignis (z. B. Löschen direkt in der Datenbank) bekommt niemand
 * ein begrenztes Skript ohne Rollen, siehe CustomScript::matchesUser().
 */
class DisableScriptsOnRoleDeletion
{
    public function handle(Model $role): void
    {
        try {
            $scripts = CustomScript::query()
                ->where('restrict_roles', true)
                ->whereHas('roles', fn ($query) => $query->whereKey($role->getKey()))
                ->withCount('roles')
                ->get();
        } catch (\Throwable $e) {
            // Tabelle fehlt (Migration noch nicht gelaufen): nichts zu tun
            return;
        }

        foreach ($scripts as $script) {
            if ($script->roles_count > 1) {
                continue;
            }

            $wasActive = $script->active;

            $script->active = false;
            if ($wasActive) {
                $script->disabled_reason = sprintf(
                    'Automatisch deaktiviert: Rolle „%s“ wurde gelöscht (%s).',
                    $role->getAttribute('name'),
                    now()->timezone(config('app.timezone'))->format('d.m.Y')
                );
            }
            $script->saveQuietly();

            Log::warning('Eigenes Skript wegen gelöschter Rolle deaktiviert', [
                'custom_script_id' => $script->id,
                'name' => $script->name,
                'role_id' => $role->getKey(),
                'role' => $role->getAttribute('name'),
                'was_active' => $wasActive,
            ]);
        }
    }
}
