<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Je Kind darf pro Tag nur eine Anwesenheitsabfrage (ChildCheckIn) bestehen.
 *
 * Bereits vorhandene Duplikate werden zusammengeführt: Behalten wird der zuletzt geänderte
 * Eintrag (aktuellste Daten), nur leere Felder werden aus den übrigen Einträgen ergänzt. Verweise (verspätete Abholungen, Erinnerungs-Logs)
 * werden auf den verbleibenden Eintrag umgehängt. Anschließend sichert ein Unique-Index die Regel ab.
 */
return new class extends Migration
{
    private const INDEX_NAME = 'child_check_ins_child_id_date_unique';

    public function up(): void
    {
        $this->mergeDuplicates();

        // Der Index kann bereits manuell per SQL angelegt worden sein
        if (Schema::hasIndex('child_check_ins', self::INDEX_NAME)) {
            return;
        }

        Schema::table('child_check_ins', function (Blueprint $table) {
            $table->unique(['child_id', 'date'], self::INDEX_NAME);
        });
    }

    public function down(): void
    {
        Schema::table('child_check_ins', function (Blueprint $table) {
            $table->dropUnique(self::INDEX_NAME);
        });
    }

    private function mergeDuplicates(): void
    {
        $groups = DB::table('child_check_ins')
            ->selectRaw('child_id, DATE(date) as day')
            ->groupBy('child_id', DB::raw('DATE(date)'))
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($groups as $group) {
            DB::transaction(function () use ($group) {
                $rows = DB::table('child_check_ins')
                    ->where('child_id', $group->child_id)
                    ->whereDate('date', $group->day)
                    ->orderBy('id')
                    ->get();

                // Der zuletzt geänderte Eintrag enthält die aktuellsten Daten (z. B. die letzte
                // Rückmeldung der Eltern) und wird behalten; bei gleichem Zeitstempel der neuere Eintrag.
                $sorted = $rows->sort(fn ($a, $b) => [(string) $b->updated_at, $b->id]
                    <=> [(string) $a->updated_at, $a->id])->values();
                $keeper = $sorted->first();
                $duplicates = $sorted->slice(1); // ebenfalls nach letzter Änderung sortiert

                $merged = [];
                foreach (['should_be', 'comment', 'lock_at', 'checked_in_at', 'checked_out_at'] as $field) {
                    if ($keeper->{$field} === null) {
                        $value = $duplicates->pluck($field)->first(fn ($value) => $value !== null);
                        if ($value !== null) {
                            $merged[$field] = $value;
                        }
                    }
                }
                foreach (['checked_in', 'checked_out'] as $field) {
                    if (! $keeper->{$field} && $duplicates->contains(fn ($row) => (bool) $row->{$field})) {
                        $merged[$field] = true;
                    }
                }

                if (! empty($merged)) {
                    DB::table('child_check_ins')->where('id', $keeper->id)->update($merged);
                }

                $duplicateIds = $duplicates->pluck('id')->all();

                if (Schema::hasTable('late_pickups')) {
                    DB::table('late_pickups')
                        ->whereIn('child_check_in_id', $duplicateIds)
                        ->update(['child_check_in_id' => $keeper->id]);
                }

                if (Schema::hasTable('reminder_logs')) {
                    DB::table('reminder_logs')
                        ->where('remindable_type', \App\Model\ChildCheckIn::class)
                        ->whereIn('remindable_id', $duplicateIds)
                        ->update(['remindable_id' => $keeper->id]);
                }

                DB::table('child_check_ins')->whereIn('id', $duplicateIds)->delete();

                Log::info('Doppelte Anwesenheitsabfragen zusammengeführt.', [
                    'child_id' => $group->child_id,
                    'date' => $group->day,
                    'kept_id' => $keeper->id,
                    'deleted_ids' => $duplicateIds,
                ]);
            });
        }
    }
};
