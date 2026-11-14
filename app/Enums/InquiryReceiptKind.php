<?php

namespace App\Enums;

/**
 * Art der quittierten Erklärung (Erklärungsquittung nach § 126b BGB).
 */
enum InquiryReceiptKind: string
{
    /** Erste verbindliche Abgabe (auch nach einem Neustart des Falls). */
    case Submission = 'submission';

    /** Selbstkorrektur durch Sorgeberechtigte (neue Antwortversion). */
    case Correction = 'correction';

    /** Übernahme einer Änderungsmeldung durch die Schule (neue Antwortversion). */
    case ChangeAccepted = 'change_accepted';

    /** Bestätigung einer Antwortversion durch eine weitere sorgeberechtigte Person. */
    case Confirmation = 'confirmation';

    /** Widerruf einer Erklärung (wird in Phase 2 genutzt). */
    case Withdrawal = 'withdrawal';

    public function label(): string
    {
        return match ($this) {
            self::Submission => 'Abgabe der Erklärung',
            self::Correction => 'Korrektur der Erklärung',
            self::ChangeAccepted => 'Änderung der Erklärung (übernommene Änderungsmeldung)',
            self::Confirmation => 'Bestätigung durch eine weitere sorgeberechtigte Person',
            self::Withdrawal => 'Widerruf der Erklärung',
        };
    }
}
