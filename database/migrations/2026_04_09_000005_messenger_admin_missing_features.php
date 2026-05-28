<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. has_chat-Spalte zu groups hinzufügen (Gruppen-Chat pro Gruppe aktivierbar)
        if (! Schema::hasColumn('groups', 'has_chat')) {
            Schema::table('groups', function (Blueprint $table) {
                $table->boolean('has_chat')->default(false)->after('protected');
            });
        }

        // 2. adm-nav-Eintrag zum Eltern-Nachrichten-Modul hinzufügen
        $module = DB::table('settings_modules')->where('setting', 'Eltern-Nachrichten')->first();
        if ($module) {
            $options = json_decode($module->options, true);
            // Nur setzen wenn noch nicht vorhanden (idempotent)
            if (! isset($options['adm-nav'])) {
                $options['adm-nav'] = [
                    'name'       => 'Nachrichten-Moderation',
                    'link'       => 'messenger/admin/reports',
                    'icon'       => 'fas fa-shield-alt',
                    'adm-rights' => ['moderate messages'],
                ];
                DB::table('settings_modules')
                    ->where('setting', 'Eltern-Nachrichten')
                    ->update(['options' => json_encode($options)]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('has_chat');
        });

        $module = DB::table('settings_modules')->where('setting', 'Eltern-Nachrichten')->first();
        if ($module) {
            $options = json_decode($module->options, true);
            unset($options['adm-nav']);
            DB::table('settings_modules')
                ->where('setting', 'Eltern-Nachrichten')
                ->update(['options' => json_encode($options)]);
        }
    }
};

