<?php

namespace App\Services\Updater;

use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Online-Updater: prüft das Git-Repository auf neue Commits und führt die
 * Schritte aus deploy.sh aus (Sicherung, Wartungsmodus, git pull, composer,
 * npm, Migrationen, Caches, Queue-Neustart).
 *
 * Status, Protokolle und Sicherungen liegen als Dateien unter
 * storage/app/updater, damit sie auch während Migrationen lesbar bleiben.
 * Jeder Schritt läuft als eigener Prozess – der laufende PHP-Prozess lädt
 * nach "composer install" also keine halb aktualisierten Klassen nach.
 */
class UpdateService
{
    public const STATUS_IDLE = 'idle';

    public const STATUS_REQUESTED = 'requested';

    public const STATUS_RUNNING = 'running';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    /** Schritte in Ausführungsreihenfolge */
    public const STEPS = [
        'preflight' => 'Voraussetzungen prüfen',
        'backup' => 'Datenbank sichern',
        'down' => 'Wartungsmodus aktivieren',
        'pull' => 'Quellcode aktualisieren (git pull)',
        'composer' => 'PHP-Abhängigkeiten installieren',
        'settings' => 'Settings-Klassen neu erkennen',
        'assets' => 'Frontend-Assets bauen',
        'migrate' => 'Datenbank migrieren',
        'post' => 'Zusatzbefehle ausführen',
        'cache' => 'Caches leeren',
        'queue' => 'Queue-Worker neu starten',
        'up' => 'Wartungsmodus beenden',
    ];

    /** Dateien, deren Änderung einen Asset-Build erfordert */
    private const ASSET_PATHS = [
        'resources/', 'package.json', 'package-lock.json',
        'vite.config.js', 'tailwind.config.js', 'postcss.config.js',
    ];

    /** @var resource|null */
    private $lockHandle = null;

    private ?string $logFile = null;

    /** @var callable|null */
    private $output = null;

    private array $state = [];

    /** Laufzeitdaten des aktuellen Updates (Branch, Commits, geänderte Dateien) */
    private array $ctx = [];

    // ── Ablage ───────────────────────────────────────────────────────────

    public function path(string $file = ''): string
    {
        $dir = rtrim(config('updater.storage_path'), '/\\');

        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return $file === '' ? $dir : $dir.DIRECTORY_SEPARATOR.$file;
    }

    private function readJson(string $file): ?array
    {
        $path = $this->path($file);
        if (! is_file($path)) {
            return null;
        }

        $data = json_decode((string) @file_get_contents($path), true);

        return is_array($data) ? $data : null;
    }

    private function writeJson(string $file, array $data): void
    {
        $path = $this->path($file);
        $tmp = $path.'.'.getmypid().'.tmp';
        file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        rename($tmp, $path);
    }

    // ── Status ───────────────────────────────────────────────────────────

    /**
     * Aktueller Status inkl. Erkennung abgestürzter Läufe.
     */
    public function state(): array
    {
        $state = $this->readJson('state.json') ?? ['status' => self::STATUS_IDLE];

        if (($state['status'] ?? null) === self::STATUS_RUNNING && ! $this->isRunning()) {
            $state['status'] = self::STATUS_FAILED;
            $state['error'] = 'Der Update-Prozess wurde unerwartet beendet.';
            $state['finished_at'] = date(DATE_ATOM);
            foreach ($state['steps'] ?? [] as $key => $step) {
                if ($step['status'] === 'running') {
                    $state['steps'][$key]['status'] = 'failed';
                }
            }
            $this->writeJson('state.json', $state);
        }

        return $state;
    }

    /**
     * Status ohne vertrauliche Felder (für die Oberfläche).
     */
    public function publicState(): array
    {
        $state = $this->state();
        unset($state['secret']);

        return $state;
    }

    public function isRunning(): bool
    {
        $handle = @fopen($this->path('run.lock'), 'c');
        if (! $handle) {
            return false;
        }

        $free = flock($handle, LOCK_EX | LOCK_NB);
        if ($free) {
            flock($handle, LOCK_UN);
        }
        fclose($handle);

        return ! $free;
    }

    public function isRequested(): bool
    {
        return ($this->readJson('state.json')['status'] ?? null) === self::STATUS_REQUESTED;
    }

    /**
     * Update zur Ausführung durch den Scheduler vormerken.
     *
     * @param  string  $secret  Geheimnis für den Wartungsmodus-Bypass des anfordernden Admins
     */
    public function request(string $requestedBy, bool $full, string $secret): void
    {
        $status = $this->state()['status'] ?? self::STATUS_IDLE;
        if (in_array($status, [self::STATUS_RUNNING, self::STATUS_REQUESTED], true)) {
            throw new RuntimeException('Es ist bereits ein Update angefordert oder in Ausführung.');
        }

        $this->writeJson('state.json', [
            'status' => self::STATUS_REQUESTED,
            'requested_by' => $requestedBy,
            'requested_at' => date(DATE_ATOM),
            'full' => $full,
            'secret' => $secret,
        ]);
    }

    public function cancelRequest(): bool
    {
        if (! $this->isRequested()) {
            return false;
        }

        $this->writeJson('state.json', ['status' => self::STATUS_IDLE]);

        return true;
    }

    /**
     * Letzte Zeilen des Protokolls des aktuellen bzw. letzten Laufs.
     */
    public function logTail(int $maxBytes = 65536): string
    {
        $log = $this->readJson('state.json')['log'] ?? null;
        $path = $log ? $this->path('logs'.DIRECTORY_SEPARATOR.basename($log)) : null;
        if (! $path || ! is_file($path)) {
            return '';
        }

        $size = filesize($path);
        $handle = fopen($path, 'rb');
        if ($size > $maxBytes) {
            fseek($handle, -$maxBytes, SEEK_END);
        }
        $content = stream_get_contents($handle);
        fclose($handle);

        return $size > $maxBytes ? "…\n".substr($content, strpos($content, "\n") + 1) : $content;
    }

    // ── Prüfung auf Updates ──────────────────────────────────────────────

    public function remote(): string
    {
        return config('updater.remote') ?: 'origin';
    }

    public function branch(): string
    {
        return config('updater.branch') ?: $this->currentBranch();
    }

    public function currentBranch(): string
    {
        return trim($this->git(['rev-parse', '--abbrev-ref', 'HEAD']));
    }

    public function lastCheck(): ?array
    {
        return $this->readJson('check.json');
    }

    /**
     * Holt den Stand des Remotes und vergleicht ihn mit der Installation.
     */
    public function check(bool $fetch = true): array
    {
        $result = [
            'checked_at' => date(DATE_ATOM),
            'remote' => $this->remote(),
            'branch' => null,
            'current_branch' => null,
            'current' => null,
            'latest' => null,
            'behind' => 0,
            'ahead' => 0,
            'commits' => [],
            'local_changes' => [],
            'migrations' => 0,
            'dependencies' => false,
            'assets' => false,
            'fetch_error' => null,
            'error' => null,
        ];

        try {
            $result['branch'] = $branch = $this->branch();
            $result['current_branch'] = $this->currentBranch();
            $target = $this->remote().'/'.$branch;

            if ($fetch) {
                try {
                    $this->git(['fetch', '--quiet', $this->remote(), $branch]);
                } catch (RuntimeException $e) {
                    $result['fetch_error'] = $e->getMessage();
                }
            }

            $result['current'] = $this->commitInfo('HEAD');
            $result['latest'] = $this->commitInfo($target);
            $result['behind'] = (int) trim($this->git(['rev-list', '--count', 'HEAD..'.$target]));
            $result['ahead'] = (int) trim($this->git(['rev-list', '--count', $target.'..HEAD']));
            $result['local_changes'] = $this->localChanges();
            $result['commits'] = $this->commitsBetween('HEAD', $target);

            if ($result['behind'] > 0) {
                $changed = $this->changedFiles('HEAD', $target);
                $result['migrations'] = count(array_filter($changed, fn ($file) => str_starts_with($file, 'database/migrations/') || str_starts_with($file, 'database/settings/')));
                $result['dependencies'] = $this->needsComposer($changed);
                $result['assets'] = $this->needsAssets($changed);
            }
        } catch (Throwable $e) {
            $result['error'] = $e->getMessage();
        }

        $this->writeJson('check.json', $result);

        return $result;
    }

    private function commitInfo(string $ref): array
    {
        [$hash, $short, $date, $author, $subject] = explode("\x1f", trim($this->git(['log', '-1', '--format=%H%x1f%h%x1f%cI%x1f%an%x1f%s', $ref])), 5);

        return compact('hash', 'short', 'date', 'author', 'subject');
    }

    private function commitsBetween(string $from, string $to, int $limit = 50): array
    {
        $output = $this->git(['log', '--no-merges', '-n', (string) $limit, '--format=%H%x1f%h%x1f%cI%x1f%an%x1f%s%x1e', $from.'..'.$to]);

        $commits = [];
        foreach (array_filter(array_map('trim', explode("\x1e", $output))) as $line) {
            [$hash, $short, $date, $author, $subject] = explode("\x1f", $line, 5);
            $commits[] = compact('hash', 'short', 'date', 'author', 'subject');
        }

        return $commits;
    }

    private function changedFiles(string $from, string $to): array
    {
        return array_values(array_filter(array_map('trim', explode("\n", $this->git(['diff', '--name-only', $from, $to])))));
    }

    /**
     * Lokal geänderte, versionierte Dateien.
     */
    private function localChanges(): array
    {
        $files = [];
        foreach (explode("\n", $this->git(['status', '--porcelain', '--untracked-files=no'])) as $line) {
            if (trim($line) !== '') {
                $files[] = trim(substr($line, 3));
            }
        }

        return $files;
    }

    private function needsComposer(array $changed): bool
    {
        return (bool) array_intersect($changed, ['composer.json', 'composer.lock'])
            || ! is_file(base_path('vendor/autoload.php'));
    }

    private function needsAssets(array $changed): bool
    {
        foreach ($changed as $file) {
            foreach (self::ASSET_PATHS as $path) {
                if (str_ends_with($path, '/') ? str_starts_with($file, $path) : $file === $path) {
                    return true;
                }
            }
        }

        return ! is_file(public_path('build/manifest.json'));
    }

    // ── Ausführung ───────────────────────────────────────────────────────

    /**
     * Führt das Update aus. Ein vorgemerktes Update (Oberfläche) wird dabei übernommen.
     *
     * @param  bool  $full  alle Schritte ausführen, auch ohne neue Commits bzw. Änderungen
     * @param  callable|null  $output  erhält jede Protokollzeile (für die Konsole)
     */
    public function run(bool $full = false, ?callable $output = null): bool
    {
        $this->output = $output;

        if (! $this->acquireLock()) {
            throw new RuntimeException('Es läuft bereits ein Update.');
        }

        try {
            $previous = $this->readJson('state.json') ?? [];
            $requested = ($previous['status'] ?? null) === self::STATUS_REQUESTED;
            $full = $full || ($requested && ! empty($previous['full']));

            @mkdir($this->path('logs'), 0775, true);
            $this->logFile = $this->path('logs'.DIRECTORY_SEPARATOR.'update-'.date('Ymd-His').'.log');

            $this->state = [
                'status' => self::STATUS_RUNNING,
                'requested_by' => $requested ? ($previous['requested_by'] ?? null) : 'Konsole',
                'requested_at' => $requested ? ($previous['requested_at'] ?? null) : null,
                'started_at' => date(DATE_ATOM),
                'finished_at' => null,
                'full' => $full,
                'secret' => $requested && ! empty($previous['secret']) ? $previous['secret'] : bin2hex(random_bytes(16)),
                'log' => basename($this->logFile),
                'from' => null,
                'to' => null,
                'message' => null,
                'error' => null,
                'maintenance' => false,
                'steps' => [],
            ];
            foreach (self::STEPS as $key => $label) {
                $this->state['steps'][$key] = ['label' => $label, 'status' => 'pending', 'message' => null];
            }
            $this->saveState();
            $this->ctx = ['full' => $full, 'changed' => []];

            $this->log('Update gestartet'.($full ? ' (vollständig)' : '').' von '.$this->state['requested_by']);

            return $this->runSteps();
        } finally {
            $this->pruneFiles('logs', 20);
            $this->releaseLock();
        }
    }

    private function runSteps(): bool
    {
        $current = null;

        try {
            foreach (array_keys(self::STEPS) as $key) {
                $current = $key;
                $this->startStep($key);

                $result = match ($key) {
                    'preflight' => $this->stepPreflight(),
                    'backup' => $this->stepBackup(),
                    'down' => $this->stepDown(),
                    'pull' => $this->stepPull(),
                    'composer' => $this->stepComposer(),
                    'settings' => $this->artisanStep(['settings:clear-discovered']),
                    'assets' => $this->stepAssets(),
                    'migrate' => $this->artisanStep(['migrate', '--force']),
                    'post' => $this->stepPost(),
                    'cache' => $this->stepCache(),
                    'queue' => $this->artisanStep(['queue:restart']),
                    'up' => $this->stepUp(),
                };

                $this->finishStep($key, $result);

                if ($key === 'preflight' && ! $this->ctx['full'] && $this->ctx['behind'] === 0) {
                    foreach (array_slice(array_keys(self::STEPS), 1) as $skip) {
                        $this->state['steps'][$skip]['status'] = 'skipped';
                    }
                    $this->state['message'] = 'Die Installation ist bereits aktuell.';
                    break;
                }
            }

            $this->state['status'] = self::STATUS_SUCCESS;
            $this->state['message'] ??= 'Update erfolgreich abgeschlossen.';
            $this->state['finished_at'] = date(DATE_ATOM);
            $this->saveState();
            $this->log($this->state['message']);
            // Prüfergebnis an den neuen Stand anpassen (ohne erneuten Fetch)
            $this->check(false);

            return true;
        } catch (Throwable $e) {
            if ($current) {
                $this->state['steps'][$current]['status'] = 'failed';
                $this->state['steps'][$current]['message'] = $e->getMessage();
                $this->state['steps'][$current]['finished_at'] = date(DATE_ATOM);
            }
            $this->log('FEHLER: '.$e->getMessage());

            if ($this->state['maintenance']) {
                if (config('updater.stay_down_on_failure')) {
                    $this->log('Die Anwendung bleibt im Wartungsmodus. Nach Behebung des Fehlers Update erneut ausführen oder Wartungsmodus beenden.');
                } else {
                    try {
                        $this->artisan(['up']);
                        $this->state['maintenance'] = false;
                    } catch (Throwable $upError) {
                        $this->log('Wartungsmodus konnte nicht beendet werden: '.$upError->getMessage());
                    }
                }
            }

            $this->state['status'] = self::STATUS_FAILED;
            $this->state['error'] = $e->getMessage();
            $this->state['finished_at'] = date(DATE_ATOM);
            $this->saveState();

            return false;
        }
    }

    private function stepPreflight(): array
    {
        $branch = $this->branch();
        $remote = $this->remote();
        $currentBranch = $this->currentBranch();

        if ($currentBranch !== $branch) {
            throw new RuntimeException("Ausgecheckt ist „{$currentBranch}“, konfiguriert ist „{$branch}“ (UPDATER_BRANCH). Der Updater wechselt keine Branches.");
        }

        $this->runProcess([$this->gitBinary(), '-c', 'safe.directory='.base_path(), 'fetch', $remote, $branch]);

        $target = $remote.'/'.$branch;
        $this->ctx['target'] = $target;
        $this->ctx['from'] = trim($this->git(['rev-parse', 'HEAD']));
        $this->ctx['behind'] = (int) trim($this->git(['rev-list', '--count', 'HEAD..'.$target]));
        $ahead = (int) trim($this->git(['rev-list', '--count', $target.'..HEAD']));
        $this->state['from'] = $this->ctx['from'];

        if ($ahead > 0) {
            throw new RuntimeException("Die Installation enthält {$ahead} lokale Commit(s), die nicht in {$target} sind – kein Fast-Forward möglich.");
        }

        if ($this->ctx['behind'] > 0) {
            $this->ctx['changed'] = $this->changedFiles('HEAD', $target);

            $conflicts = array_intersect($this->localChanges(), $this->ctx['changed']);
            if ($conflicts) {
                throw new RuntimeException('Lokal geänderte Dateien würden überschrieben: '.implode(', ', $conflicts));
            }
        }

        $php = $this->phpBinary();
        $this->log('PHP: '.$php.' | Git-Branch: '.$branch.' | Ziel: '.$target);

        return $this->done($this->ctx['behind'] > 0
            ? $this->ctx['behind'].' neue(r) Commit(s) verfügbar.'
            : 'Keine neuen Commits'.($this->ctx['full'] ? ' – vollständige Ausführung angefordert.' : '.'));
    }

    private function stepBackup(): array
    {
        if (! config('updater.backup.enabled')) {
            return $this->skip('Deaktiviert (UPDATER_BACKUP=false).');
        }

        try {
            $file = $this->backupDatabase();
        } catch (Throwable $e) {
            if (config('updater.backup.required')) {
                throw new RuntimeException('Datenbanksicherung fehlgeschlagen: '.$e->getMessage());
            }

            return $this->warn('Sicherung fehlgeschlagen: '.$e->getMessage());
        }

        $this->pruneFiles('backups', max(1, (int) config('updater.backup.keep')));

        return $this->done('Gesichert in storage/app/updater/backups/'.basename($file).' ('.round(filesize($file) / 1048576, 1).' MB).');
    }

    private function backupDatabase(): string
    {
        $config = config('database.connections.'.config('database.default'));
        @mkdir($this->path('backups'), 0775, true);
        $base = $this->path('backups'.DIRECTORY_SEPARATOR.'db-'.date('Ymd-His').'-'.substr($this->ctx['from'], 0, 7));

        switch ($config['driver']) {
            case 'mysql':
            case 'mariadb':
                $binary = $this->findExecutable(config('updater.binaries.mysqldump'))
                    ?? throw new RuntimeException('mysqldump nicht gefunden (UPDATER_MYSQLDUMP_BINARY).');

                $command = [$binary, '--single-transaction', '--quick', '--routines', '--no-tablespaces',
                    '--default-character-set=utf8mb4', '--user='.$config['username']];
                if (! empty($config['unix_socket'])) {
                    $command[] = '--socket='.$config['unix_socket'];
                } else {
                    $command[] = '--host='.(is_array($config['host']) ? reset($config['host']) : $config['host']);
                    $command[] = '--port='.$config['port'];
                }
                $command[] = '--result-file='.$base.'.sql';
                $command[] = $config['database'];

                // Passwort über die Umgebung, damit es nicht in der Prozessliste erscheint
                $this->runProcess($command, ['MYSQL_PWD' => (string) $config['password']]);

                return $this->gzip($base.'.sql');

            case 'sqlite':
                if (! copy($config['database'], $base.'.sqlite')) {
                    throw new RuntimeException('SQLite-Datei konnte nicht kopiert werden.');
                }

                return $this->gzip($base.'.sqlite');

            default:
                throw new RuntimeException("Datenbanktreiber „{$config['driver']}“ wird nicht unterstützt.");
        }
    }

    private function gzip(string $file): string
    {
        if (! function_exists('gzopen')) {
            return $file;
        }

        $in = fopen($file, 'rb');
        $out = gzopen($file.'.gz', 'wb6');
        while (! feof($in)) {
            gzwrite($out, fread($in, 1048576));
        }
        fclose($in);
        gzclose($out);
        unlink($file);

        return $file.'.gz';
    }

    private function stepDown(): array
    {
        $this->artisan(['down', '--retry=60', '--secret='.$this->state['secret']]);
        $this->state['maintenance'] = true;

        return $this->done();
    }

    private function stepPull(): array
    {
        if ($this->ctx['behind'] === 0) {
            $this->state['to'] = $this->ctx['from'];

            return $this->skip('Keine neuen Commits.');
        }

        $this->runProcess([$this->gitBinary(), '-c', 'safe.directory='.base_path(), 'pull', '--ff-only', $this->remote(), $this->branch()]);

        $this->state['to'] = trim($this->git(['rev-parse', 'HEAD']));
        $this->ctx['changed'] = $this->changedFiles($this->ctx['from'], $this->state['to']);

        return $this->done(substr($this->ctx['from'], 0, 7).' → '.substr($this->state['to'], 0, 7).', '.count($this->ctx['changed']).' Datei(en) geändert.');
    }

    private function stepComposer(): array
    {
        if (! $this->ctx['full'] && config('updater.smart_steps') && ! $this->needsComposer($this->ctx['changed'])) {
            return $this->skip('composer.json/composer.lock unverändert.');
        }

        $binary = config('updater.binaries.composer') ?: 'composer';
        $command = str_ends_with($binary, '.phar')
            ? [$this->phpBinary(), $binary]
            : [$this->findExecutable($binary) ?? throw new RuntimeException("Composer nicht gefunden ({$binary}, UPDATER_COMPOSER_BINARY).")];

        $this->runProcess([...$command, ...config('updater.composer_args')], ['COMPOSER_NO_INTERACTION' => '1']);

        return $this->done();
    }

    private function stepAssets(): array
    {
        if (! config('updater.build_assets')) {
            return $this->skip('Deaktiviert (UPDATER_BUILD_ASSETS=false).');
        }
        if (! $this->ctx['full'] && config('updater.smart_steps') && ! $this->needsAssets($this->ctx['changed'])) {
            return $this->skip('Keine Änderungen an Frontend-Dateien.');
        }

        $npm = $this->findExecutable(config('updater.binaries.npm') ?: 'npm');
        if (! $npm) {
            return $this->warn('npm nicht gefunden – Assets (public/build) bitte separat bauen und hochladen.');
        }

        $this->runProcess([$npm, 'ci', '--no-audit', '--no-fund']);
        $this->runProcess([$npm, 'run', 'build']);

        return $this->done();
    }

    private function stepPost(): array
    {
        $commands = config('updater.post_update_commands', []);
        if (! $commands) {
            return $this->skip('Keine Zusatzbefehle konfiguriert.');
        }

        foreach ($commands as $command) {
            $this->artisan((array) $command);
        }

        return $this->done(count($commands).' Befehl(e) ausgeführt.');
    }

    private function stepCache(): array
    {
        // bewusst kein cache:clear – Sitzungen/Sperren im Cache bleiben erhalten
        foreach (['config:clear', 'route:clear', 'view:clear', 'event:clear', 'settings:clear-cache'] as $command) {
            $this->artisan([$command]);
        }

        return $this->done();
    }

    private function stepUp(): array
    {
        $this->artisan(['up']);
        $this->state['maintenance'] = false;

        return $this->done();
    }

    private function artisanStep(array $arguments): array
    {
        $this->artisan($arguments);

        return $this->done();
    }

    private function done(?string $message = null): array
    {
        return ['status' => 'done', 'message' => $message];
    }

    private function skip(string $message): array
    {
        return ['status' => 'skipped', 'message' => $message];
    }

    private function warn(string $message): array
    {
        return ['status' => 'warning', 'message' => $message];
    }

    private function startStep(string $key): void
    {
        $this->state['steps'][$key]['status'] = 'running';
        $this->state['steps'][$key]['started_at'] = date(DATE_ATOM);
        $this->saveState();
        $this->log('── '.self::STEPS[$key]);
    }

    private function finishStep(string $key, array $result): void
    {
        $this->state['steps'][$key]['status'] = $result['status'];
        $this->state['steps'][$key]['message'] = $result['message'];
        $this->state['steps'][$key]['finished_at'] = date(DATE_ATOM);
        $this->saveState();

        if ($result['message']) {
            $this->log(($result['status'] === 'skipped' ? 'Übersprungen: ' : '').$result['message']);
        }
    }

    private function saveState(): void
    {
        $this->writeJson('state.json', $this->state);
    }

    // ── Prozesse ─────────────────────────────────────────────────────────

    private function artisan(array $arguments): string
    {
        return $this->runProcess([$this->phpBinary(), base_path('artisan'), ...$arguments, '--no-interaction', '--no-ansi']);
    }

    /**
     * Git-Befehl ohne Protokollierung (für Abfragen).
     */
    private function git(array $arguments): string
    {
        return $this->runProcess([$this->gitBinary(), '-c', 'safe.directory='.base_path(), ...$arguments], [], 120, false);
    }

    private function runProcess(array $command, array $env = [], ?int $timeout = null, bool $log = true): string
    {
        if ($log) {
            $this->log('$ '.implode(' ', $command));
        }

        // Composer und npm benötigen ein beschreibbares Home-Verzeichnis (fehlt oft unter Cron/www-data)
        if (! getenv('HOME')) {
            @mkdir($this->path('home'), 0775, true);
            $env['HOME'] = $this->path('home');
        }

        $process = new Process($command, base_path(), $env, null, $timeout ?? (int) config('updater.step_timeout', 900));

        $buffer = '';
        $process->run(function ($type, $data) use (&$buffer, $log) {
            $buffer .= $data;
            if ($log) {
                $this->write($data);
            }
        });

        if (! $process->isSuccessful()) {
            $lines = array_slice(array_filter(explode("\n", trim($buffer))), -10);
            throw new RuntimeException(sprintf(
                '„%s“ fehlgeschlagen (Exit-Code %s)%s',
                implode(' ', array_slice($command, 0, 4)),
                $process->getExitCode() ?? '–',
                $lines ? ': '.implode(' | ', $lines) : ''
            ));
        }

        return $buffer;
    }

    private function phpBinary(): string
    {
        return config('updater.binaries.php') ?: ((new PhpExecutableFinder)->find(false) ?: 'php');
    }

    private function gitBinary(): string
    {
        return $this->findExecutable(config('updater.binaries.git') ?: 'git') ?? 'git';
    }

    private function findExecutable(string $name): ?string
    {
        if (str_contains($name, '/') || str_contains($name, '\\')) {
            return is_file($name) ? $name : null;
        }

        return (new ExecutableFinder)->find($name);
    }

    // ── Protokoll, Sperre, Aufräumen ─────────────────────────────────────

    private function log(string $message): void
    {
        $this->write('['.date('H:i:s').'] '.$message."\n");
    }

    private function write(string $text): void
    {
        // Geheimnis des Wartungsmodus-Bypass nicht protokollieren
        if (! empty($this->state['secret'])) {
            $text = str_replace($this->state['secret'], '***', $text);
        }

        if ($this->logFile) {
            file_put_contents($this->logFile, $text, FILE_APPEND);
        }
        if ($this->output) {
            ($this->output)($text);
        }
    }

    private function acquireLock(): bool
    {
        $this->lockHandle = fopen($this->path('run.lock'), 'c');

        if (! $this->lockHandle || ! flock($this->lockHandle, LOCK_EX | LOCK_NB)) {
            $this->lockHandle = null;

            return false;
        }

        return true;
    }

    private function releaseLock(): void
    {
        if ($this->lockHandle) {
            flock($this->lockHandle, LOCK_UN);
            fclose($this->lockHandle);
            $this->lockHandle = null;
        }
    }

    /**
     * Behält nur die neuesten $keep Dateien eines Unterordners.
     */
    private function pruneFiles(string $directory, int $keep): void
    {
        $files = glob($this->path($directory).DIRECTORY_SEPARATOR.'*') ?: [];
        usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));

        foreach (array_slice($files, $keep) as $file) {
            @unlink($file);
        }
    }
}
