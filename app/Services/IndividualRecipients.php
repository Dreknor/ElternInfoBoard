<?php

namespace App\Services;

use App\Model\User;
use App\Settings\GeneralSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * Direkte Freigabe von Beiträgen, Terminen und Listen an einzelne Personen
 * (Formularfeld `personen[]`, nur wenn general.allow_individual_recipients aktiv ist).
 */
class IndividualRecipients
{
    public static function enabled(): bool
    {
        return (bool) (app(GeneralSetting::class)->allow_individual_recipients ?? false);
    }

    /**
     * Validierungsregeln für Gruppen und Personen: mindestens eines von beiden ist Pflicht.
     *
     * @param  array  $groupRules  Bisherige Regeln für `gruppen`, wenn die Funktion deaktiviert ist
     */
    public static function rules(array $groupRules = ['required']): array
    {
        if (! self::enabled()) {
            return [
                'gruppen' => $groupRules,
            ];
        }

        return [
            'gruppen' => ['required_without:personen', 'nullable', 'array'],
            'personen' => ['required_without:gruppen', 'nullable', 'array'],
            'personen.*' => ['integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
        ];
    }

    public static function messages(): array
    {
        $message = 'Bitte mindestens eine Gruppe oder eine Person auswählen.';

        return [
            'gruppen.required' => 'Bitte mindestens eine Gruppe auswählen.',
            'gruppen.required_without' => $message,
            'personen.required_without' => $message,
        ];
    }

    /**
     * Übernimmt die ausgewählten Personen. Ist die Funktion deaktiviert, bleiben
     * bestehende Freigaben unverändert (das Formular zeigt die Auswahl dann nicht an).
     *
     * @param  Model  $model  Post, Termin oder Liste (Relation recipientUsers)
     */
    public static function sync(Model $model, Request $request): void
    {
        if (! self::enabled()) {
            return;
        }

        $model->recipientUsers()->sync(self::idsFromRequest($request));
    }

    public static function idsFromRequest(Request $request): array
    {
        return collect($request->input('personen', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Auswahlliste für das Formular (id, Name, E-Mail zur Unterscheidung gleichnamiger Personen).
     */
    public static function selectableUsers(): Collection
    {
        return User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])
            ->values();
    }
}
