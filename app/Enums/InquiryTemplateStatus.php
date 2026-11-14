<?php

namespace App\Enums;

/**
 * Bearbeitungsstatus einer Vorlagenversion (Konzept Prozess A).
 */
enum InquiryTemplateStatus: string
{
    case Draft = 'draft';

    /** Zur Prüfung eingereicht. */
    case InReview = 'in_review';

    case Approved = 'approved';

    /** Zur Überarbeitung zurückgegeben (wieder bearbeitbar). */
    case Rejected = 'rejected';

    /** Durch eine neuere freigegebene Version ersetzt. */
    case Superseded = 'superseded';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Entwurf',
            self::InReview => 'In Prüfung',
            self::Approved => 'Freigegeben',
            self::Rejected => 'Zur Überarbeitung',
            self::Superseded => 'Ersetzt',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::InReview => 'amber',
            self::Approved => 'green',
            self::Rejected => 'red',
            self::Superseded => 'slate',
        };
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Rejected], true);
    }
}
