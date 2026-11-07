<?php

namespace App\Enums;

/**
 * Bearbeitungsstand einer Änderungsmeldung der Eltern.
 */
enum InquiryChangeRequestStatus: string
{
    /** Gemeldet, vom Sekretariat noch nicht bearbeitet. */
    case Pending = 'pending';

    /** Übernommen – daraus ist eine neue Antwortversion entstanden. */
    case Accepted = 'accepted';

    /** Abgelehnt (mit Begründung an die Eltern). */
    case Rejected = 'rejected';

    /** Von den Eltern zurückgezogen oder durch eine neue Meldung ersetzt. */
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Offen',
            self::Accepted => 'Übernommen',
            self::Rejected => 'Abgelehnt',
            self::Withdrawn => 'Zurückgezogen',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Accepted => 'green',
            self::Rejected => 'red',
            self::Withdrawn => 'gray',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
