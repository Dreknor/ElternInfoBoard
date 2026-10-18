<?php

namespace App\Services\Elternabfragen;

use App\Enums\InquiryFieldType;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Formularfelder einer Vorlagenversion: Definition prüfen, Validierungsregeln für
 * Antworten bilden, Antworten bereinigen und lesbar darstellen.
 *
 * Definition je Feld:
 *  key, type, label, help, required, options[], min_length, max_length, format,
 *  error_message, consent_text
 */
class FieldSchema
{
    public const FORMATS = [
        'email' => 'E-Mail-Adresse',
        'phone' => 'Telefonnummer',
        'postal_code' => 'Postleitzahl',
        'number' => 'Zahl',
    ];

    public const UPLOAD_MIMES = ['pdf', 'jpg', 'jpeg', 'png', 'heic'];

    public const YES = 'yes';

    public const NO = 'no';

    public const GRANTED = 'granted';

    public const DENIED = 'denied';

    /**
     * Prüft und normalisiert die Felddefinition einer Vorlage.
     *
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<int, array<string, mixed>>
     *
     * @throws ValidationException
     */
    public function normalizeDefinition(array $fields): array
    {
        $errors = [];
        $normalized = [];
        $keys = [];
        $cleanKey = fn (array $field) => Str::of((string) ($field['key'] ?? ''))->lower()->replaceMatches('/[^a-z0-9_]/', '')->limit(40, '')->toString();

        // Vorhandene Schlüssel zuerst reservieren, damit neue Felder sie nicht verdrängen
        $explicit = array_count_values(array_filter(array_map($cleanKey, array_values($fields))));

        foreach (array_values($fields) as $index => $field) {
            $position = $index + 1;
            $type = InquiryFieldType::tryFrom((string) ($field['type'] ?? ''));
            $label = trim((string) ($field['label'] ?? ''));

            if (! $type) {
                $errors["fields.$index"] = "Feld $position: unbekannter Feldtyp.";

                continue;
            }
            if ($label === '') {
                $errors["fields.$index"] = "Feld $position: Bitte eine Beschriftung angeben.";

                continue;
            }

            $key = $cleanKey($field);
            if ($key === '' || isset($keys[$key])) {
                $key = $this->uniqueKey(Str::slug($label, '_'), $keys + $explicit);
            }
            $keys[$key] = true;

            $item = [
                'key' => $key,
                'type' => $type->value,
                'label' => Str::limit($label, 500, ''),
                'help' => $this->text($field['help'] ?? null, 1000),
                'required' => $type->isInput() && filter_var($field['required'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ];

            if ($type->hasOptions()) {
                $options = $field['options'] ?? [];
                if (is_string($options)) {
                    $options = preg_split('/\r\n|\r|\n/', $options);
                }
                $options = array_values(array_unique(array_filter(array_map(fn ($o) => Str::limit(trim((string) $o), 200, ''), (array) $options), 'strlen')));
                if (count($options) < 2) {
                    $errors["fields.$index"] = "Feld $position („{$label}“): mindestens zwei Antwortoptionen angeben.";
                }
                $item['options'] = $options;
            }

            if ($type->supportsLength()) {
                $min = $this->positiveInt($field['min_length'] ?? null);
                $max = $this->positiveInt($field['max_length'] ?? null);
                if ($min !== null && $max !== null && $min > $max) {
                    $errors["fields.$index"] = "Feld $position („{$label}“): Mindestwert ist größer als Höchstwert.";
                }
                $item['min_length'] = $min;
                $item['max_length'] = $max;
            }

            if ($type === InquiryFieldType::Text) {
                $format = $field['format'] ?? null;
                $item['format'] = array_key_exists((string) $format, self::FORMATS) ? $format : null;
            }

            if ($type === InquiryFieldType::Consent) {
                $consent = $this->text($field['consent_text'] ?? null, 20000);
                if ($consent === null) {
                    $errors["fields.$index"] = "Feld $position („{$label}“): Der vollständige Einwilligungstext ist erforderlich.";
                }
                $item['consent_text'] = $consent;
            }

            if ($type->isInput()) {
                $item['error_message'] = $this->text($field['error_message'] ?? null, 300);
            }

            $normalized[] = $item;
        }

        if ($normalized === [] || ! collect($normalized)->contains(fn ($f) => InquiryFieldType::from($f['type'])->isInput())) {
            $errors['fields'] = 'Die Vorlage benötigt mindestens ein Eingabefeld.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $normalized;
    }

    /**
     * Validierungsregeln für Antworten (Schlüssel `answers.<key>`).
     *
     * @param  array<string, mixed>  $existing  bisherige Antwort (bei Korrekturen bleiben Dateien erhalten)
     * @param  array<int, string>  $children  wählbare eigene Kinder (id => Name) für Felder vom Typ „child“
     */
    public function rules(array $fields, bool $submit, int $uploadMaxKb, array $existing = [], array $children = []): array
    {
        $rules = ['answers' => ['nullable', 'array']];

        foreach ($fields as $field) {
            $type = InquiryFieldType::from($field['type']);
            if (! $type->isInput()) {
                continue;
            }
            $key = 'answers.'.$field['key'];
            $required = $submit && ($field['required'] ?? false);
            $presence = $required ? 'required' : 'nullable';

            $rules[$key] = match ($type) {
                InquiryFieldType::Consent => [$presence, 'in:'.self::GRANTED.','.self::DENIED],
                InquiryFieldType::YesNo => [$presence, 'in:'.self::YES.','.self::NO],
                InquiryFieldType::Radio, InquiryFieldType::Select => [$presence, 'string', function ($attribute, $value, $fail) use ($field) {
                    if (! in_array($value, $field['options'] ?? [], true)) {
                        $fail($field['error_message'] ?: 'Bitte eine der angebotenen Optionen wählen.');
                    }
                }],
                InquiryFieldType::Checkbox => array_values(array_filter([
                    $presence, 'array',
                    $required ? 'min:'.max(1, (int) ($field['min_length'] ?? 1)) : null,
                    ! $required && $submit && ($field['min_length'] ?? null) ? 'min:'.$field['min_length'] : null,
                    ($field['max_length'] ?? null) ? 'max:'.$field['max_length'] : null,
                ])),
                InquiryFieldType::Text => array_values(array_filter([
                    $presence, 'string',
                    $submit && ($field['min_length'] ?? null) ? 'min:'.$field['min_length'] : null,
                    'max:'.($field['max_length'] ?: 255),
                    $submit ? $this->formatRule($field['format'] ?? null) : null,
                ])),
                InquiryFieldType::Textarea => array_values(array_filter([
                    $presence, 'string',
                    $submit && ($field['min_length'] ?? null) ? 'min:'.$field['min_length'] : null,
                    'max:'.($field['max_length'] ?: 5000),
                ])),
                InquiryFieldType::Date => [$presence, 'date_format:Y-m-d'],
                InquiryFieldType::File => [
                    $required && empty($existing[$field['key']]) ? 'required' : 'nullable',
                    'file', 'mimes:'.implode(',', self::UPLOAD_MIMES), 'max:'.$uploadMaxKb,
                ],
                InquiryFieldType::Child => [$presence, function ($attribute, $value, $fail) use ($field, $children) {
                    if (! array_key_exists((int) self::childId($value), $children)) {
                        $fail($field['error_message'] ?: 'Bitte wählen Sie eines Ihrer Kinder aus.');
                    }
                }],
                InquiryFieldType::Info => [],
            };

            if ($type === InquiryFieldType::Checkbox) {
                $rules[$key.'.*'] = ['string', function ($attribute, $value, $fail) use ($field) {
                    if (! in_array($value, $field['options'] ?? [], true)) {
                        $fail('Ungültige Auswahl.');
                    }
                }];
            }
        }

        return $rules;
    }

    /**
     * Verständliche Fehlermeldungen: eigene Meldung des Feldes, sonst Standardtext mit Beschriftung.
     */
    public function messages(array $fields): array
    {
        $messages = [];
        foreach ($fields as $field) {
            $key = 'answers.'.$field['key'];
            $label = $field['label'];
            $custom = $field['error_message'] ?? null;
            $type = InquiryFieldType::from($field['type']);

            $messages[$key.'.required'] = $custom ?: match ($type) {
                InquiryFieldType::Consent, InquiryFieldType::YesNo => "Bitte treffen Sie eine Entscheidung zu „{$label}“.",
                InquiryFieldType::File => "Bitte laden Sie eine Datei für „{$label}“ hoch.",
                InquiryFieldType::Checkbox, InquiryFieldType::Radio, InquiryFieldType::Select => "Bitte wählen Sie bei „{$label}“ aus.",
                InquiryFieldType::Child => "Bitte wählen Sie bei „{$label}“ ein Kind aus.",
                default => "Bitte füllen Sie „{$label}“ aus.",
            };
            foreach (['min', 'max', 'email', 'regex', 'numeric', 'date_format', 'in', 'mimes', 'array', 'string'] as $rule) {
                $messages[$key.'.'.$rule] = $custom ?: $this->defaultMessage($rule, $field);
            }
        }

        return $messages;
    }

    /**
     * Nur bekannte Felder übernehmen und Werte normalisieren (ohne Dateien).
     * Ausgewählte Kinder werden mit Namen gespeichert, damit die Antwort auch später lesbar bleibt.
     *
     * @param  array<int, string>  $children  wählbare eigene Kinder (id => Name)
     * @return array<string, mixed>
     */
    public function sanitize(array $fields, array $input, array $children = []): array
    {
        $answers = [];
        foreach ($fields as $field) {
            $type = InquiryFieldType::from($field['type']);
            if (! $type->isInput() || $type === InquiryFieldType::File || ! array_key_exists($field['key'], $input)) {
                continue;
            }
            $value = $input[$field['key']];

            $answers[$field['key']] = match ($type) {
                InquiryFieldType::Checkbox => array_values(array_intersect($field['options'] ?? [], array_map('strval', (array) $value))),
                InquiryFieldType::Child => array_key_exists($id = (int) self::childId($value), $children)
                    ? ['id' => $id, 'name' => $children[$id]]
                    : null,
                default => is_scalar($value) ? (trim((string) $value) === '' ? null : trim((string) $value)) : null,
            };
        }

        return $answers;
    }

    /**
     * Pflichtfelder ohne Antwort (für Export-Vorschau und Prüfung).
     *
     * @return list<string> Beschriftungen
     */
    public function missing(array $fields, array $answers): array
    {
        $missing = [];
        foreach ($fields as $field) {
            if (! ($field['required'] ?? false)) {
                continue;
            }
            $value = $answers[$field['key']] ?? null;
            if ($value === null || $value === '' || $value === []) {
                $missing[] = $field['label'];
            }
        }

        return $missing;
    }

    /**
     * Lesbare Darstellung eines Werts (Zusammenfassung, Bestätigung, Export).
     */
    public function display(array $field, mixed $value): string
    {
        if ($value === null || $value === '' || $value === []) {
            return '–';
        }

        return match (InquiryFieldType::from($field['type'])) {
            InquiryFieldType::Consent => $value === self::GRANTED ? 'Einwilligung erteilt' : 'Einwilligung nicht erteilt',
            InquiryFieldType::YesNo => $value === self::YES ? 'Ja' : 'Nein',
            InquiryFieldType::Checkbox => implode(', ', (array) $value),
            InquiryFieldType::Date => rescue(fn () => \Carbon\Carbon::createFromFormat('Y-m-d', (string) $value)->format('d.m.Y'), (string) $value, false),
            InquiryFieldType::File => is_array($value) ? ($value['name'] ?? 'Datei') : 'Datei',
            InquiryFieldType::Child => is_array($value) ? (string) ($value['name'] ?? 'Kind #'.($value['id'] ?? '?')) : 'Kind #'.$value,
            default => (string) $value,
        };
    }

    /**
     * Stabiler Hash des den Eltern vorgelegten Formularinhalts (inkl. Einwilligungstexte).
     */
    public function formHash(int $templateVersionId, string $title, ?string $intro, array $fields): string
    {
        return hash('sha256', json_encode([
            'template_version_id' => $templateVersionId,
            'title' => $title,
            'intro' => $intro,
            'fields' => $fields,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function answerHash(array $answers): string
    {
        ksort($answers);

        return hash('sha256', json_encode($answers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Kind-ID aus einer Eingabe: ID direkt oder gespeicherter Wert {id, name} (z. B. aus einem Entwurf).
     */
    public static function childId(mixed $value): ?string
    {
        $id = is_array($value) ? ($value['id'] ?? null) : $value;

        return is_scalar($id) && ctype_digit((string) $id) ? (string) $id : null;
    }

    private function formatRule(?string $format): ?string
    {
        return match ($format) {
            'email' => 'email:rfc',
            'phone' => 'regex:/^\+?[0-9 ()\/-]{5,30}$/',
            'postal_code' => 'regex:/^[0-9]{5}$/',
            'number' => 'numeric',
            default => null,
        };
    }

    private function defaultMessage(string $rule, array $field): string
    {
        $label = $field['label'];
        $min = (int) ($field['min_length'] ?? 0);
        $max = (int) ($field['max_length'] ?? 0);

        return match ($rule) {
            'min' => InquiryFieldType::from($field['type']) === InquiryFieldType::Checkbox
                ? 'Bitte wählen Sie bei „'.$label.'“ mindestens '.max(1, $min).' Option(en).'
                : "„{$label}“ muss mindestens {$min} Zeichen lang sein.",
            'max' => match (InquiryFieldType::from($field['type'])) {
                InquiryFieldType::Checkbox => "Bitte wählen Sie bei „{$label}“ höchstens {$max} Optionen.",
                InquiryFieldType::File => "Die Datei für „{$label}“ ist zu groß.",
                default => "„{$label}“ ist zu lang.",
            },
            'email' => "Bitte geben Sie bei „{$label}“ eine gültige E-Mail-Adresse an.",
            'regex' => "Bitte prüfen Sie das Format bei „{$label}“ (".(self::FORMATS[$field['format'] ?? ''] ?? 'Format').').',
            'numeric' => "Bitte geben Sie bei „{$label}“ eine Zahl an.",
            'date_format' => "Bitte geben Sie bei „{$label}“ ein gültiges Datum an.",
            'mimes' => "Erlaubt sind bei „{$label}“ nur PDF- und Bilddateien (JPG, PNG, HEIC).",
            default => "Bitte prüfen Sie Ihre Angabe bei „{$label}“.",
        };
    }

    private function uniqueKey(string $base, array $taken): string
    {
        $base = substr(preg_replace('/[^a-z0-9_]/', '', strtolower($base)) ?: 'feld', 0, 32);
        $key = $base;
        $i = 2;
        while (isset($taken[$key])) {
            $key = $base.'_'.$i++;
        }

        return $key;
    }

    private function text(mixed $value, int $limit): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : Str::limit($value, $limit, '');
    }

    private function positiveInt(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }
}
