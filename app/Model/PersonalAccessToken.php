<?php

namespace App\Model;

use App\Model\Scopes\ExcludeStudentAccounts;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    /**
     * The database connection that should be used by the model.
     *
     * @var string
     */
    protected $connection = 'mysql';

    /**
     * Produktiv wie bisher MySQL (config `sanctum.token_connection`); in Tests (SQLite im Speicher)
     * die Standardverbindung, sonst lassen sich dort keine Tokens anlegen.
     */
    public function getConnectionName()
    {
        if (app()->runningUnitTests()) {
            return config('database.default');
        }

        return config('sanctum.token_connection', $this->connection);
    }

    /**
     * Auch Schülerkonten auflösen (globaler Scope ExcludeStudentAccounts). Was ein Schüler-Token
     * darf, regeln RestrictStudentAccounts und die Token-Fähigkeit `student`.
     */
    public function tokenable(): MorphTo
    {
        return $this->morphTo('tokenable')->withoutGlobalScopes([ExcludeStudentAccounts::class]);
    }
}
