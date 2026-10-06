<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Benachrichtigungskanäle je Nutzer und Kategorie (App-Push, Browser-Push, E-Mail).
 * Fehlt eine Zeile, gelten die Standardwerte (alles an = bisheriges Verhalten).
 * Bisherige App-Schalter aus `user_app_settings.settings.push.<kategorie>` werden übernommen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('category', 30);
            $table->boolean('app')->default(true);
            $table->boolean('web')->default(true);
            $table->boolean('mail')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'category']);
            $table->index(['category', 'app']);
        });

        if (! Schema::hasTable('user_app_settings')) {
            return;
        }

        $now = now();
        DB::table('user_app_settings')->orderBy('id')->chunk(500, function ($rows) use ($now) {
            $insert = [];
            foreach ($rows as $row) {
                $push = json_decode($row->settings ?? '', true)['push'] ?? null;
                if (! is_array($push)) {
                    continue;
                }
                foreach (['nachrichten', 'termine', 'messenger', 'hort'] as $category) {
                    if (($push[$category] ?? null) === false) {
                        $insert[] = [
                            'user_id' => $row->user_id, 'category' => $category,
                            'app' => false, 'web' => true, 'mail' => true,
                            'created_at' => $now, 'updated_at' => $now,
                        ];
                    }
                }
            }
            if ($insert) {
                DB::table('notification_preferences')->insertOrIgnore($insert);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
