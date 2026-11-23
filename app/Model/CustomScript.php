<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Ein instanzspezifisches HTML/JavaScript-Snippet (z. B. Chatbot, Gag in der Anwesenheitsliste).
 *
 * Ausgegeben wird es nur, wenn es aktiv ist und Zielgruppe (Kontoart, Rollen) sowie
 * Seitenregel passen. Siehe CustomScriptRenderer.
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property bool $active
 * @property string $snippet
 * @property string $position
 * @property string $page_mode
 * @property string|null $page_patterns
 * @property bool $for_guests
 * @property bool $for_users
 * @property bool $for_students
 * @property bool $restrict_roles
 * @property bool $use_on_chat
 * @property int $sort_order
 * @property string|null $disabled_reason
 * @property int|null $updated_by
 */
class CustomScript extends Model
{
    public const POSITIONS = ['head', 'body'];

    public const PAGE_ALL = 'all';

    public const PAGE_ONLY = 'only';

    public const PAGE_EXCEPT = 'except';

    public const PAGE_MODES = [self::PAGE_ALL, self::PAGE_ONLY, self::PAGE_EXCEPT];

    protected $fillable = [
        'name', 'description', 'active', 'snippet', 'position', 'page_mode', 'page_patterns',
        'for_guests', 'for_users', 'for_students', 'restrict_roles', 'use_on_chat',
        'sort_order', 'disabled_reason', 'updated_by',
    ];

    protected $casts = [
        'active' => 'boolean',
        'for_guests' => 'boolean',
        'for_users' => 'boolean',
        'for_students' => 'boolean',
        'restrict_roles' => 'boolean',
        'use_on_chat' => 'boolean',
        'sort_order' => 'integer',
        'updated_by' => 'integer',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'custom_script_role', 'custom_script_id', 'role_id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Pfadmuster als bereinigte Liste (ohne Leerzeilen, ohne führenden/abschließenden "/").
     *
     * @return list<string>
     */
    public function patterns(): array
    {
        return self::parsePatterns($this->page_patterns);
    }

    /**
     * @return list<string>
     */
    public static function parsePatterns(?string $text): array
    {
        // "/" allein bleibt als "" erhalten und steht für die Startseite der Instanz
        return collect(preg_split('/\R/', (string) $text))
            ->map(fn (string $line) => trim($line))
            ->filter(fn (string $line) => $line !== '')
            ->map(fn (string $line) => trim($line, '/'))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Passt die Zielgruppe auf den (ggf. nicht angemeldeten) Nutzer?
     *
     * Gäste und Schülerkonten haben keine Rollen; für sie zählt nur die Kontoart.
     * Bei "Auf Rollen begrenzen" ohne verbliebene Rolle bekommt niemand das Skript.
     */
    public function matchesUser(?User $user): bool
    {
        if ($user === null) {
            return $this->for_guests;
        }

        if ($user->isStudent()) {
            return $this->for_students;
        }

        if (! $this->for_users) {
            return false;
        }

        if (! $this->restrict_roles) {
            return true;
        }

        $scriptRoles = $this->roles->pluck('id');

        if ($scriptRoles->isEmpty()) {
            return false;
        }

        return $user->roles->pluck('id')->intersect($scriptRoles)->isNotEmpty();
    }

    /**
     * Passt die Seitenregel auf den Pfad (relativ zur Instanz, ohne Query-String)?
     */
    public function matchesPath(string $path): bool
    {
        if ($this->page_mode === self::PAGE_ALL) {
            return true;
        }

        $patterns = $this->patterns();
        $path = Str::lower(trim($path, '/'));

        // Ohne Muster greift keine Begrenzung – im Zweifel nicht ausgeben
        if ($patterns === []) {
            return false;
        }

        $hit = false;
        foreach ($patterns as $pattern) {
            $regex = '#^'.str_replace('\*', '.*', preg_quote(Str::lower($pattern), '#')).'$#u';
            if (preg_match($regex, $path) === 1) {
                $hit = true;
                break;
            }
        }

        return $this->page_mode === self::PAGE_ONLY ? $hit : ! $hit;
    }

    /**
     * Kurzbeschreibung der Zielgruppe für die Übersicht.
     */
    public function audienceLabel(): string
    {
        $parts = [];
        if ($this->for_users) {
            $parts[] = $this->restrict_roles
                ? 'Rollen: '.($this->roles->isEmpty() ? '– keine –' : $this->roles->pluck('name')->sort()->implode(', '))
                : 'Angemeldete Konten';
        }
        if ($this->for_guests) {
            $parts[] = 'Gäste';
        }
        if ($this->for_students) {
            $parts[] = 'Schülerkonten';
        }

        return $parts === [] ? 'niemand' : implode(' · ', $parts);
    }

    /**
     * Kurzbeschreibung der Seitenregel für die Übersicht.
     */
    public function pageLabel(): string
    {
        return match ($this->page_mode) {
            self::PAGE_ONLY => 'Nur: '.implode(', ', $this->patterns()),
            self::PAGE_EXCEPT => 'Außer: '.implode(', ', $this->patterns()),
            default => 'Alle Seiten',
        };
    }
}
