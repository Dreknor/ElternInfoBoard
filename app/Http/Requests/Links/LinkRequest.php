<?php

namespace App\Http\Requests\Links;

use App\Model\Link;
use App\Support\LinkUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Anlegen und Bearbeiten eines Links (Modul „Links“). */
class LinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('edit links');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => trim((string) $this->input('title')),
            'url' => LinkUrl::normalize($this->input('url')),
            'description' => filled($this->input('description')) ? trim((string) $this->input('description')) : null,
            'category' => filled($this->input('category')) ? trim((string) $this->input('category')) : null,
            'icon' => filled($this->input('icon')) ? $this->input('icon') : null,
            'for_all' => $this->boolean('for_all'),
            'pinned' => $this->boolean('pinned'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'url' => ['required', 'string', 'max:'.LinkUrl::MAX_LENGTH],
            'description' => ['nullable', 'string', 'max:500'],
            'category' => ['nullable', 'string', 'max:80'],
            'icon' => ['nullable', 'string', Rule::in(array_keys(Link::ICONS))],
            'for_all' => ['boolean'],
            'pinned' => ['boolean'],
            'is_active' => ['boolean'],
            'gruppen' => ['nullable', 'array'],
            'gruppen.*' => ['integer', Rule::exists('groups', 'id')->whereNull('deleted_at')],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! $validator->errors()->has('url') && ! LinkUrl::isValid($this->input('url'))) {
                    $validator->errors()->add('url', 'Bitte eine gültige Adresse angeben: https://…, mailto:…, tel:… oder ein interner Pfad wie /termine.');
                }

                if (! $this->boolean('for_all') && empty($this->input('gruppen'))) {
                    $validator->errors()->add('gruppen', 'Bitte mindestens eine Gruppe wählen oder „Für alle Nutzer“ aktivieren.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'Titel',
            'url' => 'Adresse',
            'description' => 'Beschreibung',
            'category' => 'Kategorie',
            'icon' => 'Symbol',
            'gruppen' => 'Gruppen',
        ];
    }

    /** Validierte Felder für das Modell (ohne Gruppen). */
    public function linkData(): array
    {
        return collect($this->validated())->except('gruppen')->all();
    }

    /** @return int[] */
    public function groupIds(): array
    {
        return $this->boolean('for_all') ? [] : array_map('intval', $this->input('gruppen', []));
    }
}
