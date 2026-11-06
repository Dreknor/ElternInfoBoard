<?php

namespace App\Support;

use App\Model\Link;
use App\Model\Post;
use App\Model\Termin;
use App\Model\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Feld „Sichtbar für“ in Nachrichten, Terminen und Links (docs/konzept-schuelerkonten.md §9, Paket S-05).
 *
 * Erscheint und wirkt nur bei aktivem Modul und mit dem Recht „create student posts“
 * (bei Links: „edit links“, weil die Verwaltung der Links an diesem Recht hängt). Ohne das
 * Recht bleibt ein vorhandener Wert beim Bearbeiten unverändert; neue Einträge sind `eltern`.
 * Einträge „nur für Schüler“ bekommen keine Elternfunktionen (Lesebestätigung, Reaktionen,
 * externes Angebot, öffentliche Termine), weil deren Empfänger Eltern wären.
 */
final class AudienceField
{
    public const LABELS = [
        StudentAccounts::ZIELGRUPPE_ELTERN => 'Eltern',
        StudentAccounts::ZIELGRUPPE_ELTERN_SCHUELER => 'Eltern und Schüler',
        StudentAccounts::ZIELGRUPPE_SCHUELER => 'nur Schüler',
    ];

    public static function available(?User $user, ?string $permission = null): bool
    {
        return $user !== null
            && ! $user->isStudent()
            && StudentAccounts::enabled()
            && $user->can($permission ?? StudentAccounts::PERMISSION_CREATE_POSTS);
    }

    public static function apply(Post|Termin|Link $model, Request $request): void
    {
        $permission = $model instanceof Link ? Link::MANAGE_PERMISSION : null;

        if (self::available($request->user(), $permission) && $request->has('zielgruppe')) {
            $value = Validator::make($request->only('zielgruppe'), [
                'zielgruppe' => ['required', Rule::in(StudentAccounts::ZIELGRUPPEN)],
            ], [], ['zielgruppe' => 'Sichtbar für'])->validate()['zielgruppe'];

            $model->zielgruppe = $value;
        } elseif (! $model->exists) {
            $model->zielgruppe = StudentAccounts::ZIELGRUPPE_ELTERN;
        }

        if ($model instanceof Link || $model->zielgruppe !== StudentAccounts::ZIELGRUPPE_SCHUELER) {
            return;
        }

        if ($model instanceof Post) {
            $model->read_receipt = false;
            $model->reactable = false;
            $model->external = false;
        } else {
            $model->public = false;
        }
    }

    public static function label(?string $value): string
    {
        return self::LABELS[$value ?? StudentAccounts::ZIELGRUPPE_ELTERN] ?? self::LABELS[StudentAccounts::ZIELGRUPPE_ELTERN];
    }
}
