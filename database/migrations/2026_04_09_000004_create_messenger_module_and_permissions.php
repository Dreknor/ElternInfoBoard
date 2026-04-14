<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        // Berechtigungen für Messenger
        $permissions = ['use messenger', 'moderate messages'];
        foreach ($permissions as $perm) {
            Permission::findOrCreate($perm, 'web');
            Permission::findOrCreate($perm, 'api');
        }

        // Modul anlegen (inaktiv per Default)
        $exists = DB::table('settings_modules')->where('setting', 'Eltern-Nachrichten')->exists();
        if (! $exists) {
            DB::table('settings_modules')->insert([
                'setting'     => 'Eltern-Nachrichten',
                'category'    => 'module',
                'description' => 'Ermöglicht Eltern den direkten Austausch untereinander. Gruppenkonversationen und 1:1-Direktnachrichten.',
                'options'     => json_encode([
                    'active' => false,
                    'nav'    => [
                        'name'       => 'Eltern-Nachrichten',
                        'link'       => 'messenger',
                        'icon'       => 'fas fa-comments',
                        'bottom-nav' => 'true',
                    ],
                    'rights' => ['use messenger'],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings_modules')->where('setting', 'Eltern-Nachrichten')->delete();

        foreach (['use messenger', 'moderate messages'] as $perm) {
            Permission::where('name', $perm)->delete();
        }
    }
};


