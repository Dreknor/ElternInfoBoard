<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Eigene Skripte: statt eines einzelnen Snippets im Setting `custom_scripts`
 * beliebig viele Skripte mit Seitenregel und Zielgruppe (Kontoart, Rollen).
 *
 * Ein bisher hinterlegtes Snippet wird als "Übernommenes Script" angelegt, damit
 * nach dem Update nichts verloren geht. Im Setting bleiben nur die globalen
 * Werte für den App-Button (app_enabled, app_label).
 */
return new class extends Migration
{
    /** Setting-Felder, die in die Tabelle wandern. */
    private const MOVED_SETTINGS = ['active', 'snippet', 'position', 'show_for_guests', 'updated_by', 'updated_at'];

    public function up(): void
    {
        Schema::create('custom_scripts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('description', 500)->nullable();
            $table->boolean('active')->default(false);
            $table->text('snippet');
            $table->string('position', 10)->default('body');
            // all | only | except
            $table->string('page_mode', 10)->default('all');
            // Pfadmuster, eines pro Zeile
            $table->text('page_patterns')->nullable();
            $table->boolean('for_guests')->default(false);
            $table->boolean('for_users')->default(true);
            $table->boolean('for_students')->default(false);
            $table->boolean('restrict_roles')->default(false);
            $table->boolean('use_on_chat')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('disabled_reason', 255)->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        $rolesTable = config('permission.table_names.roles', 'roles');

        Schema::create('custom_script_role', function (Blueprint $table) use ($rolesTable) {
            $table->foreignId('custom_script_id')->constrained('custom_scripts')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained($rolesTable)->cascadeOnDelete();
            $table->primary(['custom_script_id', 'role_id']);
        });

        $this->migrateLegacySnippet();
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_script_role');
        Schema::dropIfExists('custom_scripts');

        if (! Schema::hasTable('settings')) {
            return;
        }

        // Setting-Felder mit Standardwerten wiederherstellen (Inhalte gehen verloren)
        $defaults = [
            'active' => false, 'snippet' => '', 'position' => 'body',
            'show_for_guests' => false, 'updated_by' => null, 'updated_at' => null,
        ];
        foreach ($defaults as $name => $value) {
            DB::table('settings')->updateOrInsert(
                ['group' => 'custom_scripts', 'name' => $name],
                ['locked' => false, 'payload' => json_encode($value), 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    private function migrateLegacySnippet(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $legacy = DB::table('settings')
            ->where('group', 'custom_scripts')
            ->whereIn('name', self::MOVED_SETTINGS)
            ->pluck('payload', 'name')
            ->map(fn ($payload) => json_decode((string) $payload, true));

        $snippet = trim((string) ($legacy['snippet'] ?? ''));

        if ($snippet !== '') {
            $position = in_array($legacy['position'] ?? 'body', ['head', 'body'], true) ? $legacy['position'] : 'body';

            DB::table('custom_scripts')->insert([
                'name' => 'Übernommenes Script',
                'description' => 'Automatisch aus der bisherigen Einzel-Einstellung übernommen.',
                'active' => (bool) ($legacy['active'] ?? false),
                'snippet' => $snippet,
                'position' => $position,
                'page_mode' => 'all',
                'page_patterns' => null,
                'for_guests' => (bool) ($legacy['show_for_guests'] ?? false),
                'for_users' => true,
                'for_students' => false,
                'restrict_roles' => false,
                // Bisher hat /chat das Snippet immer geladen – Verhalten beibehalten
                'use_on_chat' => true,
                'sort_order' => 0,
                'updated_by' => is_numeric($legacy['updated_by'] ?? null) ? (int) $legacy['updated_by'] : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('settings')
            ->where('group', 'custom_scripts')
            ->whereIn('name', self::MOVED_SETTINGS)
            ->delete();
    }
};
