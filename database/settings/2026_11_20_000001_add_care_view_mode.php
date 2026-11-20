<?php

use Illuminate\Support\Facades\DB;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Ansicht der Anwesenheitsliste: einfache Liste, Detailansicht oder Kachelansicht.
 * Bestehende Installationen behalten ihre bisherige Ansicht (view_detailed_care).
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $payload = DB::table('settings')
            ->where('group', 'care')
            ->where('name', 'view_detailed_care')
            ->value('payload');

        $detailed = $payload !== null && json_decode($payload) === true;

        $this->migrator->add('care.view_mode', $detailed ? 'detailed' : 'simple');
    }
};
