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
}
