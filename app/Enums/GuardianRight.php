<?php

namespace App\Enums;

/**
 * Einzelrechte einer Bezugsperson gegenüber einem Kind (Spalten in child_user).
 */
enum GuardianRight: string
{
    /** Sorgerecht – u. a. Rückmeldungen pro Kind (E7). */
    case Custody = 'has_custody';

    /** Erhält Informationen der Kind-Gruppen (abgeleitete Gruppenmitgliedschaft). */
    case Information = 'receives_information';

    /** Krankmelden, Schickzeiten, Anwesenheit, Vollmachten, Notizen. */
    case Manage = 'can_manage';

    public function column(): string
    {
        return $this->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::Custody => 'Sorgeberechtigt',
            self::Information => 'Erhält Informationen',
            self::Manage => 'Darf krankmelden & Betreuung verwalten',
        };
    }
}
