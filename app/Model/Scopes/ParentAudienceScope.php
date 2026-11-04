<?php

namespace App\Model\Scopes;

use App\Support\StudentAccounts;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Schema;

/**
 * Beiträge und Termine „nur für Schüler“ fehlen in jeder Abfrage (docs/konzept-schuelerkonten.md §3).
 *
 * Eltern sehen `zielgruppe = schueler` nie: nicht in Listen, Archiv, Suche, Newsletter, Push,
 * Lesebestätigungen, Erinnerungen, API oder MCP-Server. Das gilt unabhängig davon, ob das Modul
 * gerade aktiv ist. Aufgehoben wird der Scope nur bewusst: Schüleransicht (`StudentPostQuery`),
 * Verwaltung durch Autor bzw. Berechtigte (`withStudentAudience()`).
 */
class ParentAudienceScope implements Scope
{
    /** @var array<string, bool> */
    private static array $columnKnown = [];

    public function apply(Builder $builder, Model $model): void
    {
        if (! self::columnExists($model)) {
            return;
        }

        $builder->where($model->qualifyColumn('zielgruppe'), '!=', StudentAccounts::ZIELGRUPPE_SCHUELER);
    }

    private static function columnExists(Model $model): bool
    {
        $table = $model->getTable();

        if (self::$columnKnown[$table] ?? false) {
            return true;
        }

        return self::$columnKnown[$table] = Schema::connection($model->getConnectionName())
            ->hasColumn($table, 'zielgruppe');
    }
}
