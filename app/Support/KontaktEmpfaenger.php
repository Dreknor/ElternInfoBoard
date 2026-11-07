<?php

namespace App\Support;

use App\Model\User;
use App\Settings\EmailSetting;

/**
 * Empfänger des Kontaktformulars (Web und API).
 *
 * `mitarbeiter` ist eine einzelne ID (ältere App-Versionen, 0/leer = Sekretariat)
 * oder eine Liste von IDs. Ohne Auswahl geht die Nachricht an die Kontaktadresse
 * der Schule. Erlaubt sind nur Personen aus User::contactRecipients().
 */
class KontaktEmpfaenger
{
    /** Höchstzahl gleichzeitiger Empfänger */
    public const MAX = 10;

    /**
     * Ausgewählte Personen-IDs (ohne 0/leer = Sekretariat), eindeutig.
     *
     * @return list<int>
     */
    public static function ids(mixed $input): array
    {
        return array_values(array_unique(array_filter(
            array_map('intval', is_array($input) ? $input : [$input]),
            fn (int $id) => $id > 0
        )));
    }

    /**
     * E-Mail-Adressen zu den IDs; null, wenn eine ID nicht auswählbar ist.
     *
     * @param  list<int>  $ids
     * @return list<string>|null
     */
    public static function emails(array $ids): ?array
    {
        if ($ids === []) {
            $settings = app(EmailSetting::class);

            return [$settings->contact_default_email ?: config('mail.from.address')];
        }

        $emails = User::contactRecipients()
            ->whereIn('id', $ids)
            ->whereNotNull('email')
            ->pluck('email', 'id');

        return $emails->count() === count($ids) ? $emails->values()->all() : null;
    }
}
