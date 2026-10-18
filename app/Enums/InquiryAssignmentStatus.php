<?php

namespace App\Enums;

/**
 * Bearbeitungsstand je Kind (Konzept Prozess D). Erinnerungen sind kein Status,
 * sondern werden als Zustellung protokolliert.
 */
enum InquiryAssignmentStatus: string
{
    /** Keine gültige Antwort liegt vor. */
    case Open = 'open';

    /** Ein Entwurf wurde begonnen, aber noch nicht abgesendet. */
    case InProgress = 'in_progress';

    /** Eine vollständige Antwort liegt vor. */
    case Submitted = 'submitted';

    /** Eine neue Antwortversion wurde abgegeben. */
    case Corrected = 'corrected';

    /** Zustellung oder Bearbeitung war nicht möglich. */
    case Problem = 'problem';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Offen',
            self::InProgress => 'In Bearbeitung',
            self::Submitted => 'Eingereicht',
            self::Corrected => 'Korrektur eingereicht',
            self::Problem => 'Technisches Problem',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'gray',
            self::InProgress => 'amber',
            self::Submitted => 'green',
            self::Corrected => 'teal',
            self::Problem => 'red',
        };
    }

    public function isAnswered(): bool
    {
        return in_array($this, [self::Submitted, self::Corrected], true);
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
