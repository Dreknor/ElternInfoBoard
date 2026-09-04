<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        \Illuminate\Support\Facades\DB::table('settings')->insert([
            [
                'group' => 'care',
                'name' => 'auto_checkin_enabled_schulzeit',
                'locked' => false,
                'payload' => 'true',
            ],
            [
                'group' => 'care',
                'name' => 'auto_checkin_time_schulzeit',
                'locked' => false,
                'payload' => '"08:30"',
            ],
            [
                'group' => 'care',
                'name' => 'auto_checkin_enabled_ferien',
                'locked' => false,
                'payload' => 'false',
            ],
            [
                'group' => 'care',
                'name' => 'auto_checkin_time_ferien',
                'locked' => false,
                'payload' => '"08:30"',
            ],
        ]);
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::table('settings')
            ->where('group', 'care')
            ->whereIn('name', [
                'auto_checkin_enabled_schulzeit',
                'auto_checkin_time_schulzeit',
                'auto_checkin_enabled_ferien',
                'auto_checkin_time_ferien',
            ])
            ->delete();
    }
};
