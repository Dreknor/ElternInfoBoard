<?php

namespace App\Auth;

use App\Model\Scopes\ExcludeStudentAccounts;
use Illuminate\Auth\EloquentUserProvider;

/**
 * Lädt angemeldete Konten auch dann, wenn es Schülerkonten sind (globaler Scope
 * ExcludeStudentAccounts, docs/konzept-schuelerkonten.md §5).
 *
 * Nur die Wiederherstellung einer bestehenden Sitzung (ID, „angemeldet bleiben“) sieht
 * Schülerkonten. Die Suche über Zugangsdaten (`retrieveByCredentials`: Login mit E-Mail,
 * Passwort-Reset) findet sie bewusst nicht: Schüler melden sich nur über den eigenen
 * Benutzernamen-Login oder SSO an.
 */
class StudentAwareUserProvider extends EloquentUserProvider
{
    public function retrieveById($identifier)
    {
        $model = $this->createModel();

        return $this->newModelQuery($model)
            ->withoutGlobalScope(ExcludeStudentAccounts::class)
            ->where($model->getAuthIdentifierName(), $identifier)
            ->first();
    }

    public function retrieveByToken($identifier, #[\SensitiveParameter] $token)
    {
        $model = $this->createModel();

        $retrievedModel = $this->newModelQuery($model)
            ->withoutGlobalScope(ExcludeStudentAccounts::class)
            ->where($model->getAuthIdentifierName(), $identifier)
            ->first();

        if (! $retrievedModel) {
            return null;
        }

        $rememberToken = $retrievedModel->getRememberToken();

        return $rememberToken && hash_equals($rememberToken, $token) ? $retrievedModel : null;
    }
}
