<?php

namespace App\Enums;

/**
 * Lebenszyklus einer Abfrage (Konzept Prozess B): Keine Abfrage wird unmittelbar
 * nach dem Erstellen versendet.
 */
enum InquiryStatus: string
{
    case Draft = 'draft';

    /** Zur Freigabe eingereicht. */
    case PendingApproval = 'pending_approval';

    /** Freigegeben, Startzeitpunkt liegt in der Zukunft. */
    case Scheduled = 'scheduled';

    /** Gestartet: Aufträge erzeugt, Eltern benachrichtigt. */
    case Running = 'running';

    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Entwurf',
            self::PendingApproval => 'Wartet auf Freigabe',
            self::Scheduled => 'Freigegeben (geplant)',
            self::Running => 'Läuft',
            self::Closed => 'Abgeschlossen',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::PendingApproval => 'amber',
            self::Scheduled => 'blue',
            self::Running => 'green',
            self::Closed => 'slate',
        };
    }

    public function isReleased(): bool
    {
        return in_array($this, [self::Running, self::Closed], true);
    }
}
