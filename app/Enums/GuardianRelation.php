<?php

namespace App\Enums;

/**
 * Art der Beziehung zwischen Kind und Bezugsperson (child_user.relation).
 *
 * @see docs/kind-zentriertes-familienmodell-konzept.md §4.1 (E5)
 */
enum GuardianRelation: string
{
    case Mother = 'mother';
    case Father = 'father';
    case LegalGuardian = 'legal_guardian';
    case FosterParent = 'foster_parent';
    case Partner = 'partner';
    case Grandparent = 'grandparent';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Mother => 'Mutter',
            self::Father => 'Vater',
            self::LegalGuardian => 'Sorgeberechtigte/r',
            self::FosterParent => 'Pflegeelternteil',
            self::Partner => 'Partner/in eines Elternteils',
            self::Grandparent => 'Großelternteil',
            self::Other => 'Sonstige Bezugsperson',
        };
    }

    /**
     * Standardrechte beim Anlegen einer Beziehung (E5).
     *
     * @return array{has_custody: bool, receives_information: bool, can_manage: bool}
     */
    public function defaultRights(): array
    {
        return match ($this) {
            self::Mother, self::Father, self::LegalGuardian, self::FosterParent => [
                'has_custody' => true, 'receives_information' => true, 'can_manage' => true,
            ],
            self::Partner => [
                'has_custody' => false, 'receives_information' => true, 'can_manage' => true,
            ],
            self::Grandparent, self::Other => [
                'has_custody' => false, 'receives_information' => true, 'can_manage' => false,
            ],
        };
    }

    /**
     * Toleranter Parser für Import-/Formularwerte (deutsch oder englisch).
     */
    public static function fromInput(?string $value): self
    {
        $normalized = mb_strtolower(trim((string) $value));

        return match ($normalized) {
            'mother', 'mutter' => self::Mother,
            'father', 'vater' => self::Father,
            'foster_parent', 'pflegemutter', 'pflegevater', 'pflegeeltern', 'pflegeelternteil' => self::FosterParent,
            'partner', 'partnerin', 'lebenspartner', 'lebenspartnerin', 'stiefmutter', 'stiefvater' => self::Partner,
            'grandparent', 'oma', 'opa', 'großmutter', 'großvater', 'grossmutter', 'grossvater', 'großeltern' => self::Grandparent,
            'other', 'sonstige', 'sonstiges', 'andere' => self::Other,
            default => self::LegalGuardian,
        };
    }

    /**
     * @return array<string, string> value => label
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
