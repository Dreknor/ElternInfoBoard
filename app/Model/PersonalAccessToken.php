<?php

namespace App\Model;

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
     * Produktiv wie bisher MySQL; in Tests (SQLite im Speicher) die Standardverbindung,
     * sonst lassen sich dort keine Tokens anlegen.
     */
    public function getConnectionName()
    {
        return app()->runningUnitTests() ? config('database.default') : $this->connection;
    }
}
