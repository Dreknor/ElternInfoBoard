<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        \Illuminate\Support\Facades\DB::table('settings')->insert([
            [
                'group' => 'care',
                'name' => 'mandate_notification_enabled',
                'locked' => false,
                'payload' => 'false',
            ],
            [
                'group' => 'care',
                'name' => 'mandate_notification_email',
                'locked' => false,
                'payload' => 'null',
            ],
        ]);
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::table('settings')
            ->where('group', 'care')
            ->whereIn('name', ['mandate_notification_enabled', 'mandate_notification_email'])
            ->delete();
    }
};
