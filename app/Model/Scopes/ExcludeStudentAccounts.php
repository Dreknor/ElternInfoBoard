<?php

namespace App\Model\Scopes;

use App\Model\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Schema;

/**
 * Schülerkonten fehlen in jeder Nutzerabfrage (docs/konzept-schuelerkonten.md §1, §5).
 *
 * Dadurch erscheinen sie in keinem Verteiler, keiner Auswahlliste, keinem Pool und keiner
 * Statistik, auch nicht über Relationen wie `$group->users` oder `$post->users`. Neue
 * Funktionen sind damit automatisch ohne Schüler. Bewusst aufgehoben wird der Scope nur über
 * `User::withStudents()` bzw. `User::students()` (Anmeldung, Schülerverwaltung, Schüleransicht).
 */
class ExcludeStudentAccounts implements Scope
{
    private static bool $columnKnown = false;

    public function apply(Builder $builder, Model $model): void
    {
        if (! self::columnExists($model)) {
            return;
        }

        $builder->where($model->qualifyColumn('account_type'), '!=', User::ACCOUNT_STUDENT);
    }

    /**
     * Ältere Migrationen nutzen das Model, bevor die Spalte existiert. Nur ein positives
     * Ergebnis wird gemerkt, damit ein frisch migrierter Testlauf den Scope trotzdem anwendet.
     */
    private static function columnExists(Model $model): bool
    {
        if (self::$columnKnown) {
            return true;
        }

        return self::$columnKnown = Schema::connection($model->getConnectionName())
            ->hasColumn($model->getTable(), 'account_type');
    }
}
