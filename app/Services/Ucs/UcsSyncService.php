<?php

namespace App\Services\Ucs;

use App\Model\Child;
use App\Model\Group;
use App\Model\UcsLinkCandidate;
use App\Model\User;
use App\Services\Ucs\Dto\KelvinStudentDto;
use App\Services\Ucs\Dto\KelvinUserDto;
use App\Settings\UcsSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Throwable;

/**
 * UcsSyncService – Orchestrierung des Kelvin-Daten-Syncs.
 *
 * Verantwortlichkeiten:
 * – Bulk-Sync aller Erziehungsberechtigten + Kinder einer Schule (run())
 * – JIT-Sync eines einzelnen Elternteils beim OIDC-Login (syncSingleParent())
 * – Strenge Auto/Manuell-Trennung (is_auto_provisioned, ucs_source)
 * – Idempotente Upsert-Logik ohne Überschreiben lokaler Daten
 * – Initial-Linking-Erkennung statt Duplikat-Anlage
 * – Telemetrie: UcsSetting::last_sync_*, exklusiver Cache-Lock
 *
 * Kennungen (Kelvin):
 * – name        → users.ucs_username / children.ucs_username (stabiler Anker, immer vorhanden)
 * – record_uid  → users.ucs_uuid / children.ucs_uuid (Quellsystem-ID, optional, KEINE UUID)
 * Der OIDC-sub-Claim wird separat in users.ucs_oidc_sub gespeichert (UcsLoginController).
 *
 * @see docs/ucs-kelvin-integration-konzept.md §5, §5.2, §5.3, §7.3, §15.1
 */
class UcsSyncService
{
    /** Cache-Keys */
    private const LOCK_KEY        = 'ucs.sync.lock';
    private const JIT_MISS_PREFIX = 'ucs.jit.miss:';

    /** Negativ-Cache-TTL für unbekannte Usernames (Sekunden). */
    public const JIT_MISS_TTL = 60;

    /** Maximale Laufzeit des Bulk-Lock (Sekunden). */
    private const LOCK_SECONDS = 3600;

    /** Maximale Länge der gespeicherten Fehlermeldung (Telemetrie). */
    private const MAX_MSG_LEN = 200;

    /**
     * Pro Lauf aufgelöste Klassen-Gruppen (Klassenname → Group).
     * Vermeidet pro Elternteil erneute SELECT/UPDATE-Runden für dieselbe Klasse.
     *
     * @var array<string, Group>
     */
    private array $groupCache = [];

    public function __construct(
        private readonly KelvinClient $client,
        private readonly UcsSetting   $settings,
    ) {}

    /**
     * Einheitlicher Negativ-Cache-Key für unbekannte UCS-Usernames
     * (gemeinsam genutzt von UcsSyncService und UcsLoginController).
     */
    public static function jitMissKey(string $username): string
    {
        return self::JIT_MISS_PREFIX.sha1(mb_strtolower($username));
    }

    // =========================================================================
    // A. Bulk-Sync
    // =========================================================================

    /**
     * Führt den vollständigen Bulk-Sync für die konfigurierte Schule durch.
     *
     * Im Dry-Run-Modus werden ausschließlich Counts zurückgegeben,
     * keine Datenbankänderungen vorgenommen.
     *
     * @return array<string, mixed>
     *
     * @throws \RuntimeException Wenn die Integration deaktiviert, die Schule nicht
     *                           konfiguriert ist oder bereits ein Sync läuft.
     */
    public function run(bool $dryRun = false): array
    {
        $this->guardEnabled();

        $school    = $this->settings->school;
        $startedAt = microtime(true);
        $lock      = null;

        if (! $dryRun) {
            $lock = Cache::lock(self::LOCK_KEY, self::LOCK_SECONDS);

            if (! $lock->get()) {
                throw new \RuntimeException('Es läuft bereits ein UCS-Sync. Bitte später erneut versuchen.');
            }

            $this->writeTelemetry(status: 'running');
        }

        $this->groupCache = [];
        $counts = $this->emptyCounters($school, $dryRun);

        try {
            // ── Schritt 1: Alle Schüler vorab laden (O(1)-Lookup, Keys lowercase) ──
            $studentMap = $this->buildStudentMap($school);

            // Rückwärts-Index aus students.legal_guardians. Deckt Kelvin-Versionen/
            // Konfigurationen ab, in denen legal_wards am Elternteil fehlt.
            $wardsByGuardian = $this->buildGuardianIndex($studentMap);

            // ── Schritt 2 + 3: Eltern iterieren + pro Elternteil syncen ──
            $seenParentUuids     = [];
            $seenParentUsernames = [];

            foreach ($this->client->listParents($school) as $parentDto) {
                /** @var KelvinUserDto $parentDto */
                $counts['parents_processed']++;

                if ($parentDto->recordUid !== null) {
                    $seenParentUuids[] = $parentDto->recordUid;
                }
                if ($parentDto->username !== '') {
                    $seenParentUsernames[] = $parentDto->username;
                }

                try {
                    $wardUsernames = $this->wardUsernamesFor($parentDto, $wardsByGuardian);

                    DB::transaction(function () use (
                        $parentDto, $wardUsernames, $studentMap, $school, $dryRun, &$counts
                    ) {
                        $user = $this->upsertUser($parentDto, $dryRun, $counts);

                        // upsertUser gibt null zurück bei Dry-Run (Wards trotzdem zählen)
                        // oder wenn der Elternteil übersprungen wurde (bereits geloggt/gezählt).
                        if ($user === null && ! $dryRun) {
                            return;
                        }

                        [$desiredGroupIds, $childIdMap, $desiredChildIds] = $this->processWards(
                            $user, $parentDto, $wardUsernames, $studentMap, $school, $dryRun, $counts
                        );

                        // ── Schritt 4: Pivot-Diff am Elternteil ──
                        if (! $dryRun && $user !== null) {
                            $this->syncChildPivots($user, $desiredChildIds, detach: true);
                            $this->syncGroupPivots($user, $desiredGroupIds, $childIdMap, detach: true);
                        }
                    });
                } catch (Throwable $e) {
                    $counts['failed_parents']++;
                    $this->log('error', 'Fehler bei Elternteil-Sync', [
                        'username' => $parentDto->username,
                        'name'     => trim($parentDto->firstname.' '.$parentDto->lastname),
                        'school'   => $parentDto->school,
                        'error'    => $e->getMessage(),
                    ]);
                    // Kein Re-Throw: nächster Elternteil wird trotzdem verarbeitet
                }
            }

            // ── Schritt 5: Orphan-Cleanup ──
            if (! $dryRun) {
                $this->orphanCleanup(
                    $school,
                    $seenParentUuids,
                    $seenParentUsernames,
                    $studentMap->map(fn (KelvinStudentDto $s) => $s->username)->values()->all(),
                    $counts,
                );
            }

            $counts['duration_seconds'] = round(microtime(true) - $startedAt, 2);

            if (! $dryRun) {
                $this->writeSuccessTelemetry($counts);
            }

            $this->log('info', 'Bulk-Sync abgeschlossen', $counts);

            return $counts;

        } catch (Throwable $e) {
            $msg = Str::limit($e->getMessage(), self::MAX_MSG_LEN);
            $this->log('error', 'Bulk-Sync fehlgeschlagen', ['error' => $msg]);

            if (! $dryRun) {
                $this->writeTelemetry(status: 'failed', message: $msg);
            }

            throw $e;
        } finally {
            if (! $dryRun) {
                $this->settings->last_sync_at = now()->toIso8601String();
                $this->settings->save();
                $lock?->release();
            }
        }
    }

    // =========================================================================
    // B. Single-Parent JIT-Sync (für OIDC-Callback §6.4)
    // =========================================================================

    /**
     * Synchronisiert einen einzelnen Elternteil und seine Kinder (JIT-Login).
     *
     * – Nur Accounts mit Rolle legal_guardian an der konfigurierten Schule
     * – Kein Detach von bestehenden Auto-Pivots (§6.4 Pkt. 4)
     * – Hard-Timeout: UcsSetting::on_login_timeout Sekunden pro Kelvin-Request
     * – Bei 404: Negativ-Cache setzen, null zurückgeben
     *
     * @param  string|null  $fallbackEmail  E-Mail aus dem OIDC-Token, falls Kelvin keine liefert.
     * @return User|null  Frisch geladener User für Auth::login, oder null.
     */
    public function syncSingleParent(string $username, ?string $fallbackEmail = null): ?User
    {
        $this->guardEnabled();

        $this->log('info', "syncSingleParent: [{$username}]");
        $this->groupCache = [];

        try {
            $parentData = $this->client->findUser($username, $this->settings->on_login_timeout);

            if ($parentData === null) {
                Cache::put(self::jitMissKey($username), true, self::JIT_MISS_TTL);
                $this->log('info', "syncSingleParent: 404 – Negativ-Cache gesetzt [{$username}]");

                return null;
            }

            $parentDto = KelvinUserDto::fromArray($parentData);
            $school    = $this->settings->school ?? '';

            if (! $parentDto->hasRole('legal_guardian')) {
                $this->log('info', 'syncSingleParent: Account ist kein Erziehungsberechtigter – keine Provisionierung', [
                    'username' => $username,
                    'roles'    => $parentDto->roles,
                ]);

                return null;
            }

            if (! $parentDto->belongsToSchool($school)) {
                $this->log('info', 'syncSingleParent: Elternteil gehört nicht zur konfigurierten Schule', [
                    'username' => $username,
                    'schools'  => $parentDto->schools,
                    'school'   => $school,
                ]);

                return null;
            }

            // Kinder einzeln laden (JIT: i. d. R. 1–4 Kinder) – außerhalb der Transaktion,
            // damit keine DB-Transaktion während HTTP-Requests offen bleibt.
            $studentMap = collect();
            foreach ($this->wardUsernamesFor($parentDto, []) as $wardUsername) {
                $wardData = $this->client->findUser($wardUsername, $this->settings->on_login_timeout);
                if ($wardData === null) {
                    continue;
                }

                $dto = KelvinStudentDto::fromArray($wardData);
                if (in_array('student', $dto->roles, true) && $dto->belongsToSchool($school)) {
                    $studentMap->put(mb_strtolower($dto->username), $dto);
                }
            }

            $counts = $this->emptyCounters($school, false);

            $user = DB::transaction(function () use ($parentDto, $studentMap, $school, $fallbackEmail, &$counts) {
                $user = $this->upsertUser($parentDto, false, $counts, $fallbackEmail);

                if ($user === null) {
                    return null;
                }

                [$desiredGroupIds, $childIdMap, $desiredChildIds] = $this->processWards(
                    $user, $parentDto, $studentMap->keys()->all(), $studentMap, $school, false, $counts
                );

                // Nur hinzufügen, KEINE bestehenden Pivots lösen (§6.4)
                $this->syncChildPivots($user, $desiredChildIds, detach: false);
                $this->syncGroupPivots($user, $desiredGroupIds, $childIdMap, detach: false);

                return $user;
            });

            if ($user === null) {
                return null;
            }

            Cache::forget(self::jitMissKey($username));
            $this->log('info', "syncSingleParent: erfolgreich [{$username}]", [
                'children' => $studentMap->count(),
            ]);

            return $user->fresh();

        } catch (Throwable $e) {
            $this->log('warning', "syncSingleParent: Fehler [{$username}]", [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    // =========================================================================
    // C. Hilfsmethoden
    // =========================================================================

    /**
     * Extrahiert den UCS-Username aus einer legal_wards/legal_guardians-URL.
     *
     * Erwartet: https://ucs.example.de/ucsschool/kelvin/v1/users/<username>
     * oder direkt einen Username. Validierung: nur [a-z0-9._-], max 120 Zeichen.
     *
     * @see docs/ucs-kelvin-integration-konzept.md §5.2
     */
    protected function extractWardUsername(string $url): ?string
    {
        $path     = parse_url($url, PHP_URL_PATH);
        $username = rawurldecode(basename((string) $path));

        if (! preg_match('/^[a-z0-9._-]{1,120}$/i', $username)) {
            $this->log('warning', 'Ungültige legal_wards-URL – Username übersprungen', [
                'url'      => $url,
                'username' => $username,
            ]);

            return null;
        }

        return $username;
    }

    /**
     * Gibt die Klassen-Gruppe für den gegebenen Klassennamen zurück (oder legt sie an).
     *
     * – Restore-Logik: SoftDeleted Gruppen werden „wiederbelebt".
     * – Bestehende lokale Klassen-Gruppen werden nur VERKNÜPFT (ucs_class_url),
     *   behalten aber ucs_source='local'. Dadurch werden sie nie automatisch
     *   soft-/hard-gelöscht (inkl. ihrer Beiträge, Termine etc.).
     */
    protected function resolveClassGroup(string $className, string $classUrl): Group
    {
        if (isset($this->groupCache[$classUrl])) {
            return $this->groupCache[$classUrl];
        }

        // 1. Match über ucs_class_url (eindeutig)
        $group = Group::withTrashed()
            ->where('ucs_class_url', $classUrl)
            ->first();

        // 2. Match über Name + bereich='Klasse' (Gruppen aus der Zeit vor der Kelvin-Anbindung)
        if ($group === null) {
            $group = Group::withTrashed()
                ->whereNull('ucs_class_url')
                ->where('name', $className)
                ->where('bereich', 'Klasse')
                ->first();

            if ($group !== null) {
                $group->ucs_class_url = $classUrl;
                $this->log('info', "Bestehende Klassen-Gruppe verknüpft [{$className}]", ['group_id' => $group->id]);
            }
        }

        if ($group !== null) {
            if ($group->trashed()) {
                $group->restore();
                $this->log('info', "Klassen-Gruppe wiederhergestellt [{$className}]");
            }

            if ($group->ucs_source === 'kelvin') {
                $group->name = $className;
            }
            $group->ucs_synced_at = now();
            $group->save();

            return $this->groupCache[$classUrl] = $group;
        }

        // 3. Neue Gruppe anlegen
        return $this->groupCache[$classUrl] = Group::create([
            'name'          => $className,
            'bereich'       => 'Klasse',
            'ucs_class_url' => $classUrl,
            'ucs_source'    => 'kelvin',
            'ucs_synced_at' => now(),
            'protected'     => false,
        ]);
    }

    /**
     * Prüft, ob ein lokales Kind ohne ucs_username existiert,
     * das namentlich und klassenweise zu einem UCS-Kind passt.
     *
     * Kein Match → null; Match → das lokale Child-Modell.
     */
    protected function detectLinkCandidate(KelvinStudentDto $dto, string $school): ?Child
    {
        $classNames = $dto->classesFor($school);

        if (empty($classNames)) {
            return null;
        }

        $groupIds = Group::whereIn('name', $classNames)
            ->where('bereich', 'Klasse')
            ->pluck('id');

        if ($groupIds->isEmpty()) {
            return null;
        }

        return Child::whereNull('ucs_username')
            ->where('ucs_source', 'local')
            ->where('first_name', $dto->firstname)
            ->where('last_name', $dto->lastname)
            ->whereIn('class_id', $groupIds)
            ->first();
    }

    // =========================================================================
    // Private – Upserts
    // =========================================================================

    /**
     * Legt einen Elternteil-User an oder aktualisiert ihn.
     *
     * Match-Strategie (in Prioritätsreihenfolge):
     *  1. ucs_username (Kelvin „name", immer vorhanden)
     *  2. ucs_uuid     (Kelvin record_uid, nur wenn vorhanden – erkennt Umbenennungen)
     *  3. email        (nur lokale Konten, die noch mit KEINEM UCS-Account verknüpft sind)
     *
     * Gibt null zurück wenn:
     *  – Dry-Run (kein DB-Write)
     *  – Neuanlage nicht möglich (fehlende oder bereits vergebene E-Mail)
     *  – der passende lokale User gelöscht wurde (Admin-Entscheidung wird respektiert)
     *
     * @param  array<string, int>  $counts  Referenz-Array für Counter
     */
    private function upsertUser(KelvinUserDto $dto, bool $dryRun, array &$counts, ?string $fallbackEmail = null): ?User
    {
        $email = $dto->email ?? ($fallbackEmail ? mb_strtolower(trim($fallbackEmail)) : null);
        $user  = null;

        if ($dto->username !== '') {
            $user = User::withTrashed()->where('ucs_username', $dto->username)->first();
        }

        if ($user === null && $dto->recordUid !== null) {
            $user = User::withTrashed()->where('ucs_uuid', $dto->recordUid)->first();
        }

        if ($user === null && $email !== null) {
            $user = User::where('email', $email)
                ->whereNull('ucs_username')
                ->first();

            if ($user !== null) {
                $this->log('info', 'Bestehendes lokales Konto per E-Mail mit UCS-Account verknüpft', [
                    'user_id'  => $user->id,
                    'username' => $dto->username,
                ]);
            }
        }

        $context = [
            'username'   => $dto->username,
            'record_uid' => $dto->recordUid,
            'name'       => trim($dto->firstname.' '.$dto->lastname),
            'school'     => $dto->school,
        ];

        if ($user !== null && $user->trashed()) {
            $counts['parents_skipped']++;
            $this->log('warning', 'Elternteil übersprungen – lokales Konto wurde gelöscht', $context + ['user_id' => $user->id]);

            return null;
        }

        if ($user === null) {
            // ── Neuanlage erfordert eine eindeutige E-Mail (users.email NOT NULL) ──
            $problem = match (true) {
                $email === null => 'keine E-Mail-Adresse in UCS (Hinweis: UDM-Attribut „e-mail" in Kelvin freigeben oder mailPrimaryAddress setzen)',
                User::withTrashed()->where('email', $email)->exists() => 'E-Mail-Adresse wird bereits von einem anderen Konto verwendet (z. B. gemeinsame Familien-Adresse)',
                default => null,
            };

            if ($problem !== null) {
                $counts['failed_parents']++;
                $this->log('error', "Elternteil nicht provisioniert – {$problem}", $context);

                return null;
            }
        }

        if ($dryRun) {
            $counts[$user === null ? 'parents_created' : 'parents_updated']++;

            return null;
        }

        if ($user === null) {
            $user = User::create([
                'name'           => trim($dto->firstname.' '.$dto->lastname),
                'email'          => $email,
                'ucs_uuid'       => $dto->recordUid,
                'ucs_username'   => $dto->username,
                'ucs_school'     => $dto->school,
                'ucs_source'     => 'kelvin',
                'ucs_synced_at'  => now(),
                'password'       => bcrypt(Str::random(32)), // nicht einlogbar ohne SSO/Passwort-Reset
                'changePassword' => 0,
                'is_active'      => ! $dto->disabled,
                'deactivated_at' => $dto->disabled ? now() : null,
            ]);
            $counts['parents_created']++;
            $this->log('info', "User angelegt [{$dto->username}]");

            return $user;
        }

        // Nur UCS-Felder + Name sanft aktualisieren;
        // Passwort, Rollen, Einstellungen werden NICHT angefasst.
        $updates = [
            'ucs_uuid'      => $dto->recordUid ?? $user->ucs_uuid,
            'ucs_username'  => $dto->username,
            'ucs_school'    => $dto->school,
            'ucs_synced_at' => now(),
        ];

        if ($dto->disabled && $user->is_active) {
            $updates['is_active']      = false;
            $updates['deactivated_at'] = now();
        } elseif (! $dto->disabled && ! $user->is_active && $user->ucs_source === 'kelvin') {
            // Nur vom Sync deaktivierte Kelvin-Konten reaktivieren, lokale Sperren respektieren.
            $updates['is_active']      = true;
            $updates['deactivated_at'] = null;
        }

        if ($user->ucs_source === 'kelvin') {
            $updates['name'] = trim($dto->firstname.' '.$dto->lastname);

            // Geänderte E-Mail aus UCS übernehmen, sofern nicht anderweitig vergeben
            if ($email !== null && mb_strtolower((string) $user->email) !== $email
                && ! User::withTrashed()->where('email', $email)->whereKeyNot($user->id)->exists()) {
                $updates['email'] = $email;
            }
        }

        $user->update($updates);
        $counts['parents_updated']++;

        return $user;
    }

    /**
     * Verarbeitet alle Kinder (Wards) eines Elternteils.
     *
     * @param  User|null                             $user           Null im Dry-Run (kein DB-Write)
     * @param  list<string>                          $wardUsernames
     * @param  Collection<string, KelvinStudentDto>  $studentMap     Keys: lowercase Username
     * @param  array<string, int>                    $counts
     * @return array{0: list<int>, 1: array<int, int>, 2: list<int>}  [$desiredGroupIds, $childIdMap, $desiredChildIds]
     */
    private function processWards(
        ?User         $user,
        KelvinUserDto $parentDto,
        array         $wardUsernames,
        Collection    $studentMap,
        string        $school,
        bool          $dryRun,
        array         &$counts,
    ): array {
        $desiredGroupIds = [];
        $childIdMap      = [];
        $desiredChildIds = [];

        foreach ($wardUsernames as $wardUsername) {
            /** @var KelvinStudentDto|null $studentDto */
            $studentDto = $studentMap->get(mb_strtolower($wardUsername));

            if ($studentDto === null) {
                $this->log('info', 'legal_ward nicht an der konfigurierten Schule gefunden', [
                    'ward'   => $wardUsername,
                    'parent' => $parentDto->username,
                ]);
                continue;
            }

            $child = $this->upsertChild($studentDto, $school, $dryRun, $counts);

            if ($child === null) {
                continue; // Dry-Run oder Link-Candidate-Pfad
            }

            $desiredChildIds[] = $child->id;

            $classNames = $studentDto->classesFor($school);
            if (empty($classNames)) {
                $this->log('warning', 'Kind ohne Klasse – kein class_id gesetzt', [
                    'student' => $studentDto->username,
                ]);
                continue;
            }

            // Kombiklasse: >1 Klassen → erste alphabetisch für class_id
            sort($classNames);
            $primaryClass = $classNames[0];

            foreach ($classNames as $className) {
                $group = $this->resolveClassGroup($className, $this->buildClassUrl($className, $school));

                $counts['groups_provisioned']++;

                // class_id nur für Kelvin-Kinder setzen (lokale Pflege respektieren)
                if ($child->ucs_source === 'kelvin' && $className === $primaryClass && (int) $child->class_id !== (int) $group->id) {
                    $child->update(['class_id' => $group->id]);
                }

                $desiredGroupIds[]      = $group->id;
                $childIdMap[$group->id] = $child->id;
            }
        }

        // Eltern-Rolle zuweisen, sobald mind. ein Kind verknüpft ist
        if (! $dryRun && $user !== null && $desiredChildIds !== []) {
            $this->ensureElternRole($user);
        }

        return [array_values(array_unique($desiredGroupIds)), $childIdMap, array_values(array_unique($desiredChildIds))];
    }

    /**
     * Legt ein Kind an oder aktualisiert es (Upsert-Logik §5.2).
     *
     * Gibt null zurück, wenn ein Link-Candidate angelegt wurde statt eines neuen Datensatzes,
     * oder im Dry-Run.
     */
    private function upsertChild(
        KelvinStudentDto $dto,
        string           $school,
        bool             $dryRun,
        array            &$counts,
    ): ?Child {
        // Match per (ucs_school, ucs_username), sonst per ucs_uuid (record_uid).
        // ❗ where('ucs_uuid', null) würde zu WHERE ucs_uuid IS NULL und damit auf
        //    beliebige lokale Kinder matchen – daher nur mit gesetzter record_uid.
        $child = Child::withoutGlobalScopes()->withTrashed()
            ->where('ucs_school', $school)
            ->where('ucs_username', $dto->username)
            ->first();

        if ($child === null && $dto->recordUid !== null) {
            $child = Child::withoutGlobalScopes()->withTrashed()
                ->where('ucs_uuid', $dto->recordUid)
                ->first();
        }

        // Per Link-Kandidat/CLI verknüpfte lokale Kinder haben ggf. noch keine ucs_school
        if ($child === null) {
            $child = Child::withoutGlobalScopes()->withTrashed()
                ->whereNull('ucs_school')
                ->where('ucs_username', $dto->username)
                ->first();
        }

        if ($child !== null) {
            if ($child->ucs_source === 'local') {
                // Nur ucs_uuid / ucs_username backfillen – NIEMALS Daten überschreiben
                if (! $dryRun && (empty($child->ucs_username) || empty($child->ucs_uuid) || empty($child->ucs_school))) {
                    $child->update([
                        'ucs_uuid'     => $child->ucs_uuid     ?: $dto->recordUid,
                        'ucs_username' => $child->ucs_username ?: $dto->username,
                        'ucs_school'   => $child->ucs_school   ?: $school,
                    ]);
                }
                $counts['children_skipped_local']++;

                return ($dryRun || $child->trashed()) ? null : $child;
            }

            // ucs_source='kelvin': Namen + Sync-Timestamp updaten, ggf. wiederherstellen
            if (! $dryRun) {
                if ($child->trashed()) {
                    $child->restore();
                    $this->log('info', 'Kind wiederhergestellt', ['child_id' => $child->id, 'username' => $dto->username]);
                }

                $child->update([
                    'first_name'    => $dto->firstname,
                    'last_name'     => $dto->lastname,
                    'ucs_uuid'      => $dto->recordUid ?? $child->ucs_uuid,
                    'ucs_username'  => $dto->username,
                    'ucs_school'    => $school,
                    'ucs_synced_at' => now(),
                ]);
            }
            $counts['children_updated']++;

            return $dryRun ? null : $child;
        }

        // Kein Match → Duplikat-Check: Lokales Kind mit gleichem Namen in einer der UCS-Klassen?
        $linkCandidate = $this->detectLinkCandidate($dto, $school);

        if ($linkCandidate !== null) {
            $existing = UcsLinkCandidate::where('child_id', $linkCandidate->id)
                ->where('ucs_username', $dto->username)
                ->first();

            if ($existing === null) {
                $counts['link_candidates_created']++;

                if (! $dryRun) {
                    UcsLinkCandidate::create([
                        'child_id'     => $linkCandidate->id,
                        'ucs_username' => $dto->username,
                        'ucs_uuid'     => $dto->recordUid,
                        'reason'       => 'name_match',
                        'payload'      => $dto->raw,
                        'detected_at'  => now(),
                    ]);
                    $this->log('info', 'Link-Kandidat erkannt – kein Duplikat angelegt', [
                        'local_child_id' => $linkCandidate->id,
                        'ucs_username'   => $dto->username,
                    ]);
                }
            } else {
                $this->log('debug', 'Link-Kandidat bereits vorhanden', [
                    'candidate_id' => $existing->id,
                    'rejected'     => ($existing->payload['status'] ?? '') === 'rejected',
                ]);
            }

            return null;
        }

        // Neues Kind anlegen
        $counts['children_created']++;

        if ($dryRun) {
            return null;
        }

        return Child::create([
            'first_name'    => $dto->firstname,
            'last_name'     => $dto->lastname,
            'ucs_uuid'      => $dto->recordUid,
            'ucs_username'  => $dto->username,
            'ucs_school'    => $school,
            'ucs_source'    => 'kelvin',
            'ucs_synced_at' => now(),
        ]);
    }

    /**
     * Synchronisiert die Auto-Kind-Pivots (child_user) eines Users.
     *
     * – Manuelle Verknüpfungen (is_auto_provisioned=false) bleiben unverändert
     *   und werden NICHT in Auto-Pivots umgewandelt.
     * – detach=true (Bulk): Auto-Pivots, die nicht mehr im Soll-Set sind, werden gelöst
     *   (z. B. Sorgerecht in UCS entfernt).
     *
     * @param  list<int>  $desiredChildIds
     */
    private function syncChildPivots(User $user, array $desiredChildIds, bool $detach): void
    {
        $existing = DB::table('child_user')
            ->where('user_id', $user->id)
            ->pluck('is_auto_provisioned', 'child_id');

        if ($detach) {
            $toDetach = $existing
                ->filter(fn ($auto, $childId) => (bool) $auto && ! in_array((int) $childId, $desiredChildIds, true))
                ->keys()
                ->all();

            if ($toDetach !== []) {
                $user->children_rel()->detach($toDetach);
                $this->log('info', 'Auto-Kind-Verknüpfungen entfernt', ['user_id' => $user->id, 'child_ids' => $toDetach]);
            }
        }

        foreach ($desiredChildIds as $childId) {
            if (! $existing->has($childId)) {
                $user->children_rel()->attach($childId, [
                    'is_auto_provisioned' => true,
                    'relation'            => 'legal_guardian',
                    'synced_at'           => now(),
                ]);
            } elseif ((bool) $existing->get($childId)) {
                $user->children_rel()->updateExistingPivot($childId, ['synced_at' => now()]);
            }
        }
    }

    /**
     * Synchronisiert die Auto-Gruppen-Pivots eines Users (Herzstück §5.2).
     *
     * – Manuelle Pivots (is_auto_provisioned=false) bleiben immer unberührt und
     *   werden nicht in Auto-Pivots umgewandelt.
     * – detach=true (Bulk): Auto-Pivots, die nicht mehr im Soll-Set sind, werden gelöst.
     *
     * @param  list<int>        $desiredGroupIds
     * @param  array<int, int>  $childIdMap      [group_id => child_id]
     */
    private function syncGroupPivots(User $user, array $desiredGroupIds, array $childIdMap, bool $detach): void
    {
        $existing = DB::table('group_user')
            ->where('user_id', $user->id)
            ->pluck('is_auto_provisioned', 'group_id');

        if ($detach) {
            $toDetach = $existing
                ->filter(fn ($auto, $groupId) => (bool) $auto && ! in_array((int) $groupId, $desiredGroupIds, true))
                ->keys()
                ->all();

            if ($toDetach !== []) {
                $user->groups()->detach($toDetach);
                $this->log('info', 'Auto-Gruppen-Verknüpfungen entfernt', ['user_id' => $user->id, 'group_ids' => $toDetach]);
            }
        }

        foreach ($desiredGroupIds as $gid) {
            $pivot = [
                'provisioned_via_child_id' => $childIdMap[$gid] ?? null,
                'synced_at'                => now(),
            ];

            if (! $existing->has($gid)) {
                $user->groups()->attach($gid, $pivot + ['is_auto_provisioned' => true]);
            } elseif ((bool) $existing->get($gid)) {
                $user->groups()->updateExistingPivot($gid, $pivot);
            }
        }
    }

    // =========================================================================
    // Private – Orphan-Cleanup
    // =========================================================================

    /**
     * Bereinigt Datensätze, die nicht mehr in der Kelvin-Antwort auftauchen.
     *
     * ⚠️  Safety-Guards: Leere Eltern- bzw. Schülerliste → der jeweilige Teil
     *     des Cleanups wird übersprungen (kein Massen-Deaktivieren bei stillen
     *     API-Fehlern).
     *
     * @param  string[]  $seenParentUuids       record_uid der gesehenen Eltern (ohne null)
     * @param  string[]  $seenParentUsernames   Usernames der gesehenen Eltern
     * @param  string[]  $studentUsernames      ALLE Schüler der Schule laut Kelvin
     * @param  array<string, int>  $counts
     */
    private function orphanCleanup(
        string $school,
        array  $seenParentUuids,
        array  $seenParentUsernames,
        array  $studentUsernames,
        array  &$counts,
    ): void {
        if ($seenParentUsernames === [] && $seenParentUuids === []) {
            $this->log('warning', 'orphanCleanup: Keine Eltern von Kelvin erhalten – Eltern-Cleanup übersprungen (Safety-Guard).');
        } else {
            $this->cleanupOrphanParents($seenParentUuids, $seenParentUsernames, $counts);
        }

        if ($studentUsernames === []) {
            $this->log('warning', 'orphanCleanup: Keine Schüler von Kelvin erhalten – Kinder-/Klassen-Cleanup übersprungen (Safety-Guard).');

            return;
        }

        // Kinder: SoftDelete, wenn sie in Kelvin nicht mehr an der Schule sind.
        // Maßgeblich ist die vollständige Schülerliste – NICHT nur die in diesem
        // Lauf verarbeiteten Wards (sonst würden Kinder bei einem einzelnen
        // fehlgeschlagenen Elternteil fälschlich gelöscht).
        Child::withoutGlobalScopes()
            ->where('ucs_source', 'kelvin')
            ->where('ucs_school', $school)
            ->whereNotIn('ucs_username', $studentUsernames)
            ->each(function (Child $child) {
                $child->delete();
                $this->log('info', 'Kind SoftDeleted (nicht mehr in Kelvin)', [
                    'child_id' => $child->id,
                    'username' => $child->ucs_username,
                ]);
            });

        // Verwaiste Link-Kandidaten bereinigen (SoftDelete löst keinen FK-Cascade aus)
        $orphanCandidateCount = UcsLinkCandidate::whereDoesntHave('child')->delete();
        if ($orphanCandidateCount > 0) {
            $this->log('info', 'Verwaiste Link-Kandidaten gelöscht', ['count' => $orphanCandidateCount]);
        }

        // Klassen-Gruppen (nur von Kelvin angelegte): Auto-Pivots entfernen + SoftDelete.
        // Im aktuellen Lauf verwendete Gruppen haben ucs_synced_at ≈ now().
        Group::withoutGlobalScopes()
            ->where('ucs_source', 'kelvin')
            ->where('ucs_synced_at', '<', now()->subMinutes(120))
            ->each(function (Group $group) {
                $autoPivotIds = DB::table('group_user')
                    ->where('group_id', $group->id)
                    ->where('is_auto_provisioned', true)
                    ->pluck('user_id')
                    ->all();

                if ($autoPivotIds !== []) {
                    $group->users()->detach($autoPivotIds);
                }

                $group->delete(); // SoftDelete
                $this->log('info', 'Klassen-Gruppe SoftDeleted (Orphan)', [
                    'group_id' => $group->id,
                    'name'     => $group->name,
                ]);
            });
    }

    /**
     * Eltern, die in Kelvin nicht mehr (als legal_guardian der Schule) vorkommen:
     * – Auto-Pivots (Kinder + Gruppen) entfernen – auch bei lokal verknüpften Konten
     * – Von Kelvin angelegte Konten deaktivieren (kein Hard-Delete)
     *
     * @param  string[]  $seenParentUuids
     * @param  string[]  $seenParentUsernames
     * @param  array<string, int>  $counts
     */
    private function cleanupOrphanParents(array $seenParentUuids, array $seenParentUsernames, array &$counts): void
    {
        $orphans = User::query()
            ->whereNotNull('ucs_username')
            ->where(function ($q) {
                $q->where('ucs_source', 'kelvin')
                    ->orWhereExists(fn ($s) => $s->from('group_user')->whereColumn('group_user.user_id', 'users.id')->where('is_auto_provisioned', true))
                    ->orWhereExists(fn ($s) => $s->from('child_user')->whereColumn('child_user.user_id', 'users.id')->where('is_auto_provisioned', true));
            })
            ->when($seenParentUsernames !== [], fn ($q) => $q->whereNotIn('ucs_username', $seenParentUsernames))
            ->when($seenParentUuids !== [], fn ($q) => $q->where(
                fn ($q2) => $q2->whereNull('ucs_uuid')->orWhereNotIn('ucs_uuid', $seenParentUuids)
            ))
            ->get();

        foreach ($orphans as $orphan) {
            DB::table('group_user')->where('user_id', $orphan->id)->where('is_auto_provisioned', true)->delete();
            DB::table('child_user')->where('user_id', $orphan->id)->where('is_auto_provisioned', true)->delete();

            if ($orphan->ucs_source === 'kelvin' && $orphan->is_active) {
                $orphan->update([
                    'is_active'      => false,
                    'deactivated_at' => now(),
                ]);
                $counts['parents_deactivated']++;
            }

            $this->log('info', 'Elternteil nicht mehr in Kelvin – Auto-Verknüpfungen entfernt', [
                'user_id'     => $orphan->id,
                'username'    => $orphan->ucs_username,
                'deactivated' => $orphan->ucs_source === 'kelvin',
            ]);
        }
    }

    // =========================================================================
    // Private – Hilfsmethoden
    // =========================================================================

    /**
     * Baut alle Schüler der Schule in eine by-username (lowercase) indizierte Collection.
     *
     * @return Collection<string, KelvinStudentDto>
     */
    private function buildStudentMap(string $school): Collection
    {
        $map = collect();

        foreach ($this->client->listStudents($school) as $dto) {
            /** @var KelvinStudentDto $dto */
            if ($dto->username !== '') {
                $map->put(mb_strtolower($dto->username), $dto);
            }
        }

        $this->log('info', 'Schüler-Map aufgebaut', ['count' => $map->count()]);

        return $map;
    }

    /**
     * Rückwärts-Index: Elternteil-Username (lowercase) → Schüler-Usernames,
     * abgeleitet aus students.legal_guardians.
     *
     * @param  Collection<string, KelvinStudentDto>  $studentMap
     * @return array<string, list<string>>
     */
    private function buildGuardianIndex(Collection $studentMap): array
    {
        $index = [];

        foreach ($studentMap as $student) {
            foreach ($student->legalGuardians as $guardianRef) {
                $guardian = $this->extractWardUsername((string) $guardianRef);
                if ($guardian !== null) {
                    $index[mb_strtolower($guardian)][] = $student->username;
                }
            }
        }

        return $index;
    }

    /**
     * Vereinigung aus legal_wards des Elternteils und dem Rückwärts-Index.
     *
     * @param  array<string, list<string>>  $wardsByGuardian
     * @return list<string>
     */
    private function wardUsernamesFor(KelvinUserDto $parentDto, array $wardsByGuardian): array
    {
        $wards = [];

        foreach ($parentDto->legalWards as $wardRef) {
            $username = $this->extractWardUsername((string) $wardRef);
            if ($username !== null) {
                $wards[mb_strtolower($username)] = $username;
            }
        }

        foreach ($wardsByGuardian[mb_strtolower($parentDto->username)] ?? [] as $username) {
            $wards[mb_strtolower($username)] ??= $username;
        }

        return array_values($wards);
    }

    /**
     * Konstruiert eine deterministische Klassen-Kennung aus Schule und Klassenname.
     * Wird als ucs_class_url in der groups-Tabelle gespeichert.
     */
    private function buildClassUrl(string $className, string $school): string
    {
        $base = rtrim($this->settings->kelvin_base_url ?? '', '/');

        return "{$base}/classes/".rawurlencode($school).':'.rawurlencode($className);
    }

    /**
     * Stellt sicher, dass der User die Rolle 'Eltern' hat.
     *
     * Idempotent; existiert die Rolle nicht, wird nur eine Warning geloggt.
     *
     * @see docs/ucs-kelvin-integration-konzept.md §5.2
     */
    private function ensureElternRole(User $user): void
    {
        if ($user->hasRole('Eltern')) {
            return;
        }

        $role = Role::where('name', 'Eltern')->where('guard_name', 'web')->first();

        if ($role === null) {
            $this->log('warning', 'Eltern-Rolle nicht in der Datenbank gefunden – Rolle nicht zugewiesen', [
                'user_id' => $user->id,
            ]);

            return;
        }

        $user->assignRole($role);
        $this->log('info', 'Eltern-Rolle zugewiesen', ['user_id' => $user->id]);
    }

    /** Wirft, wenn die UCS-Integration deaktiviert oder Schule nicht konfiguriert. */
    private function guardEnabled(): void
    {
        if (! $this->settings->enabled) {
            throw new \RuntimeException('UCS-Integration ist deaktiviert (UcsSetting::enabled=false).');
        }
        if (empty($this->settings->school)) {
            throw new \RuntimeException('UCS-Integration: Schule nicht konfiguriert (UcsSetting::school ist leer).');
        }
    }

    /** Schreibt Telemetrie-Felder in UcsSetting. */
    private function writeTelemetry(string $status, ?string $message = null): void
    {
        $this->settings->last_sync_status  = $status;
        $this->settings->last_sync_message = $message;
        $this->settings->save();
    }

    /** Schreibt den Erfolgs-Abschluss der Telemetrie inkl. Counts. */
    private function writeSuccessTelemetry(array $counts): void
    {
        $this->settings->last_sync_status   = 'success';
        $this->settings->last_sync_message  = sprintf(
            'Eltern: +%d /~%d /-%d | Kinder: +%d /~%d | Übersprungen: %d | Fehler: %d',
            $counts['parents_created'],
            $counts['parents_updated'],
            $counts['parents_deactivated'],
            $counts['children_created'],
            $counts['children_updated'],
            $counts['parents_skipped'],
            $counts['failed_parents'],
        );
        $this->settings->last_sync_parents  = $counts['parents_processed'];
        $this->settings->last_sync_students = $counts['children_created'] + $counts['children_updated'];
        $this->settings->last_sync_at       = now()->toIso8601String();
        $this->settings->save();
    }

    /** @return array<string, mixed> */
    private function emptyCounters(string $school, bool $dryRun): array
    {
        return [
            'school'                  => $school,
            'dry_run'                 => $dryRun,
            'parents_processed'       => 0,
            'parents_created'         => 0,
            'parents_updated'         => 0,
            'parents_deactivated'     => 0,
            'parents_skipped'         => 0,
            'children_created'        => 0,
            'children_updated'        => 0,
            'children_skipped_local'  => 0,
            'link_candidates_created' => 0,
            'groups_provisioned'      => 0,
            'failed_parents'          => 0,
            'duration_seconds'        => 0.0,
        ];
    }

    /** @param  array<string, mixed>  $context */
    private function log(string $level, string $message, array $context = []): void
    {
        Log::channel('ucs')->{$level}('[UcsSyncService] '.$message, $context);
    }
}
