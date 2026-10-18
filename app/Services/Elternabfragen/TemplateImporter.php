<?php

namespace App\Services\Elternabfragen;

use App\Enums\InquiryFieldType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Excel-/CSV-Listen als Grundlage für Vorlagen: Jede Spaltenüberschrift der ersten Zeile
 * wird zu einem Feld. Werte darunter (z. B. eine bisher ausgefüllte Liste) bestimmen den
 * Feldtyp: Ja/Nein, Datum, wenige wiederkehrende Werte als Auswahl, lange Texte als Textarea.
 * Spalten zur Identifikation des Kindes entfallen – die Zuordnung erfolgt je Kind automatisch.
 */
class TemplateImporter
{
    public const MAX_COLUMNS = 60;

    /** Spaltenköpfe, die das Kind identifizieren (normalisiert: Kleinbuchstaben ohne Sonderzeichen). */
    private const IDENTITY_HEADERS = [
        'name', 'vorname', 'nachname', 'familienname', 'rufname', 'namedeskindes', 'vornamedeskindes', 'nachnamedeskindes',
        'klasse', 'kind', 'schueler', 'schuelerin', 'schuelername', 'nr', 'lfdnr', 'nummer', 'id', 'schuelerid', 'geburtsdatum',
    ];

    private const YES_NO = ['ja', 'nein', 'j', 'n', 'yes', 'no'];

    /**
     * @return array{fields: list<array<string, mixed>>, skipped: list<string>}
     *
     * @throws ValidationException
     */
    public function import(UploadedFile $file): array
    {
        $sheets = Excel::toArray(new class {}, $file);
        $rows = array_values(array_filter($sheets[0] ?? [], fn ($row) => array_filter((array) $row, fn ($v) => trim((string) $v) !== '') !== []));

        return $this->fromRows($rows);
    }

    /**
     * @param  list<array<int, mixed>>  $rows  erste Zeile = Spaltenköpfe
     * @return array{fields: list<array<string, mixed>>, skipped: list<string>}
     *
     * @throws ValidationException
     */
    public function fromRows(array $rows): array
    {
        $headers = array_map(fn ($v) => Str::limit(trim(preg_replace('/\s+/u', ' ', (string) $v)), 500, ''), array_values($rows[0] ?? []));
        $fields = [];
        $skipped = [];
        $seen = [];

        foreach (array_slice($headers, 0, self::MAX_COLUMNS, true) as $column => $label) {
            if ($label === '') {
                continue;
            }
            if ($this->isIdentity($label)) {
                $skipped[] = $label;

                continue;
            }
            if (isset($seen[mb_strtolower($label)])) {
                continue;
            }
            $seen[mb_strtolower($label)] = true;

            $values = [];
            foreach (array_slice($rows, 1) as $row) {
                $value = trim((string) (array_values((array) $row)[$column] ?? ''));
                if ($value !== '') {
                    $values[] = $value;
                }
            }

            $fields[] = $this->field($label, $values);
        }

        if ($fields === []) {
            throw ValidationException::withMessages([
                'file' => $skipped === []
                    ? 'In der ersten Zeile wurden keine Spaltenüberschriften gefunden.'
                    : 'Die Datei enthält nur Spalten zur Identifikation des Kindes ('.implode(', ', $skipped).'). Diese werden je Kind automatisch erfasst.',
            ]);
        }

        return ['fields' => $fields, 'skipped' => $skipped];
    }

    /**
     * @param  list<string>  $values
     * @return array<string, mixed>
     */
    private function field(string $label, array $values): array
    {
        $field = ['type' => InquiryFieldType::Text->value, 'label' => $label, 'required' => true];
        $normalized = $this->normalize($label);
        $distinct = array_values(array_unique($values));

        if ($values !== [] && collect($values)->every(fn ($v) => in_array(mb_strtolower($v), self::YES_NO, true))) {
            $field['type'] = InquiryFieldType::YesNo->value;
        } elseif (str_contains($normalized, 'datum') || ($values !== [] && collect($values)->every(fn ($v) => preg_match('/^\d{1,2}\.\d{1,2}\.\d{2,4}$|^\d{4}-\d{2}-\d{2}$/', $v)))) {
            $field['type'] = InquiryFieldType::Date->value;
        } elseif (count($distinct) >= 2 && count($distinct) <= 10 && count($values) > count($distinct) && collect($distinct)->every(fn ($v) => mb_strlen($v) <= 200)) {
            $field['type'] = count($distinct) <= 4 ? InquiryFieldType::Radio->value : InquiryFieldType::Select->value;
            $field['options'] = $distinct;
        } elseif (collect($values)->contains(fn ($v) => mb_strlen($v) > 120 || str_contains($v, "\n"))) {
            $field['type'] = InquiryFieldType::Textarea->value;
        } else {
            $field['format'] = match (true) {
                str_contains($normalized, 'mail') => 'email',
                str_contains($normalized, 'telefon') || str_contains($normalized, 'handy') || str_contains($normalized, 'mobil') || $normalized === 'tel' => 'phone',
                $normalized === 'plz' || str_contains($normalized, 'postleitzahl') => 'postal_code',
                default => null,
            };
        }

        return $field;
    }

    private function isIdentity(string $label): bool
    {
        return in_array($this->normalize($label), self::IDENTITY_HEADERS, true);
    }

    private function normalize(string $label): string
    {
        $label = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], mb_strtolower($label));

        return preg_replace('/[^a-z0-9]/', '', $label);
    }
}
