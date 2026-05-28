<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('settings_modules')->where('setting', 'Familienübersicht')->exists();

        if (! $exists) {
            DB::table('settings_modules')->insert([
                'setting'     => 'Familienübersicht',
                'category'    => 'module',
                'description' => 'Persönlicher Wochenplan für Eltern: aggregiert Stundenplan, Vertretungen, GTAs, Schickzeiten, Krankmeldungen und Termine kindsbezogen in einer Wochenübersicht.',
                    'options'     => json_encode([
                    'active' => true,
                    'nav'    => [
                        'name'       => 'Wochenplan',
                        'link'       => 'wochenplan',
                        'icon'       => 'fas fa-calendar-week',
                        'bottom-nav' => 'true',
                    ],
                    'rights' => [],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings_modules')->where('setting', 'Familienübersicht')->delete();
    }
};

