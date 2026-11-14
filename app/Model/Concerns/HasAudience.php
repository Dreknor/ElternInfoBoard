<?php

namespace App\Model\Concerns;

use App\Model\Scopes\ParentAudienceScope;
use App\Model\User;
use App\Support\StudentAccounts;
use Illuminate\Database\Eloquent\Builder;

/**
 * Zielgruppe von Beiträgen und Terminen (`zielgruppe`: eltern | eltern_schueler | schueler),
 * docs/konzept-schuelerkonten.md §3, Paket S-05.
 *
 * Standard ist der globale ParentAudienceScope: `schueler` fehlt überall. Wer Einträge für
 * Schüler verwaltet (Autor, Recht „create student posts“ oder das Bearbeitungsrecht des Moduls),
 * erreicht sie über die Route-Bindung und `withStudentAudience()`.
 */
trait HasAudience
{
    public static function bootHasAudience(): void
    {
        static::addGlobalScope(new ParentAudienceScope);
    }

    /** Bearbeitungsrecht des Moduls (z. B. „edit posts“), das auch Schülereinträge umfasst. */
    abstract protected static function audienceManagePermission(): string;

    /** Spalte mit dem Autor (oder null), der seine Schülereinträge immer sieht. */
    protected static function audienceAuthorColumn(): ?string
    {
        return null;
    }

    /** Auch Einträge „nur für Schüler“ (nur Verwaltung und Schüleransicht). */
    public function scopeWithStudentAudience(Builder $query): Builder
    {
        return $query->withoutGlobalScope(ParentAudienceScope::class);
    }

    /** Nur Einträge, die Schüler sehen dürfen (`eltern_schueler`, `schueler`). */
    public function scopeForStudentAudience(Builder $query): Builder
    {
        return $query->withoutGlobalScope(ParentAudienceScope::class)
            ->whereIn($query->qualifyColumn('zielgruppe'), [
                StudentAccounts::ZIELGRUPPE_ELTERN_SCHUELER,
                StudentAccounts::ZIELGRUPPE_SCHUELER,
            ]);
    }

    /** Schülereinträge, die dieser Nutzer verwalten darf (eigene bzw. alle mit Recht). */
    public function scopeStudentOnlyManagedBy(Builder $query, User $user): Builder
    {
        $query->withoutGlobalScope(ParentAudienceScope::class)
            ->where($query->qualifyColumn('zielgruppe'), StudentAccounts::ZIELGRUPPE_SCHUELER);

        if (! static::managesStudentAudience($user)) {
            $author = static::audienceAuthorColumn();
            $author === null
                ? $query->whereRaw('1 = 0')
                : $query->where($query->qualifyColumn($author), $user->id);
        }

        return $query;
    }

    public static function managesStudentAudience(?User $user): bool
    {
        if ($user === null || $user->isStudent()) {
            return false;
        }

        return $user->can(static::audienceManagePermission())
            || (StudentAccounts::enabled() && $user->can(StudentAccounts::PERMISSION_CREATE_POSTS));
    }

    /** Nur für Schüler (Eltern sehen das nie). */
    public function isStudentOnly(): bool
    {
        return $this->zielgruppe === StudentAccounts::ZIELGRUPPE_SCHUELER;
    }

    /** Für Schüler sichtbar (`eltern_schueler` oder `schueler`). */
    public function isForStudents(): bool
    {
        return in_array($this->zielgruppe, [StudentAccounts::ZIELGRUPPE_ELTERN_SCHUELER, StudentAccounts::ZIELGRUPPE_SCHUELER], true);
    }

    /**
     * Route-Bindung: Verwaltende (Autor, Bearbeitungsrecht, Schülerrecht) erreichen auch
     * Einträge „nur für Schüler“, alle anderen nicht (404).
     */
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        $query = parent::resolveRouteBindingQuery($query, $value, $field);

        $user = auth()->user();
        if (! $user instanceof User || $user->isStudent()) {
            return $query;
        }

        if (static::managesStudentAudience($user)) {
            return $query->withoutGlobalScope(ParentAudienceScope::class);
        }

        $author = static::audienceAuthorColumn();
        if ($author !== null) {
            $query->withoutGlobalScope(ParentAudienceScope::class)
                ->where(fn ($q) => $q->where($q->qualifyColumn('zielgruppe'), '!=', StudentAccounts::ZIELGRUPPE_SCHUELER)
                    ->orWhere($q->qualifyColumn($author), $user->id));
        }

        return $query;
    }
}
