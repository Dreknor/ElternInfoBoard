<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Familie / Haushalt – Abrechnungs- und Organisationseinheit
 * (Pflichtstunden, Reinigung, Rückmeldungen, Termine).
 *
 * @property string $name
 * @property string $source auto|manual|import|ucs|migration
 * @property bool $is_locked Automatik (FamilyBuilder) fasst gesperrte Familien nie an
 *
 * @see docs/kind-zentriertes-familienmodell-konzept.md §4.2, §7
 */
class Family extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    public const SOURCE_AUTO = 'auto';

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_IMPORT = 'import';

    public const SOURCE_UCS = 'ucs';

    public const SOURCE_MIGRATION = 'migration';

    protected $fillable = ['name', 'source', 'is_locked', 'notes'];

    protected function casts(): array
    {
        return [
            'is_locked' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Kinder aller Familienmitglieder (abgeleitet, nicht gespeichert).
     */
    public function childrenQuery(): Builder
    {
        return Child::query()
            ->whereHas('parents', fn ($q) => $q->where('users.family_id', $this->id))
            ->distinct();
    }

    public function children()
    {
        return $this->childrenQuery()->get();
    }

    /**
     * Vorschlag für einen Familiennamen aus den Mitgliedernamen.
     *
     * @param  iterable<User>  $members
     */
    public static function suggestName(iterable $members): string
    {
        $lastNames = collect($members)
            ->map(fn (User $user) => trim((string) $user->familieName))
            ->filter()
            ->unique()
            ->values();

        if ($lastNames->isEmpty()) {
            return 'Familie';
        }

        return 'Familie '.$lastNames->implode(' / ');
    }
}
