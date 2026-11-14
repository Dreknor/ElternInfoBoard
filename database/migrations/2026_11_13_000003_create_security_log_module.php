<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Menüeintrag „Sicherheitsprotokoll“ (nur für Nutzer mit dem Recht „see security log“). */
    public function up(): void
    {
        if (DB::table('settings_modules')->where('setting', 'Sicherheitsprotokoll')->exists()) {
            return;
        }

        DB::table('settings_modules')->insert([
            'setting' => 'Sicherheitsprotokoll',
            'category' => 'module',
            'description' => 'Zeigt das Sicherheitsprotokoll (Anmeldungen, Exporte, Downloads) nur lesend an.',
            'options' => json_encode([
                'active' => '1',
                'rights' => [],
                'adm-nav' => [
                    'adm-rights' => ['see security log'],
                    'name' => 'Sicherheitsprotokoll',
                    'link' => 'security-log',
                    'icon' => 'fas fa-shield-alt',
                ],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('settings_modules')->where('setting', 'Sicherheitsprotokoll')->delete();
    }
};
