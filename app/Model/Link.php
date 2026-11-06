<?php

namespace App\Model;

use App\Model\Concerns\HasAudience;
use App\Scopes\GetGroupsScope;
use App\Support\LinkUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Modul „Links“: ein von der Verwaltung gepflegter Link (intern oder extern).
 *
 * Sichtbar ist ein aktiver Link für alle angemeldeten Nutzer (`for_all`) oder für
 * Mitglieder der zugeordneten Gruppen. `pinned` zeigt ihn zusätzlich auf der Startseite.
 *
 * Zielgruppe (`zielgruppe`, nur mit Schülerkonten): wie bei Beiträgen. Links „nur für Schüler“
 * fehlen dank HasAudience überall außer in Verwaltung und Schüleransicht.
 */
class Link extends Model
{
    use HasAudience;

    /** Recht für die Verwaltung der Links (auch der Schülerlinks). */
    public const MANAGE_PERMISSION = 'edit links';

    /**
     * Auswählbare Symbole (Font Awesome 5) mit Bezeichnung. Die App bildet sie auf
     * eigene Symbole ab; der Schlüssel `icon_key` in der API ist der Teil nach „fa-“.
     */
    public const ICONS = [
        'fas fa-link' => 'Link',
        'fas fa-globe' => 'Webseite',
        'fas fa-school' => 'Schule',
        'fas fa-graduation-cap' => 'Lernen',
        'fas fa-book' => 'Buch',
        'fas fa-laptop' => 'Lernplattform',
        'fas fa-utensils' => 'Essen/Mensa',
        'fas fa-bus' => 'Fahrt/Bus',
        'fas fa-calendar-alt' => 'Kalender',
        'fas fa-file-alt' => 'Dokument',
        'fas fa-file-pdf' => 'PDF',
        'fas fa-download' => 'Download',
        'fas fa-images' => 'Fotos',
        'fas fa-video' => 'Video',
        'fas fa-users' => 'Gemeinschaft',
        'fas fa-hands-helping' => 'Förderverein',
        'fas fa-euro-sign' => 'Bezahlen',
        'fas fa-shopping-cart' => 'Bestellen',
        'fas fa-futbol' => 'Sport',
        'fas fa-music' => 'Musik',
        'fas fa-info-circle' => 'Information',
        'fas fa-question-circle' => 'Hilfe',
        'fas fa-envelope' => 'E-Mail',
        'fas fa-phone' => 'Telefon',
    ];

    protected $fillable = [
        'title', 'url', 'description', 'category', 'icon',
        'for_all', 'pinned', 'is_active', 'position', 'created_by', 'updated_by',
    ];

    protected $attributes = [
        'zielgruppe' => 'eltern',
    ];

    protected static function audienceManagePermission(): string
    {
        return self::MANAGE_PERMISSION;
    }

    protected function casts(): array
    {
        return [
            'for_all' => 'boolean',
            'pinned' => 'boolean',
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function groups(): BelongsToMany
    {
        // Ohne die Nutzer-Scopes von Group: die Zuordnung gilt unabhängig davon, wer sie ansieht.
        return $this->belongsToMany(Group::class, 'link_group')
            ->withoutGlobalScope(GetGroupsScope::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Aktive Links, die der Nutzer sehen darf. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query
            ->where('links.is_active', true)
            ->where(fn (Builder $q) => $q->where('links.for_all', true)
                ->orWhereExists(fn ($sub) => $sub->selectRaw(1)
                    ->from('link_group')
                    ->join('group_user', 'link_group.group_id', '=', 'group_user.group_id')
                    ->whereColumn('link_group.link_id', 'links.id')
                    ->where('group_user.user_id', $user->id)));
    }

    /**
     * Aktive Links für ein Schülerkonto: Zielgruppe mit Schülern und für alle oder für eine
     * Gruppe des verknüpften Kindes (nie über die Gruppen des Kontos selbst).
     *
     * @param  int[]  $groupIds  StudentContext::groupIds()
     */
    public function scopeVisibleToStudentGroups(Builder $query, array $groupIds): Builder
    {
        return $query->forStudentAudience()
            ->where('links.is_active', true)
            ->where(fn (Builder $q) => $q->where('links.for_all', true)
                ->when($groupIds !== [], fn (Builder $q) => $q->orWhereExists(fn ($sub) => $sub->selectRaw(1)
                    ->from('link_group')
                    ->whereColumn('link_group.link_id', 'links.id')
                    ->whereIn('link_group.group_id', $groupIds))));
    }

    /**
     * Für Schüler nutzbar: interne Pfade nur in die Schüleransicht (`/schueler…`),
     * weil Schülerkonten alle anderen Seiten des Boards nicht erreichen.
     */
    public function usableByStudents(): bool
    {
        if ($this->type() !== 'internal') {
            return true;
        }

        $path = (string) (parse_url($this->href(), PHP_URL_PATH) ?? '/');

        return $path === '/schueler' || str_starts_with($path, '/schueler/');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('links.position')->orderBy('links.title')->orderBy('links.id');
    }

    /**
     * Links nach Kategorie gruppiert, in Reihenfolge des ersten Auftretens.
     * Links ohne Kategorie stehen unter dem Schlüssel '' vorne.
     *
     * @param  Collection<int, Link>  $links
     * @return Collection<string, Collection<int, Link>>
     */
    public static function grouped(Collection $links): Collection
    {
        $grouped = $links->toBase()->groupBy(fn (Link $link) => (string) $link->category);

        return $grouped->has('')
            ? collect(['' => $grouped->get('')])->merge($grouped->except(''))
            : $grouped;
    }

    /** Art des Links: internal | external | mail | phone */
    public function type(): string
    {
        return LinkUrl::type($this->url);
    }

    /** Vollständige URL (interne Pfade werden zur Adresse dieser Instanz ergänzt). */
    public function href(): string
    {
        return LinkUrl::absolute($this->url);
    }

    public function opensInNewTab(): bool
    {
        return $this->type() === 'external';
    }

    /** Anzeigename der Zieladresse, z. B. „schule.de“. */
    public function displayHost(): string
    {
        return LinkUrl::display($this->url);
    }

    public function iconClass(): string
    {
        if ($this->icon && array_key_exists($this->icon, self::ICONS)) {
            return $this->icon;
        }

        return match ($this->type()) {
            'mail' => 'fas fa-envelope',
            'phone' => 'fas fa-phone',
            'internal' => 'fas fa-arrow-right',
            default => 'fas fa-external-link-alt',
        };
    }

    /** Symbolname ohne Präfix, z. B. „utensils“ (für die App). */
    public function iconKey(): string
    {
        return (string) Str::after($this->iconClass(), 'fa-');
    }
}
