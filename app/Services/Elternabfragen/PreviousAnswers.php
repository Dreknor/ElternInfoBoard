<?php

namespace App\Services\Elternabfragen;

use App\Enums\InquiryFieldType;
use App\Model\InquiryAssignment;
use App\Model\InquiryResponse;

/**
 * Wiederkehrende Abfragen (z. B. neues Schuljahr): Angaben der Vorabfrage für dasselbe
 * Kind vorbefüllen und beim Absenden festhalten, was sich geändert hat.
 *
 * Einwilligungen werden nicht übernommen (müssen aktiv neu erteilt werden), Dateien
 * ebenfalls nicht (sie gehören zur Vorabfrage und werden mit ihr gelöscht).
 */
class PreviousAnswers
{
    /** Gültige Antwort der Vorabfrage für dasselbe Kind. */
    public function baseline(InquiryAssignment $assignment): ?InquiryResponse
    {
        $previousId = $assignment->inquiry?->previous_inquiry_id;
        if (! $previousId) {
            return null;
        }

        return InquiryResponse::query()
            ->where('inquiry_id', $previousId)
            ->where('child_id', $assignment->child_id)
            ->where('is_current', true)
            ->with(['templateVersion', 'inquiry:id,title,deadline_at'])
            ->first();
    }

    /**
     * Vorbefüllung: nur Felder, die es mit gleichem Typ weiterhin gibt und deren Wert
     * noch gültig ist (Optionen, eigene Kinder).
     *
     * @param  array<int, string>  $children  wählbare eigene Kinder (id => Name)
     * @return array<string, mixed>
     */
    public function prefill(InquiryAssignment $assignment, array $fields, array $children = []): array
    {
        $baseline = $this->baseline($assignment);
        if (! $baseline) {
            return [];
        }

        $answers = [];
        foreach ($this->comparable($fields, $baseline) as $field) {
            $type = InquiryFieldType::from($field['type']);
            $value = $baseline->answers[$field['key']] ?? null;
            if ($type === InquiryFieldType::Consent || $value === null || $value === '' || $value === []) {
                continue;
            }

            $value = match ($type) {
                InquiryFieldType::Radio, InquiryFieldType::Select => in_array($value, $field['options'] ?? [], true) ? $value : null,
                InquiryFieldType::Checkbox => array_values(array_intersect((array) $value, $field['options'] ?? [])) ?: null,
                InquiryFieldType::Child => array_key_exists((int) FieldSchema::childId($value), $children) ? $value : null,
                default => $value,
            };
            if ($value !== null) {
                $answers[$field['key']] = $value;
            }
        }

        return $answers;
    }

    /**
     * Geänderte Angaben gegenüber der Vorabfrage (Feldschlüssel => alter/neuer Wert).
     * Neue Felder ohne Gegenstück in der Vorabfrage zählen nicht als Änderung.
     *
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public function changes(array $fields, InquiryResponse $baseline, array $answers): array
    {
        $changes = [];
        foreach ($this->comparable($fields, $baseline) as $field) {
            $old = $baseline->answers[$field['key']] ?? null;
            $new = $answers[$field['key']] ?? null;
            if ($this->normalize($field, $old) !== $this->normalize($field, $new)) {
                $changes[$field['key']] = ['old' => $old, 'new' => $new];
            }
        }

        return $changes;
    }

    /** Eingabefelder (ohne Dateien), die in der Vorabfrage mit gleichem Typ vorkamen. */
    private function comparable(array $fields, InquiryResponse $baseline): array
    {
        $previous = collect($baseline->templateVersion?->fields ?? [])->keyBy(fn ($f) => $f['key'] ?? '');

        return array_values(array_filter($fields, function (array $field) use ($previous) {
            $type = InquiryFieldType::from($field['type']);

            return $type->isInput() && $type !== InquiryFieldType::File
                && ($previous->get($field['key'])['type'] ?? null) === $field['type'];
        }));
    }

    private function normalize(array $field, mixed $value): mixed
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        return match (InquiryFieldType::from($field['type'])) {
            InquiryFieldType::Checkbox => collect((array) $value)->map(fn ($v) => (string) $v)->sort()->values()->all(),
            InquiryFieldType::Child => FieldSchema::childId($value),
            default => is_scalar($value) ? trim((string) $value) : $value,
        };
    }
}
