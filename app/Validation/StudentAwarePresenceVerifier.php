<?php

namespace App\Validation;

use App\Model\User;
use Illuminate\Validation\DatabasePresenceVerifier;

/**
 * `exists:users,…` und `unique:users,…` sehen keine Schülerkonten (docs/konzept-schuelerkonten.md §5.1, S-04).
 *
 * Formulare der Eltern- und Verwaltungsseiten prüfen Benutzer-IDs per `exists:users,id`. Ohne
 * diesen Filter ließe sich ein Schülerkonto über eine erratene ID in Gruppen, Familien,
 * Aufgaben oder Gespräche eintragen. `unique` verrät so auch nicht, dass es ein Schülerkonto gibt.
 * Die Schülerkonten-Verwaltung prüft Benutzernamen selbst (StudentAccountService).
 */
class StudentAwarePresenceVerifier extends DatabasePresenceVerifier
{
    protected function table($table)
    {
        $query = parent::table($table);

        if ($this->isUsersTable($table)) {
            $query->where(fn ($q) => $q->whereNull('account_type')->orWhere('account_type', '!=', User::ACCOUNT_STUDENT));
        }

        return $query;
    }

    private function isUsersTable(string $table): bool
    {
        $table = str_contains($table, '.') ? substr($table, strrpos($table, '.') + 1) : $table;

        return $table === (new User)->getTable() || $table === User::class;
    }
}
