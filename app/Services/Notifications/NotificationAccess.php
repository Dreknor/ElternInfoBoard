<?php

namespace App\Services\Notifications;

use App\Model\Module;
use App\Model\User;
use App\Services\Push\PushTarget;
use Illuminate\Support\Collection;

/**
 * Modulbezogene Benachrichtigungen (Glocke, App-/Browser-Push, E-Mail) nur an Nutzer,
 * die das zugehörige Modul noch sehen dürfen: Modul aktiv und eines der Modulrechte
 * (`options.rights`) bzw. eines der Verwaltungsrechte (`adm-rights`) vorhanden.
 *
 * Das Modul wird aus dem Typ der Benachrichtigung abgeleitet, ersatzweise aus der Ziel-URL.
 * Benachrichtigungen ohne Modulbezug (Nachrichten, Erinnerungen, System …) bleiben unberührt.
 * Hort und Krankmeldungen sind bewusst ausgenommen: Hinweise zum eigenen Kind sollen
 * Sorgeberechtigte immer erreichen.
 */
final class NotificationAccess
{
    /** Typ (klein geschrieben) → Modul (`settings_modules.setting`). */
    private const MODULE_BY_TYPE = [
        'vertretung' => 'Vertretungsplan',
        'vertretungsplan' => 'Vertretungsplan',
        'messenger' => 'Eltern-Nachrichten',
        'conversation' => 'Eltern-Nachrichten',
        'elternrat' => 'Elternrat',
        'reinigung' => 'Reinigung',
        'pflichtstunden' => 'Pflichtstunden',
        'ags' => 'Arbeitsgemeinschaften',
        'elternabfrage' => 'Elternabfragen',
        'elternabfragen' => 'Elternabfragen',
        'termin' => 'Termine',
        'termine' => 'Termine',
        'liste' => 'Listen',
        'listen' => 'Listen',
        'listen eintragung' => 'Listen',
    ];

    /** Ziel aus der URL (PushTarget) → Modul. */
    private const MODULE_BY_TARGET = [
        'vertretungsplan' => 'Vertretungsplan',
        'conversation' => 'Eltern-Nachrichten',
        'elternrat' => 'Elternrat',
        'reinigung' => 'Reinigung',
        'pflichtstunden' => 'Pflichtstunden',
        'ags' => 'Arbeitsgemeinschaften',
        'inquiry' => 'Elternabfragen',
        'termin' => 'Termine',
        'liste' => 'Listen',
    ];

    /** Modul, dessen Recht für die Benachrichtigung nötig ist, oder null ohne Modulbezug. */
    public static function module(?string $type, ?string $url = null): ?string
    {
        $byType = self::MODULE_BY_TYPE[mb_strtolower(trim((string) $type))] ?? null;
        if ($byType) {
            return $byType;
        }

        return self::MODULE_BY_TARGET[PushTarget::fromUrl($url)['type'] ?? ''] ?? null;
    }

    /**
     * Prüffunktion für viele Nutzer (Modul wird nur einmal geladen).
     *
     * @return \Closure(User|int|null): bool
     */
    public static function checker(?string $type, ?string $url = null): \Closure
    {
        $setting = self::module($type, $url);
        if ($setting === null) {
            return fn ($user) => true;
        }

        $module = Module::where('setting', $setting)->first();
        if (! $module || ! self::isActive($module)) {
            return fn ($user) => false;
        }

        $rights = self::rights($module);
        if ($rights === []) {
            return fn ($user) => true;
        }

        return function ($user) use ($rights) {
            $model = $user instanceof User ? $user : ($user ? User::find($user) : null);

            return $model !== null && collect($rights)->contains(fn (string $right) => $model->can($right));
        };
    }

    public static function allows(User|int|null $user, ?string $type, ?string $url = null): bool
    {
        return (self::checker($type, $url))($user);
    }

    /**
     * @template T of User|int
     *
     * @param  iterable<T>  $users
     * @return Collection<int, T>|\Illuminate\Database\Eloquent\Collection<int, T>
     */
    public static function filter(iterable $users, ?string $type, ?string $url = null): Collection
    {
        $users = $users instanceof Collection ? $users : collect($users);
        if (self::module($type, $url) === null) {
            return $users;
        }

        $check = self::checker($type, $url);

        return $users->filter(fn ($user) => $check($user))->values();
    }

    /**
     * @param  int[]  $userIds
     * @return int[]
     */
    public static function filterIds(array $userIds, ?string $type, ?string $url = null): array
    {
        if ($userIds === [] || self::module($type, $url) === null) {
            return $userIds;
        }

        $check = self::checker($type, $url);
        $allowed = User::whereIn('id', $userIds)->get()->filter(fn (User $user) => $check($user))->pluck('id')->all();

        return array_values(array_intersect($userIds, $allowed));
    }

    private static function isActive(Module $module): bool
    {
        $active = $module->options['active'] ?? false;

        return $active === true || $active === 1 || $active === '1';
    }

    /**
     * Modulrechte plus Verwaltungsrechte (Verwaltende bleiben benachrichtigt).
     *
     * @return string[]
     */
    private static function rights(Module $module): array
    {
        $options = $module->options ?? [];
        $rights = $options['rights'] ?? [];
        if (is_string($rights)) {
            $rights = json_decode($rights, true) ?? [];
        }
        $rights = array_values((array) $rights);
        if ($rights === []) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_merge($rights, self::admRights($options)),
            'is_string'
        )));
    }

    /** @return string[] */
    private static function admRights(mixed $node): array
    {
        if (! is_array($node)) {
            return [];
        }
        $found = [];
        foreach ($node as $key => $value) {
            if ($key === 'adm-rights') {
                $found = array_merge($found, array_values((array) $value));
            } elseif (is_array($value)) {
                $found = array_merge($found, self::admRights($value));
            }
        }

        return $found;
    }
}
