<?php

namespace App\Http\View\Composers;

use App\Model\Module;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ModulesComposer
{
    public function compose($view): void
    {
        if (!auth()->check()) {
            $view->with('modules', collect([]));
            return;
        }

        $modules = Cache::remember('modules', 30, function () {
            try {
                // Lade alle Module der Kategorie 'module' und filtere dann nach active
                $allModules = Module::where('category', 'module')->orderBy('sort_order')->orderBy('id')->get();
            } catch (\Exception $e) {
                // Fallback falls sort_order-Spalte noch nicht migriert wurde
                Log::warning('ModulesComposer: orderBy(sort_order) fehlgeschlagen, Fallback auf id-Sortierung.', [
                    'error' => $e->getMessage(),
                ]);
                $allModules = Module::where('category', 'module')->orderBy('id')->get();
            }

            return $allModules->filter(function ($module) {
                // Prüfe ob options existiert und active = 1 ist
                return isset($module->options['active']) &&
                       ($module->options['active'] === '1' || $module->options['active'] === 1 || $module->options['active'] === true);
            });
        });

        if (! isset($modules)) {
            $modules = collect([]);
        }

        $view->with('modules', $modules);
    }
}
