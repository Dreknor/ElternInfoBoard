<?php

namespace App\Services\Students;

use App\Model\Child;
use App\Model\User;
use App\Settings\SchuelerSetting;
use Illuminate\Support\Collection;

/**
 * Schülerkonten-Import (docs/konzept-schuelerkonten.md §9.1, Paket S-07b).
 *
 * Eine Zeile je Kind: Schüler-ID (Pflicht, = children.external_id), optional Benutzername und
 * Konto (ja = anlegen/aktivieren, nein = sperren). Abgleich nur über die Schüler-ID, nie über
 * Namen. Die Vorschau (dryRun) ändert nichts. Nicht zu verwechseln mit dem „Schüler-Import mit
 * Schüler-ID“ (SchuelerImportService), der Kinder und Elternkonten anlegt.
 */
class StudentImportService
{
    public const ACTION_CREATE = 'anlegen';

    public const ACTION_LINK = 'vorhanden';

    public const ACTION_ACTIVATE = 'entsperren';

    public const ACTION_DEACTIVATE = 'sperren';

    public const ACTION_RENAME = 'umbenennen';

    public const ACTION_NONE = 'unverändert';

    public const ACTION_SKIP = 'Klärungsfall';

    public function __construct(
        private readonly StudentAccountService $accounts,
        private readonly SchuelerSetting $settings,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $rows  Zeilen mit Überschriften (WithHeadingRow)
     * @return array{rows: list<array{zeile: int, schueler_id: string, kind: ?string, klasse: ?string, benutzername: ?string, aktion: string, hinweis: ?string}>, created: Collection<int, User>, summary: array<string, int>}
     */
    public function run(array $rows, bool $dryRun): array
    {
        $result = [];
        $created = collect();
        $seenIds = [];
        $reservedNames = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2; // Überschrift = Zeile 1
            $row = array_change_key_case(array_map(fn ($v) => is_string($v) ? trim($v) : $v, (array) $row));

            $externalId = (string) ($this->value($row, ['schueler_id', 'schuler_id', 'schuelerid', 'schulerid', 'schueler-id', 'external_id', 'id']) ?? '');
            $username = $this->value($row, ['benutzername', 'username', 'login']);
            $username = $username !== null && $username !== '' ? $this->accounts->normalizeUsername((string) $username) : null;
            $kontoRaw = mb_strtolower((string) ($this->value($row, ['konto', 'account']) ?? 'ja'));
            $wanted = ! in_array($kontoRaw, ['nein', 'n', '0', 'no', 'false', 'aus'], true);

            $entry = ['zeile' => $line, 'schueler_id' => $externalId, 'kind' => null, 'klasse' => null, 'benutzername' => $username, 'aktion' => self::ACTION_SKIP, 'hinweis' => null];

            if ($externalId === '') {
                if (array_filter($row, fn ($v) => $v !== null && $v !== '') === []) {
                    continue; // leere Zeile
                }
                $result[] = array_merge($entry, ['hinweis' => 'Schüler-ID fehlt']);

                continue;
            }

            if (isset($seenIds[$externalId])) {
                $result[] = array_merge($entry, ['hinweis' => 'Schüler-ID doppelt in der Datei (Zeile '.$seenIds[$externalId].')']);

                continue;
            }
            $seenIds[$externalId] = $line;

            $child = Child::query()->with(['studentAccount', 'class:id,name', 'group:id,name'])->where('external_id', $externalId)->first();
            if ($child === null) {
                $result[] = array_merge($entry, ['hinweis' => 'Unbekannte Schüler-ID']);

                continue;
            }

            $entry['kind'] = trim($child->first_name.' '.$child->last_name);
            $entry['klasse'] = $child->class?->name ?? $child->group?->name;

            if ($child->status === Child::STATUS_LEFT) {
                $result[] = array_merge($entry, ['hinweis' => 'Kind ist ausgeschieden']);

                continue;
            }

            if ($child->ucs_source === 'kelvin' && $this->settings->ucs_provisionierung) {
                $result[] = array_merge($entry, ['aktion' => self::ACTION_NONE, 'hinweis' => 'Konto kommt aus dem UCS-Abgleich']);

                continue;
            }

            $account = $child->studentAccount;

            if (! $wanted) {
                if ($account === null || $account->is_active === false) {
                    $result[] = array_merge($entry, ['aktion' => self::ACTION_NONE, 'benutzername' => $account?->username]);
                } else {
                    $dryRun || $this->accounts->deactivate($account, 'import');
                    $result[] = array_merge($entry, ['aktion' => self::ACTION_DEACTIVATE, 'benutzername' => $account->username]);
                }

                continue;
            }

            if ($username !== null && ! $this->accounts->usernameIsValid($username)) {
                $result[] = array_merge($entry, ['hinweis' => 'Benutzername ungültig (nur a–z, 0–9, Punkt, Bindestrich; kein @)']);

                continue;
            }

            if ($username !== null && (isset($reservedNames[$username]) || User::withStudents()->withTrashed()
                ->where('username', $username)
                ->when($account, fn ($q) => $q->whereKeyNot($account->id))
                ->exists())) {
                $result[] = array_merge($entry, ['hinweis' => "Benutzername „{$username}“ ist schon vergeben"]);

                continue;
            }

            if ($account === null) {
                $name = $username ?? $this->accounts->uniqueUsername($child->first_name, $child->last_name);
                // In der Vorschau eindeutig halten, auch wenn mehrere Kinder gleich heißen
                while (isset($reservedNames[$name])) {
                    $name = $this->accounts->uniqueUsername($child->first_name, $child->last_name).count($reservedNames);
                }
                $reservedNames[$name] = true;

                if (! $dryRun) {
                    [$account] = $this->accounts->createForChild($child, ['username' => $name, 'via' => 'import']);
                    $created->push($account);
                }
                $result[] = array_merge($entry, ['aktion' => self::ACTION_CREATE, 'benutzername' => $name,
                    'hinweis' => $this->settings->eltern_zustimmung ? 'aktiv nach Zustimmung der Eltern' : null]);

                continue;
            }

            $actions = [];
            if ($username !== null && $username !== $account->username) {
                $dryRun || $this->accounts->rename($account, $username);
                $actions[] = self::ACTION_RENAME;
                $reservedNames[$username] = true;
            }
            if ($account->is_active === false) {
                $blockedByConsent = $this->settings->eltern_zustimmung && $account->student_consent !== 'ja';
                if ($blockedByConsent) {
                    $entry['hinweis'] = 'bleibt gesperrt bis zur Zustimmung der Eltern';
                } else {
                    $dryRun || $this->accounts->activate($account);
                    $actions[] = self::ACTION_ACTIVATE;
                }
            }

            $result[] = array_merge($entry, [
                'aktion' => $actions === [] ? self::ACTION_LINK : implode(', ', $actions),
                'benutzername' => $username ?? $account->username,
            ]);
        }

        $summary = [];
        foreach ($result as $r) {
            $key = $r['aktion'] === self::ACTION_SKIP ? 'klaerung' : $r['aktion'];
            $summary[$key] = ($summary[$key] ?? 0) + 1;
        }

        if (! $dryRun) {
            StudentAccountLog::info('Schülerkonten-Import ausgeführt', null, ['angelegt' => $created->count(), 'zeilen' => count($result)]);
        }

        return ['rows' => $result, 'created' => $created, 'summary' => $summary];
    }

    private function value(array $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
                return is_float($row[$key]) && floor($row[$key]) === $row[$key] ? (string) (int) $row[$key] : $row[$key];
            }
        }

        return null;
    }
}
