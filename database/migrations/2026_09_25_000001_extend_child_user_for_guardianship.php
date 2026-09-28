<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kind-zentriertes Familienmodell (FAM-02): child_user wird zur qualifizierten
 * Beziehung Kind ↔ Bezugsperson (Rechte, Herkunft, Prüfstatus).
 *
 * Bestehende Verknüpfungen erhalten alle Rechte (heutiges Verhalten).
 * Doppelte Zeilen (child_id, user_id) werden vor dem UNIQUE-Index zusammengeführt.
 *
 * @see docs/kind-zentriertes-familienmodell-konzept.md §4.1, §12.2
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('child_user', function (Blueprint $table) {
            $table->boolean('has_custody')->default(true)->after('relation');
            $table->boolean('receives_information')->default(true)->after('has_custody');
            $table->boolean('can_manage')->default(true)->after('receives_information');
            $table->string('source', 20)->default('manual')->after('can_manage');
            $table->date('valid_until')->nullable()->after('source');
            $table->timestamp('reviewed_at')->nullable()->after('valid_until');
        });

        // Herkunft & Relation für Bestand ableiten
        DB::table('child_user')->where('is_auto_provisioned', true)->update(['source' => 'ucs']);
        DB::table('child_user')->whereNull('relation')->update(['relation' => 'legal_guardian']);

        $this->mergeDuplicates();

        Schema::table('child_user', function (Blueprint $table) {
            $table->unique(['child_id', 'user_id'], 'child_user_child_user_unique');
        });
    }

    public function down(): void
    {
        Schema::table('child_user', function (Blueprint $table) {
            $table->dropUnique('child_user_child_user_unique');
        });

        Schema::table('child_user', function (Blueprint $table) {
            $table->dropColumn(['has_custody', 'receives_information', 'can_manage', 'source', 'valid_until', 'reviewed_at']);
        });
    }

    /**
     * Behält pro (child_id, user_id) die älteste Zeile; ein Auto-Flag irgendeiner
     * Dublette bleibt erhalten.
     */
    private function mergeDuplicates(): void
    {
        $duplicates = DB::table('child_user')
            ->select('child_id', 'user_id', DB::raw('MIN(id) as keep_id'), DB::raw('MAX(is_auto_provisioned) as any_auto'))
            ->groupBy('child_id', 'user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table('child_user')
                ->where('child_id', $duplicate->child_id)
                ->where('user_id', $duplicate->user_id)
                ->where('id', '!=', $duplicate->keep_id)
                ->delete();

            if ((bool) $duplicate->any_auto) {
                DB::table('child_user')->where('id', $duplicate->keep_id)
                    ->update(['is_auto_provisioned' => true, 'source' => 'ucs']);
            }
        }
    }
};
