<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        \Illuminate\Support\Facades\DB::table('settings')->insert([
            [
                'group' => 'care',
                'name' => 'show_parents',
                'locked' => false,
                'payload' => 'false',
            ],
        ]);
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::table('settings')
            ->where('group', 'care')
            ->where('name', 'show_parents')
            ->delete();
    }
};
