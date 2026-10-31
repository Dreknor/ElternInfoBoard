<?php

namespace App\Enums;

/**
 * Prüfung einer eingereichten Antwort durch das Sekretariat (Menschliche Kontrolle).
 * Nur geprüfte Antworten sind für Mitarbeitende sichtbar.
 */
enum InquiryReviewStatus: string
{
    case Pending = 'pending';

    case Approved = 'approved';

    /** Widersprüchlich oder unvollständig – Klärung nötig. */
    case Clarification = 'clarification';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Ungeprüft',
            self::Approved => 'Geprüft',
            self::Clarification => 'Klärung nötig',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Approved => 'green',
            self::Clarification => 'amber',
        };
    }
}
