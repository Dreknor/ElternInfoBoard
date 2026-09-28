<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Vorbereitung für die UCS- und Familienmodell-Migrationen: Altbestände mit
 * ungültigen Nulldaten („0000-00-00 00:00:00“) lassen unter MySQL/MariaDB im
 * strikten SQL-Modus jedes ALTER TABLE mit Tabellenkopie (z. B. neuer
 * Fremdschlüssel) scheitern. Betroffene Werte werden auf NULL bzw. – bei
 * Pflichtspalten – auf created_at oder den aktuellen Zeitpunkt gesetzt.
 *
 * Nur die Tabellen, die in diesem Release verändert werden. Idempotent.
 */
return new class extends Migration
{
    private const TABLES = [
        'users', 'children', 'child_user', 'groups', 'group_user', 'listen', 'listen_termine',
        'listen_eintragungen', 'rueckmeldungen', 'users_rueckmeldungen', 'abfrage_answers', 'posts',
    ];

    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        $columns = DB::table('information_schema.columns')
            ->where('table_schema', DB::getDatabaseName())
            ->whereIn('table_name', self::TABLES)
            ->whereIn('data_type', ['datetime', 'timestamp', 'date'])
            ->get(['table_name', 'column_name', 'is_nullable']);

        foreach ($columns as $column) {
            $table = $column->table_name ?? $column->TABLE_NAME;
            $name = $column->column_name ?? $column->COLUMN_NAME;
            $nullable = ($column->is_nullable ?? $column->IS_NULLABLE) === 'YES';

            if (! Schema::hasColumn($table, $name)) {
                continue;
            }

            $replacement = $nullable
                ? 'NULL'
                : ($name !== 'created_at' && Schema::hasColumn($table, 'created_at')
                    ? "COALESCE(NULLIF(CAST(created_at AS CHAR), '0000-00-00 00:00:00'), NOW())"
                    : 'NOW()');

            DB::statement("UPDATE `{$table}` SET `{$name}` = {$replacement} WHERE CAST(`{$name}` AS CHAR) LIKE '0000-00-00%'");
        }
    }

    public function down(): void
    {
        // Bereinigte Werte lassen sich nicht sinnvoll wiederherstellen.
    }
};
