<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        // Eigene Rechte für die zwei Support-Ticket-Einstiegspunkte:
        // - "use support widget"    -> schwebender Support-Button (unten rechts)
        // - "create support ticket" -> Button im Hilfe-Fenster ("Support-Ticket erstellen")
        $permissions = ['use support widget', 'create support ticket'];

        foreach ($permissions as $perm) {
            Permission::findOrCreate($perm, 'web');
            Permission::findOrCreate($perm, 'api');
        }
    }

    public function down(): void
    {
        foreach (['use support widget', 'create support ticket'] as $perm) {
            Permission::where('name', $perm)->delete();
        }
    }
};
