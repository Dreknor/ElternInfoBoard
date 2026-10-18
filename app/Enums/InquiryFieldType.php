<?php

namespace App\Enums;

/**
 * Feldtypen einer Elternabfrage (Konzept Kap. 6.1).
 */
enum InquiryFieldType: string
{
    /** Einwilligung: vollständiger Text bleibt sichtbar und wird mit der Vorlagenversion verknüpft. */
    case Consent = 'consent';

    /** Zustimmung oder Ablehnung als Einzelentscheidung. */
    case YesNo = 'yes_no';

    /** Mehrfachauswahl. */
    case Checkbox = 'checkbox';

    /** Auswahl einer Option. */
    case Radio = 'radio';

    case Select = 'select';

    case Text = 'text';

    case Textarea = 'textarea';

    case Date = 'date';

    case File = 'file';

    /** Auswahl eines eigenen Kindes (Kinder, für die das antwortende Konto sorgeberechtigt ist). */
    case Child = 'child';

    /** Zwischenüberschrift/Hinweis ohne Eingabe – gliedert das Formular in kurze Abschnitte. */
    case Info = 'info';

    public function label(): string
    {
        return match ($this) {
            self::Consent => 'Einwilligung',
            self::YesNo => 'Zustimmung / Ablehnung',
            self::Checkbox => 'Mehrfachauswahl',
            self::Radio => 'Auswahl einer Option',
            self::Select => 'Dropdown',
            self::Text => 'Text (einzeilig)',
            self::Textarea => 'Text (mehrzeilig)',
            self::Date => 'Datum',
            self::File => 'Datei-Upload',
            self::Child => 'Auswahl eines eigenen Kindes',
            self::Info => 'Abschnitt / Hinweis',
        };
    }

    public function hasOptions(): bool
    {
        return in_array($this, [self::Checkbox, self::Radio, self::Select], true);
    }

    public function isInput(): bool
    {
        return $this !== self::Info;
    }

    public function supportsLength(): bool
    {
        return in_array($this, [self::Text, self::Textarea, self::Checkbox], true);
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
