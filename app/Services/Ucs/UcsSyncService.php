<?php

namespace App\Services\Ucs;

use App\Model\Child;
use App\Model\Group;
use App\Model\UcsLinkCandidate;
use App\Model\User;
use App\Services\Ucs\Dto\KelvinStudentDto;
use App\Services\Ucs\Dto\KelvinUserDto;
use App\Services\Ucs\Exceptions\KelvinAuthException;
use App\Services\Ucs\Exceptions\KelvinUnavailableException;
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
 * – Telemetrie: UcsSetting::last_sync_*, Cache-Lock 30 min
 *
 * @see docs/ucs-kelvin-integration-konzept.md §5, §5.2, §5.3, §7.3, §15.1
 */
class UcsSyncService
{
    /** Cache-Keys */
    private const LOCK_KEY       = 'ucs.sync.lock';
    private const JIT_MISS_PREFIX = 'ucs.jit.miss:';

    /** Maximale Länge der gespeicherten Fehlermeldung (Telemetrie). */
    private const MAX_MSG_LEN = 200;

    public function __construct(
        private readonly KelvinClient $client,
        private readonly UcsSetting   $settings,
    ) {}

    // =========================================================================
    // A. Bulk-Sync
    // =========================================================================

    /**
     * Führt den vollständigen Bulk-Sync für die konfigurierte Schule durch.
     *
     * Im Dry-Run-Modus werden ausschließlich Counts zurückgegeben,
     * keine Datenbankänderungen vorgenommen.
     *
     * @return array{
     *   school: string,
     *   dry_run: bool,
     *   parents_processed: int,
     *   parents_created: int,
     *   parents_updated: int,
     *   parents_deactivated: int,
     *   children_created: int,
     *   children_updated: int,
     *   children_skipped_local: int,
     *   link_candidates_created: int,
     *   groups_provisioned: int,
     *   failed_parents: int,
     *   duration_seconds: float,
     * }
     *
     * @throws \RuntimeException Wenn die Integration deaktiviert oder Schule nicht konfiguriert ist.
     */
    public function run(bool $dryRun = false): array
    {
        $this->guardEnabled();

        $school = $this->settings->school;
        $startedAt = microtime(true);

        // Telemetrie: Status 'running' schreiben + Cache-Lock setzen (30 min)
        if (! $dryRun) {
            $this->writeTelemetry(status: 'running');
            Cache::put(self::LOCK_KEY, true, 30 * 60);
        }

        $counts = $this->emptyCounters($school, $dryRun);

        try {
            // ── Schritt 1: Alle Schüler vorab in eine Collection laden (O(1)-Lookup) ──
            $studentMap = $this->buildStudentMap($school);

            // ── Schritt 2 + 3: Eltern paginiert iterieren + pro Elternteil syncen ──
            // Tracking per Username (primär) und UUID (wenn vorhanden).
            // record_uid kann bei manchen Kelvin-Installationen null sein; der Username
            // ist immer vorhanden (aus URL extrahiert) und dient als stabiler Anker.
            $seenParentUuids     = [];
            $seenParentUsernames = [];
            $seenStudentUsernames = [];

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
                    DB::transaction(function () use (
                        $parentDto, $studentMap, $school, $dryRun, &$counts, &$seenStudentUsernames
                    ) {
                        $user = $this->upsertUser($parentDto, $dryRun, $counts);

                        // Im Dry-Run ist $user null (kein DB-Write) – dennoch processWards für Counts aufrufen
                        if ($user === null && ! $dryRun) {
                            return;
                        }

                        [$desiredGroupIds, $childIdMap] = $this->processWards(
                            $user, $parentDto, $studentMap, $school, $dryRun, $counts, $seenStudentUsernames
                        );

                        // ── Schritt 4: Pivot-Diff am Elternteil (Herzstück §5.2) ──
                        if (! $dryRun && $user !== null) {
                            $this->syncGroupPivots($user, $desiredGroupIds, $childIdMap);
                        }
                    });
                } catch (Throwable $e) {
                    $counts['failed_parents']++;
                    $this->log('error', 'Fehler bei Elternteil-Sync', [
                        'username' => $parentDto->username,
                        'error'    => $e->getMessage(),
                    ]);
                    // Kein Re-Throw: nächster Elternteil wird trotzdem verarbeitet
                }
            }

            // ── Schritt 5: Orphan-Cleanup ──
            if (! $dryRun) {
                $this->orphanCleanup($school, $seenParentUuids, $seenParentUsernames, $seenStudentUsernames, $counts);
            }

            $counts['duration_seconds'] = round(microtime(true) - $startedAt, 2);

            // Telemetrie: Erfolg
            if (! $dryRun) {
                $this->writeSuccessTelemetry($counts);
            }

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
                Cache::forget(self::LOCK_KEY);
            }
        }
    }

    // =========================================================================
    // B. Single-Parent JIT-Sync (für OIDC-Callback §6.4)
    // =========================================================================

    /**
     * Synchronisiert einen einzelnen Elternteil und seine Kinder (JIT-Login).
     *
     * – Kein Detach von bestehenden Auto-Pivots (§6.4 Pkt. 4)
     * – Hard-Timeout: UcsSetting::on_login_timeout Sekunden
     * – Bei Timeout/Fehler: Negativ-Cache setzen, null zurückgeben
     *
     * @return User|null  Frisch geladener User für Auth::login, oder null bei Fehler.
     */
    public function syncSingleParent(string $username): ?User
    {
        $this->guardEnabled();

        $this->log('info', "syncSingleParent: [{$username}]");

        try {
            $parentData = $this->client->findUser($username, $this->settings->on_login_timeout);

            if ($parentData === null) {
                Cache::put(
                    self::JIT_MISS_PREFIX.$username,
                    true,
                    now()->addMinutes(15)
                );
                $this->log('info', "syncSingleParent: 404 – Negativ-Cache gesetzt [{$username}]");

                return null;
            }

            $parentDto = KelvinUserDto::fromArray($parentData);
            $school    = $this->settings->school ?? '';
            $dummyCounts = $this->emptyCounters($school, false);

            $user = DB::transaction(function () use ($parentDto, $school, &$dummyCounts) {
                $user = $this->upsertUser($parentDto, false, $dummyCounts);

                if ($user === null) {
                    return null;
                }

                // Kinder einzeln per findUser() laden (JIT: max. 1–4 Kinder)
                $studentMap = collect();
                foreach ($parentDto->legalWards as $wardUrl) {
                    $wardUsername = $this->extractWardUsername($wardUrl);
                    if ($wardUsername === null) {
                        continue;
                    }

                    $wardData = $this->client->findUser($wardUsername, $this->settings->on_login_timeout);
                    if ($wardData !== null) {
                        $dto = KelvinStudentDto::fromArray($wardData);
                        $studentMap->put($dto->username, $dto);
                    }
                }

                if ($studentMap->isNotEmpty()) {
                    $seenStudents = [];
                    // JIT: kein Detach → $desiredGroupIds ignorieren
                    [$desiredGroupIds, $childIdMap] = $this->processWards(
                        $user, $parentDto, $studentMap, $school, false, $dummyCounts, $seenStudents
                    );
                    // Nur neue Pivots hinzufügen, KEINE bestehenden löschen (§6.4)
                    foreach ($desiredGroupIds as $gid) {
                        $user->groups()->syncWithoutDetaching([
                            $gid => [
                                'is_auto_provisioned'      => true,
                                'provisioned_via_child_id' => $childIdMap[$gid] ?? null,
                                'synced_at'                => now(),
                            ],
                        ]);
                    }
                }

                return $user;
            });

            $this->log('info', "syncSingleParent: erfolgreich [{$username}]");

            return $user instanceof User ? $user->fresh() : null;

        } catch (Throwable $e) {
            Cache::put(
                self::JIT_MISS_PREFIX.$username,
                true,
                now()->addMinutes(5)
            );
            $this->log('warning', "syncSingleParent: Fehler – Negativ-Cache gesetzt [{$username}]", [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    // =========================================================================
    // C. Hilfsmethoden
    // =========================================================================

    /**
     * Extrahiert den UCS-Username aus einer legal_wards-URL.
     *
     * Erwartet: https://ucs.example.de/ucsschool/kelvin/v1/users/<username>
     * Validierung: nur [a-z0-9._-], max 120 Zeichen.
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
     * Gibt die Klassen-Gruppe für den gegebenen Klassenname und URL zurück (oder legt sie an).
     *
     * Restore-Logic: SoftDeleted Gruppen werden „wiederbelebt".
     */
    protected function resolveClassGroup(string $className, string $classUrl): Group
    {
        // 1. Match über ucs_class_url (eindeutig)
        $group = Group::withTrashed()
            ->where('ucs_class_url', $classUrl)
            ->first();

        if ($group !== null) {
            if ($group->trashed()) {
                $group->restore();
                $this->log('info', "Klassen-Gruppe wiederhergestellt [{$className}]");
            }
            $group->fill([
                'name'          => $className,
                'ucs_source'    => 'kelvin',
                'ucs_synced_at' => now(),
            ])->save();

            return $group;
        }

        // 2. Match über Name + bereich='Klasse' (Fallback für Gruppen vor Kelvin-Anbindung)
        $group = Group::withTrashed()
            ->where('name', $className)
            ->where('bereich', 'Klasse')
            ->first();

        if ($group !== null) {
            if ($group->trashed()) {
                $group->restore();
            }
            $group->fill([
                'ucs_class_url' => $classUrl,
                'ucs_source'    => 'kelvin',
                'ucs_synced_at' => now(),
            ])->save();

            return $group;
        }

        // 3. Neue Gruppe anlegen
        return Group::create([
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
        $classNames = $dto->schoolClasses[$school] ?? [];

        if (empty($classNames)) {
            return null;
        }

        // Klassen-IDs ermitteln
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
     * @param  array<string, int>  $counts  Referenz-Array für Counter
     */
    private function upsertUser(KelvinUserDto $dto, bool $dryRun, array &$counts): ?User
    {
        // Match-Strategie (in Prioritätsreihenfolge):
        //  1. ucs_uuid  – zuverlässigste Kennung; nur wenn record_uid vorhanden
        //  2. ucs_username – stabiler Anmeldename aus Kelvin
        //  3. email     – Fallback für Accounts, bei denen Kelvin keinen Benutzernamen liefert
        //
        // Hintergrund: Einige Kelvin-Installationen liefern record_uid = null und/oder
        // username = "" – in diesem Fall extrahiert KelvinUserDto den Namen aus dem
        // URL-Pfad. Die Email dient als letzter Anker, falls sie lokal bereits bekannt ist.
        $user = null;

        if ($dto->recordUid !== null) {
            $user = User::where('ucs_uuid', $dto->recordUid)->first();
        }

        if ($user === null && $dto->username !== '') {
            $user = User::where('ucs_username', $dto->username)->first();
        }

        if ($user === null && $dto->email !== null) {
            $user = User::where('email', $dto->email)->first();
        }

        if ($dryRun) {
            if ($user === null) {
                $counts['parents_created']++;
            } else {
                $counts['parents_updated']++;
            }

            return null; // Im Dry-Run kein echtes Objekt zurückgeben
        }

        if ($user === null) {
            // Neuanlage
            $user = User::create([
                'name'          => trim($dto->firstname.' '.$dto->lastname),
                'email'         => $dto->email,
                'ucs_uuid'      => $dto->recordUid,   // null, wenn Kelvin keinen record_uid liefert
                'ucs_username'  => $dto->username,
                'ucs_school'    => $dto->school,
                'ucs_source'    => 'kelvin',
                'ucs_synced_at' => now(),
                'password'      => bcrypt(Str::random(32)), // nicht einlogbar ohne OIDC
                'is_active'     => true,
            ]);
            $counts['parents_created']++;
            $this->log('info', "User angelegt [{$dto->username}]");
        } else {
            // Nur UCS-Felder + Name sanft aktualisieren
            // password, email (wenn local), Rollen, Einstellungen NICHT überschreiben
            $updates = [
                'ucs_uuid'      => $dto->recordUid ?? $user->ucs_uuid, // vorhandene UUID nicht überschreiben
                'ucs_username'  => $dto->username,
                'ucs_school'    => $dto->school,
                'ucs_synced_at' => now(),
                'is_active'     => true,
            ];

            // Name nur überschreiben, wenn User aus Kelvin stammt
            if ($user->ucs_source === 'kelvin') {
                $updates['name'] = trim($dto->firstname.' '.$dto->lastname);
            }

            // E-Mail nur setzen, wenn bisher leer und dto eine hat
            if (empty($user->email) && ! empty($dto->email)) {
                $updates['email'] = $dto->email;
            }

            $user->update($updates);
            $counts['parents_updated']++;
        }

        return $user;
    }

    /**
     * Verarbeitet alle legal_wards eines Elternteils.
     *
     * @param  User|null                             $user        Null im Dry-Run (kein DB-Write)
     * @param  Collection<string, KelvinStudentDto>  $studentMap  Vorab geladene Schüler
     * @param  array<string, int>                    $counts
     * @param  array<string>                         $seenStudentUsernames
     * @return array{0: list<int>, 1: array<int, int>}  [$desiredGroupIds, $childIdMap]
     */
    private function processWards(
        ?User      $user,
        KelvinUserDto $parentDto,
        Collection $studentMap,
        string     $school,
        bool       $dryRun,
        array      &$counts,
        array      &$seenStudentUsernames,
    ): array {
        $desiredGroupIds = [];
        $childIdMap      = [];
        $childLinked     = false; // Wird true, sobald mind. ein Kind verknüpft wurde

        foreach ($parentDto->legalWards as $wardUrl) {
            $wardUsername = $this->extractWardUsername($wardUrl);
            if ($wardUsername === null) {
                continue;
            }

            /** @var KelvinStudentDto|null $studentDto */
            $studentDto = $studentMap->get($wardUsername);

            if ($studentDto === null) {
                $this->log('warning', "legal_ward nicht in Schüler-Map gefunden", [
                    'ward'   => $wardUsername,
                    'parent' => $parentDto->username,
                ]);
                continue;
            }

            $seenStudentUsernames[] = $studentDto->username;

            $child = $this->upsertChild($studentDto, $school, $dryRun, $counts);

            if ($child === null) {
                continue; // Dry-Run oder Link-Candidate-Pfad
            }

            // Pivot child_user (idempotent, nur wenn echter User vorhanden)
            if (! $dryRun && $user !== null) {
                $user->children_rel()->syncWithoutDetaching([
                    $child->id => [
                        'is_auto_provisioned' => true,
                        'relation'            => 'legal_guardian',
                        'synced_at'           => now(),
                    ],
                ]);
                $childLinked = true;
            }

            // Klassen-Gruppen
            $classNames = $studentDto->schoolClasses[$school] ?? [];
            if (empty($classNames)) {
                $this->log('warning', "Kind ohne Klasse – kein class_id gesetzt", [
                    'student' => $studentDto->username,
                ]);
                continue;
            }

            // Kombiklasse: >1 Klassen → erste alphabetisch für class_id
            if (count($classNames) > 1) {
                sort($classNames);
                $this->log('info', 'Kombiklasse erkannt – erste Klasse für class_id', [
                    'student' => $studentDto->username,
                    'classes' => $classNames,
                ]);
            }

            $primaryClass = $classNames[0];

            foreach ($classNames as $className) {
                $classUrl = $this->buildClassUrl($className, $school);
                $group    = $this->resolveClassGroup($className, $classUrl);

                $counts['groups_provisioned']++;

                // class_id nur für Kelvin-Kinder setzen
                if (! $dryRun && $child->ucs_source === 'kelvin' && $className === $primaryClass) {
                    $child->update(['class_id' => $group->id]);
                }

                $desiredGroupIds[]              = $group->id;
                $childIdMap[$group->id]         = $child->id;
            }
        }

        // Eltern-Rolle zuweisen, sobald mind. ein Kind verknüpft wurde
        if ($childLinked && $user !== null) {
            $this->ensureElternRole($user);
        }

        return [$desiredGroupIds, $childIdMap];
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
        // Match per ucs_uuid (primär), sonst Composite (ucs_school, ucs_username)
        $child = Child::withoutGlobalScopes()
            ->where('ucs_uuid', $dto->recordUid)
            ->first()
            ?? Child::withoutGlobalScopes()
                ->where('ucs_school', $school)
                ->where('ucs_username', $dto->username)
                ->first();

        if ($child !== null) {
            // Bestehendes Kind
            if ($child->ucs_source === 'local') {
                // Nur ucs_uuid / ucs_username backfillen – NIEMALS Daten überschreiben
                if (! $dryRun && (empty($child->ucs_username) || empty($child->ucs_uuid))) {
                    $child->update([
                        'ucs_uuid'      => $child->ucs_uuid     ?: $dto->recordUid,
                        'ucs_username'  => $child->ucs_username ?: $dto->username,
                        'ucs_school'    => $child->ucs_school   ?: $school,
                    ]);
                    $this->log('info', 'Lokales Kind mit UCS-UUID/Username verlinkt', [
                        'child_id' => $child->id,
                        'username' => $dto->username,
                    ]);
                }
                $counts['children_skipped_local']++;

                return $dryRun ? null : $child;
            }

            // ucs_source='kelvin': Namen + Sync-Timestamp updaten
            if (! $dryRun) {
                $child->update([
                    'first_name'    => $dto->firstname,
                    'last_name'     => $dto->lastname,
                    'ucs_uuid'      => $dto->recordUid,
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
            if (! $dryRun) {
                // Vorhandenen Kandidaten für dieses (child_id, ucs_username)-Paar suchen
                $existing = UcsLinkCandidate::where('child_id', $linkCandidate->id)
                    ->where('ucs_username', $dto->username)
                    ->first();

                if ($existing !== null) {
                    // Bereits vorhanden: prüfen ob verworfen oder noch offen
                    $isRejected = ($existing->payload['status'] ?? '') === 'rejected';
                    $this->log(
                        'info',
                        $isRejected
                            ? 'Link-Kandidat wurde früher verworfen – Sync überspringt dieses Paar'
                            : 'Link-Kandidat bereits offen – kein Duplikat angelegt',
                        [
                            'local_child_id' => $linkCandidate->id,
                            'ucs_username'   => $dto->username,
                            'candidate_id'   => $existing->id,
                            'rejected'       => $isRejected,
                        ]
                    );
                } else {
                    // Neuen Kandidaten anlegen
                    UcsLinkCandidate::create([
                        'child_id'    => $linkCandidate->id,
                        'ucs_username'=> $dto->username,
                        'ucs_uuid'    => $dto->recordUid,
                        'reason'      => 'name_match',
                        'payload'     => $dto->raw ?? [],
                        'detected_at' => now(),
                    ]);
                    $counts['link_candidates_created']++;
                    $this->log('info', 'Link-Kandidat erkannt – kein Duplikat angelegt', [
                        'local_child_id' => $linkCandidate->id,
                        'ucs_username'   => $dto->username,
                    ]);
                }
            } else {
                // Dry-Run: nur zählen, wenn noch kein Kandidat vorhanden ist
                $alreadyExists = UcsLinkCandidate::where('child_id', $linkCandidate->id)
                    ->where('ucs_username', $dto->username)
                    ->exists();
                if (! $alreadyExists) {
                    $counts['link_candidates_created']++;
                }
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
     * Synchronisiert die Auto-Gruppen-Pivots eines Users (Herzstück §5.2).
     *
     * Entfernt Auto-Pivots, die nicht mehr im Soll-Set sind.
     * Lässt manuelle Pivots (is_auto_provisioned=false) immer unberührt.
     *
     * ❗ Wichtig: detach() ignoriert wherePivot-Filter am Query-Builder.
     *    IDs werden daher zuerst via pluck() ermittelt, dann explizit übergeben.
     *
     * @param  list<int>        $desiredGroupIds
     * @param  array<int, int>  $childIdMap      [group_id => child_id]
     */
    private function syncGroupPivots(User $user, array $desiredGroupIds, array $childIdMap): void
    {
        $idsToDetach = $user->groups()
            ->wherePivot('is_auto_provisioned', true)
            ->whereNotIn('groups.id', $desiredGroupIds)
            ->pluck('groups.id')
            ->all();

        Log::debug("syncGroupPivots: User {$user->name} – Detach-IDs ermittelt", [
            'desired_group_ids' => $desiredGroupIds,
            'current_auto_group_ids' => $user->groups()->wherePivot('is_auto_provisioned', true)->pluck('groups.id')->all(),
            'ids_to_detach' => $idsToDetach,
        ]);

        if (! empty($idsToDetach)) {
            $user->groups()->detach($idsToDetach);
        }

        foreach ($desiredGroupIds as $gid) {
            $user->groups()->syncWithoutDetaching([
                $gid => [
                    'is_auto_provisioned'      => true,
                    'provisioned_via_child_id' => $childIdMap[$gid] ?? null,
                    'synced_at'                => now(),
                ],
            ]);
        }
    }

    // =========================================================================
    // Private – Orphan-Cleanup
    // =========================================================================

    /**
     * Bereinigt Datensätze, die nicht mehr in der Kelvin-Antwort auftauchen.
     *
     * ⚠️  Safety-Guard: Wenn alle Listen leer sind (API lieferte nichts /
     *     Kelvin hatte einen stillen Fehler), wird der Cleanup ABGEBROCHEN.
     *     Ziel: kein ungewollter Massen-Soft-Delete bei leerem API-Response.
     *
     * Orphan-Erkennung für Eltern:
     *   Ein Elternteil gilt als Waise wenn:
     *   – Er hat eine ucs_uuid UND diese ist NICHT in $seenParentUuids, ODER
     *   – Er hat keine ucs_uuid (record_uid war null in Kelvin) UND
     *     sein ucs_username ist NICHT in $seenParentUsernames.
     *
     * @param  string[]  $seenParentUuids       Alle record_uid der gesehenen Eltern (ohne null)
     * @param  string[]  $seenParentUsernames   Alle username der gesehenen Eltern
     * @param  string[]  $seenStudentUsernames  Alle username der gesehenen Schüler
     * @param  array<string, int>  $counts
     */
    private function orphanCleanup(
        string $school,
        array  $seenParentUuids,
        array  $seenParentUsernames,
        array  $seenStudentUsernames,
        array  &$counts,
    ): void {
        // Safety-Guard: Alle Listen leer → Kelvin lieferte kein Ergebnis.
        // Kein Cleanup durchführen, um ungewollte Massen-Deaktivierung zu verhindern.
        if (empty($seenParentUuids) && empty($seenParentUsernames) && empty($seenStudentUsernames)) {
            $this->log('warning', 'orphanCleanup: Alle Listen leer – Cleanup übersprungen (Safety-Guard).');

            return;
        }

        // Eltern: deaktivieren (kein Hard-Delete!)
        //
        // Zwei Fälle:
        // a) Elternteil hat eine ucs_uuid → Orphan wenn UUID nicht mehr in Kelvin
        // b) Elternteil hat keine ucs_uuid (record_uid war null) → Orphan wenn Username nicht mehr in Kelvin
        $orphanParents = User::where('ucs_source', 'kelvin')
            ->where('is_active', true)
            ->where(function ($q) use ($seenParentUuids, $seenParentUsernames) {
                $q->where(function ($q2) use ($seenParentUuids) {
                    // Fall a: Hat UUID, aber nicht in der gesehenen Liste
                    $q2->whereNotNull('ucs_uuid')
                        ->when(
                            ! empty($seenParentUuids),
                            fn ($q3) => $q3->whereNotIn('ucs_uuid', $seenParentUuids),
                            fn ($q3) => $q3, // Wenn keine UUIDs gesehen → keine UUID-Waise
                        );
                })->orWhere(function ($q2) use ($seenParentUsernames) {
                    // Fall b: Hat keine UUID, aber Username nicht in der gesehenen Liste
                    $q2->whereNull('ucs_uuid')
                        ->when(
                            ! empty($seenParentUsernames),
                            fn ($q3) => $q3->whereNotIn('ucs_username', $seenParentUsernames),
                            fn ($q3) => $q3->whereRaw('1=0'), // Wenn keine Usernames gesehen → sicher abstehen
                        );
                });
            })
            ->get();

        foreach ($orphanParents as $orphan) {
            $orphan->update([
                'is_active'      => false,
                'deactivated_at' => now(),
            ]);
            $counts['parents_deactivated']++;
            $this->log('info', "Elternteil deaktiviert (nicht mehr in Kelvin)", [
                'user_id'  => $orphan->id,
                'username' => $orphan->ucs_username,
            ]);
        }

        // Kinder: SoftDelete
        Child::withoutGlobalScopes()
            ->where('ucs_source', 'kelvin')
            ->where('ucs_school', $school)
            ->whereNotIn('ucs_username', $seenStudentUsernames)
            ->each(function (Child $child) {
                $child->delete();
                $this->log('info', "Kind SoftDeleted (nicht mehr in Kelvin)", [
                    'child_id' => $child->id,
                    'username' => $child->ucs_username,
                ]);
            });

        // Verwaiste Link-Kandidaten bereinigen:
        // Wenn ein Kind soft-deleted wurde, greift der FK cascadeOnDelete NICHT
        // (nur hard-delete löst den DB-Cascade aus). Daher werden Kandidaten,
        // deren Kind nicht mehr existiert oder soft-deleted ist, hier manuell
        // entfernt. scopeOpen() filtert sie zusätzlich aus der UI heraus.
        $orphanCandidateCount = UcsLinkCandidate::whereDoesntHave('child')->delete();
        if ($orphanCandidateCount > 0) {
            $this->log('info', "Verwaiste Link-Kandidaten gelöscht (Kind soft-deleted)", [
                'count' => $orphanCandidateCount,
            ]);
        }

        // Klassen-Gruppen: Auto-Pivots entfernen + SoftDelete
        // Gruppen, die im aktuellen Sync-Lauf aktualisiert wurden, haben
        // ucs_synced_at ≈ now(). Als Puffer werden 120 Minuten genutzt,
        // damit auch langsame Syncs (>10 min) keine gültigen Gruppen löschen.
        Group::withoutGlobalScopes()
            ->withTrashed()
            ->where('ucs_source', 'kelvin')
            ->where('ucs_synced_at', '<', now()->subMinutes(120)) // konservativer Puffer
            ->each(function (Group $group) {
                if (! $group->trashed()) {
                    // Auto-Pivots weg (manuelle bleiben erhalten)
                    $autoPivotIds = DB::table('group_user')
                        ->where('group_id', $group->id)
                        ->where('is_auto_provisioned', true)
                        ->pluck('user_id')
                        ->all();

                    if (! empty($autoPivotIds)) {
                        $group->users()->detach($autoPivotIds);
                    }

                    $group->delete(); // SoftDelete
                    $this->log('info', "Klassen-Gruppe SoftDeleted (Orphan)", [
                        'group_id' => $group->id,
                        'name'     => $group->name,
                    ]);
                }
            });
    }

    // =========================================================================
    // Private – Hilfsmethoden
    // =========================================================================

    /**
     * Baut alle Schüler der Schule in eine by-username indiziierte Collection.
     *
     * @return Collection<string, KelvinStudentDto>
     */
    private function buildStudentMap(string $school): Collection
    {
        $map = collect();

        foreach ($this->client->listStudents($school) as $dto) {
            /** @var KelvinStudentDto $dto */
            $map->put($dto->username, $dto);
        }

        $this->log('info', "Schüler-Map aufgebaut", ['count' => $map->count()]);

        return $map;
    }

    /**
     * Konstruiert eine Class-URL aus Klassenname und Schule.
     * Diese wird als ucs_class_url in der groups-Tabelle gespeichert.
     *
     * Falls die Kelvin-API eine vollständige URL liefert, sollte der Caller
     * diese direkt übergeben. Als Fallback wird hier eine deterministische
     * URL aus der base_url konstruiert.
     */
    private function buildClassUrl(string $className, string $school): string
    {
        $base = rtrim($this->settings->kelvin_base_url ?? '', '/');

        return "{$base}/classes/".rawurlencode($school).':'.rawurlencode($className);
    }

    /**
     * Stellt sicher, dass der User die Rolle 'Eltern' hat.
     *
     * Idempotent: hat der User die Rolle bereits, passiert nichts.
     * Fehlerresistent: existiert die Rolle nicht in der DB, wird nur
     * eine Warning geloggt (kein Exception-Crash des Sync-Laufs).
     *
     * Hintergrund: Jeder per UCS-Sync provisionierte Nutzer, der mindestens
     * ein Kind verknüpft bekommt, benötigt die Rolle 'Eltern' um die App
     * vollständig nutzen zu können (Äquivalent zu UsersImport::assignRole('Eltern')).
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
        $this->log('info', "Eltern-Rolle zugewiesen", ['user_id' => $user->id]);
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
            'Eltern: +%d /~%d /-%d | Kinder: +%d /~%d | Fehler: %d',
            $counts['parents_created'],
            $counts['parents_updated'],
            $counts['parents_deactivated'],
            $counts['children_created'],
            $counts['children_updated'],
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

