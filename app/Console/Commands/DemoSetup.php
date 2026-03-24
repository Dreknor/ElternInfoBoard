<?php

namespace App\Console\Commands;

use App\Model\AbfrageOptions;
use App\Model\Arbeitsgemeinschaft;
use App\Model\Child;
use App\Model\ChildCheckIn;
use App\Model\ChildMandate;
use App\Model\Discussion;
use App\Model\Disease;
use App\Model\ElternratEvent;
use App\Model\ElternratTask;
use App\Model\EventAttendee;
use App\Model\Group;
use App\Model\Holiday;
use App\Model\Krankmeldungen;
use App\Model\Listen_Eintragungen;
use App\Model\listen_termine;
use App\Model\Liste;
use App\Model\Losung;
use App\Model\Module;
use App\Model\Notification;
use App\Model\Pflichtstunde;
use App\Model\Poll;
use App\Model\Poll_Option;
use App\Model\Poll_Votes;
use App\Model\Post;
use App\Model\ReadReceipts;
use App\Model\Reinigung;
use App\Model\Rueckmeldungen;
use App\Model\Schickzeiten;
use App\Model\Site;
use App\Model\SiteBlock;
use App\Model\SiteBlockText;
use App\Model\Termin;
use App\Model\User;
use App\Model\UserRueckmeldungen;
use App\Model\VertretungsplanNews;
use App\Settings\CareSetting;
use App\Settings\GeneralSetting;
use App\Settings\NotifySetting;
use App\Settings\PflichtstundenSetting;
use App\Settings\SchickzeitenSetting;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DemoSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:setup
                            {--fresh : Datenbank vorher leeren (migrate:fresh --seed)}
                            {--force : Bestätigungsabfragen überspringen}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Legt Demo-Daten für eine Vorführung des ElternInfoBoards an (Ev. Schulzentrum Radebeul)';

    private array $groups = [];
    private array $users = [];
    private array $children = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->newLine();
        $this->line('╔══════════════════════════════════════════════════════════╗');
        $this->line('║   ElternInfoBoard – Demo-Setup                          ║');
        $this->line('╚══════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Sicherheitscheck
        if (!$this->option('force')) {
            if (!$this->confirm('⚠️  Dieser Befehl legt Demo-Daten in der Datenbank an. Fortfahren?', true)) {
                $this->info('Abgebrochen.');
                return self::SUCCESS;
            }
        }

        // Optionale Migration
        if ($this->option('fresh')) {
            if (!$this->option('force')) {
                $this->warn('⚠️  --fresh löscht die gesamte Datenbank und führt migrate:fresh --seed aus!');
                if (!$this->confirm('Wirklich fortfahren?', false)) {
                    $this->info('Abgebrochen.');
                    return self::SUCCESS;
                }
            }
            $this->info('🔄 Führe migrate:fresh --seed aus...');
            $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);
        } else {
            // Prüfen ob bereits Demo-Daten vorhanden
            if (User::where('email', 'schulleitung@ev-schulzentrum-demo.de')->exists()) {
                $this->warn('⚠️  Demo-Daten scheinen bereits vorhanden zu sein (Demo-Benutzer gefunden).');
                if (!$this->option('force') && !$this->confirm('Trotzdem fortfahren?', false)) {
                    $this->info('Abgebrochen. Nutze --fresh um alle Daten neu anzulegen.');
                    return self::SUCCESS;
                }
            }
        }

        $this->newLine();
        $this->info('📝 Bitte geben Sie die Admin-Zugangsdaten ein:');
        $adminName = $this->ask('Name des Administrators', 'Administrator');
        $adminEmail = $this->ask('E-Mail-Adresse');
        while (empty($adminEmail) || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $this->error('Bitte eine gültige E-Mail-Adresse eingeben.');
            $adminEmail = $this->ask('E-Mail-Adresse');
        }
        $adminPassword = $this->secret('Passwort (min. 8 Zeichen)');
        while (empty($adminPassword) || strlen($adminPassword) < 8) {
            $this->error('Das Passwort muss mindestens 8 Zeichen lang sein.');
            $adminPassword = $this->secret('Passwort (min. 8 Zeichen)');
        }
        $adminPasswordConfirm = $this->secret('Passwort bestätigen');
        while ($adminPassword !== $adminPasswordConfirm) {
            $this->error('Die Passwörter stimmen nicht überein.');
            $adminPassword = $this->secret('Passwort (min. 8 Zeichen)');
            $adminPasswordConfirm = $this->secret('Passwort bestätigen');
        }

        $this->newLine();

        try {
            DB::transaction(function () use ($adminName, $adminEmail, $adminPassword) {
                $this->runSetup($adminName, $adminEmail, $adminPassword);
            });
        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Fehler beim Anlegen der Demo-Daten: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return self::FAILURE;
        }

        $this->newLine();
        $this->line('╔══════════════════════════════════════════════════════════╗');
        $this->line('║   ✅  Demo-Daten erfolgreich angelegt!                  ║');
        $this->line('╚══════════════════════════════════════════════════════════╝');
        $this->newLine();
        $this->table(
            ['Account', 'E-Mail', 'Passwort'],
            [
                ['Administrator', $adminEmail, '(wie eingegeben)'],
                ['Schulleitung', 'schulleitung@ev-schulzentrum-demo.de', 'Demo1234!'],
                ['Sekretariat', 'sekretariat@ev-schulzentrum-demo.de', 'Demo1234!'],
                ['Klassenlehrerin 3b', 'hoffmann@ev-schulzentrum-demo.de', 'Demo1234!'],
                ['Elternrat-Sprecher', 'elternrat@ev-schulzentrum-demo.de', 'Demo1234!'],
                ['Familie Müller', 'mueller@ev-schulzentrum-demo.de', 'Demo1234!'],
                ['Familie Schulz', 'schulz@ev-schulzentrum-demo.de', 'Demo1234!'],
                ['Familie Fischer', 'fischer@ev-schulzentrum-demo.de', 'Demo1234!'],
                ['Familie Koch', 'koch@ev-schulzentrum-demo.de', 'Demo1234!'],
                ['Familie Bauer', 'bauer@ev-schulzentrum-demo.de', 'Demo1234!'],
                ['Familie Wagner', 'wagner@ev-schulzentrum-demo.de', 'Demo1234!'],
            ]
        );
        $this->newLine();
        $this->info('🌐 Öffnen Sie die Anwendung im Browser und melden Sie sich an!');
        $this->newLine();

        return self::SUCCESS;
    }

    private function runSetup(string $adminName, string $adminEmail, string $adminPassword): void
    {
        $this->setupStep('Berechtigungen & Rollen', fn() => $this->createPermissionsAndRoles());
        $admin = $this->setupStep('Admin-Benutzer', fn() => $this->createAdminUser($adminName, $adminEmail, $adminPassword));
        $this->setupStep('Demo-Benutzer', fn() => $this->createDemoUsers());
        $this->setupStep('Gruppen', fn() => $this->createGroups());
        $this->setupStep('Benutzer den Gruppen zuordnen', fn() => $this->assignUsersToGroups());
        $this->setupStep('Kinder', fn() => $this->createChildren());
        $this->setupStep('Einstellungen', fn() => $this->configureSettings());
        $this->setupStep('Module aktivieren', fn() => $this->activateModules());
        $this->setupStep('Krankheiten', fn() => $this->createDiseases());
        $this->setupStep('Krankmeldungen', fn() => $this->createKrankmeldungen());
        $this->setupStep('Schickzeiten', fn() => $this->createSchickzeiten());
        $this->setupStep('Nachrichten (Posts)', fn() => $this->createPosts($admin));
        $this->setupStep('Termine', fn() => $this->createTermine($admin));
        $this->setupStep('Listen & Elterngespräche', fn() => $this->createListen($admin));
        $this->setupStep('Elternrat', fn() => $this->createElternrat($admin));
        $this->setupStep('Vertretungsplan', fn() => $this->createVertretungen());
        $this->setupStep('Losungen', fn() => $this->createLosungen());
        $this->setupStep('Pflichtstunden', fn() => $this->createPflichtstunden($admin));
        $this->setupStep('Infoseiten', fn() => $this->createSites($admin));
        $this->setupStep('Schulferien', fn() => $this->createHolidays());
        $this->setupStep('Arbeitsgemeinschaften', fn() => $this->createArbeitsgemeinschaften($admin));
        $this->setupStep('Benachrichtigungen', fn() => $this->createNotifications($admin));
        // Admin allen Gruppen zuordnen
        $this->setupStep('Admin zu allen Gruppen hinzufügen', fn() => $this->addAdminToAllGroups($admin));
    }

    private function setupStep(string $name, callable $fn): mixed
    {
        $this->line("  ⏳ {$name}...");
        $result = $fn();
        $this->line("  <fg=green>✓</> {$name}");
        return $result;
    }

    // =========================================================================
    // 1. Permissions & Rollen
    // =========================================================================
    private function createPermissionsAndRoles(): void
    {
        $permissions = [
            'edit permission', 'edit user', 'create posts', 'view all', 'edit posts',
            'upload files', 'import user', 'release posts', 'use scriptTag', 'view elternrat',
            'send urgent message', 'edit reinigung', 'upload great files', 'edit termin',
            'edit terminliste', 'create terminliste', 'view protected', 'add changelog',
            'set password', 'make sticky', 'edit settings', 'allow password-less-login',
            'manage rueckmeldungen', 'push to wordpress', 'view rueckmeldungen',
            'view reinigung', 'delete elternrat file', 'download schickzeiten',
            'edit schickzeiten', 'view schickzeiten', 'view krankmeldung', 'view groups',
            'view mitarbeiterboard', 'loginAsUser', 'can:scan files', 'view vertretungsplan',
            'edit GTA', 'schoolyear.change', 'delete logs', 'see logs',
            'view external offer', 'view sites', 'create sites',
            'manage pflichtstunden', 'manage elternrat events', 'manage elternrat tasks',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $roles = [
            'Administrator' => $permissions, // alle Rechte
            'Mitarbeiter' => [
                'create posts', 'edit posts', 'release posts', 'upload files',
                'edit termin', 'edit terminliste', 'create terminliste',
                'view krankmeldung', 'view schickzeiten', 'view rueckmeldungen',
                'view vertretungsplan', 'view mitarbeiterboard', 'view groups',
                'manage rueckmeldungen', 'view external offer',
            ],
            'Elternrat' => [
                'view elternrat', 'view groups', 'manage elternrat events', 'manage elternrat tasks',
            ],
            'Sekretariat' => [
                'view krankmeldung', 'edit schickzeiten', 'view schickzeiten',
                'download schickzeiten', 'view groups', 'view rueckmeldungen',
                'manage pflichtstunden', 'view mitarbeiterboard',
            ],
        ];

        foreach ($roles as $roleName => $rolePerms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $permObjects = Permission::whereIn('name', $rolePerms)->get();
            $role->syncPermissions($permObjects);
        }
    }

    // =========================================================================
    // 2. Admin-Benutzer
    // =========================================================================
    private function createAdminUser(string $name, string $email, string $password): User
    {
        $admin = User::withTrashed()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'changePassword' => false,
                'benachrichtigung' => 1,
                'email_verified_at' => now(),
            ]
        );
        $admin->restore();
        $admin->syncRoles(['Administrator']);
        $this->users['admin'] = $admin;
        return $admin;
    }

    // =========================================================================
    // 3. Demo-Benutzer
    // =========================================================================
    private function createDemoUsers(): void
    {
        $demoPassword = Hash::make('Demo1234!');

        $usersData = [
            'schulleitung' => [
                'name' => 'Herr Dr. Thomas Schneider (Schulleitung)',
                'email' => 'schulleitung@ev-schulzentrum-demo.de',
                'roles' => ['Administrator', 'Mitarbeiter'],
            ],
            'sekretariat' => [
                'name' => 'Frau Anke Weber (Sekretariat)',
                'email' => 'sekretariat@ev-schulzentrum-demo.de',
                'roles' => ['Sekretariat'],
            ],
            'lehrerin' => [
                'name' => 'Frau Christine Hoffmann (Klassenlehrerin 3b)',
                'email' => 'hoffmann@ev-schulzentrum-demo.de',
                'roles' => ['Mitarbeiter'],
            ],
            'lehrer2' => [
                'name' => 'Herr Martin Berger (Klassenlehrer 1a)',
                'email' => 'berger@ev-schulzentrum-demo.de',
                'roles' => ['Mitarbeiter'],
            ],
            'elternrat' => [
                'name' => 'Sandra Müller (Elternratsvorsitzende)',
                'email' => 'elternrat@ev-schulzentrum-demo.de',
                'roles' => ['Elternrat'],
            ],
            'mueller' => [
                'name' => 'Familie Müller',
                'email' => 'mueller@ev-schulzentrum-demo.de',
                'roles' => [],
            ],
            'schulz' => [
                'name' => 'Familie Schulz',
                'email' => 'schulz@ev-schulzentrum-demo.de',
                'roles' => [],
            ],
            'fischer' => [
                'name' => 'Familie Fischer',
                'email' => 'fischer@ev-schulzentrum-demo.de',
                'roles' => [],
            ],
            'koch' => [
                'name' => 'Familie Koch',
                'email' => 'koch@ev-schulzentrum-demo.de',
                'roles' => [],
            ],
            'bauer' => [
                'name' => 'Familie Bauer',
                'email' => 'bauer@ev-schulzentrum-demo.de',
                'roles' => [],
            ],
            'wagner' => [
                'name' => 'Familie Wagner',
                'email' => 'wagner@ev-schulzentrum-demo.de',
                'roles' => [],
            ],
        ];

        foreach ($usersData as $key => $data) {
            $user = User::withTrashed()->firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => $demoPassword,
                    'changePassword' => false,
                    'benachrichtigung' => 1,
                    'email_verified_at' => now(),
                ]
            );
            $user->restore();
            if (!empty($data['roles'])) {
                $user->syncRoles($data['roles']);
            }
            $this->users[$key] = $user;
        }
    }

    // =========================================================================
    // 4. Gruppen
    // =========================================================================
    private function createGroups(): void
    {
        $groupsData = [
            // Klassen
            'klasse_1a' => ['name' => '1a – Frösche', 'bereich' => 'Klassen', 'protected' => false],
            'klasse_1b' => ['name' => '1b – Schmetterlinge', 'bereich' => 'Klassen', 'protected' => false],
            'klasse_2a' => ['name' => '2a – Spatzen', 'bereich' => 'Klassen', 'protected' => false],
            'klasse_3b' => ['name' => '3b – Adler', 'bereich' => 'Klassen', 'protected' => false],
            'klasse_4a' => ['name' => '4a – Löwen', 'bereich' => 'Klassen', 'protected' => false],
            // Schulgemeinschaft
            'gesamtelternschaft' => ['name' => 'Gesamtelternschaft', 'bereich' => 'Schule', 'protected' => false],
            'elternrat_gruppe' => ['name' => 'Elternrat', 'bereich' => 'Schule', 'protected' => true],
            'hort' => ['name' => 'Hort', 'bereich' => 'Betreuung', 'protected' => false],
            'foerderverein' => ['name' => 'Förderverein', 'bereich' => 'Schule', 'protected' => false],
        ];

        foreach ($groupsData as $key => $data) {
            // Direkt per DB::table, da GetGroupsScope sonst ohne Auth nichts zurückgibt
            $existing = DB::table('groups')->where('name', $data['name'])->first();
            if (!$existing) {
                $id = DB::table('groups')->insertGetId(array_merge($data, [
                    'owner_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
                $this->groups[$key] = $id;
            } else {
                $this->groups[$key] = $existing->id;
            }
        }
    }

    // =========================================================================
    // 5. Benutzer den Gruppen zuordnen
    // =========================================================================
    private function assignUsersToGroups(): void
    {
        $assignments = [
            // Klasse 1a: Familie Müller, Familie Bauer
            'klasse_1a' => ['mueller', 'bauer', 'lehrer2'],
            // Klasse 1b: Familie Schulz
            'klasse_1b' => ['schulz'],
            // Klasse 2a: Familie Fischer
            'klasse_2a' => ['fischer'],
            // Klasse 3b: Familie Koch, Familie Wagner, Lehrerin Hoffmann
            'klasse_3b' => ['koch', 'wagner', 'lehrerin'],
            // Gesamtelternschaft: alle
            'gesamtelternschaft' => ['schulleitung', 'sekretariat', 'lehrerin', 'lehrer2',
                'elternrat', 'mueller', 'schulz', 'fischer', 'koch', 'bauer', 'wagner'],
            // Elternrat: nur Elternrat-Mitglieder
            'elternrat_gruppe' => ['elternrat', 'mueller', 'fischer'],
            // Hort: einige Familien
            'hort' => ['mueller', 'fischer', 'bauer'],
            // Förderverein
            'foerderverein' => ['elternrat', 'mueller', 'schulz', 'bauer', 'wagner'],
        ];

        foreach ($assignments as $groupKey => $userKeys) {
            if (!isset($this->groups[$groupKey])) {
                continue;
            }
            $groupId = $this->groups[$groupKey];
            foreach ($userKeys as $userKey) {
                if (!isset($this->users[$userKey])) {
                    continue;
                }
                $userId = $this->users[$userKey]->id;
                $exists = DB::table('group_user')
                    ->where('group_id', $groupId)
                    ->where('user_id', $userId)
                    ->exists();
                if (!$exists) {
                    DB::table('group_user')->insert([
                        'group_id' => $groupId,
                        'user_id' => $userId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    // =========================================================================
    // 6. Kinder
    // =========================================================================
    private function createChildren(): void
    {
        $childrenData = [
            // Familie Müller – Klasse 1a
            'lena_mueller' => [
                'first_name' => 'Lena', 'last_name' => 'Müller',
                'group_id' => $this->groups['klasse_1a'],
                'class_id' => $this->groups['klasse_1a'],
                'parent' => 'mueller',
                'mandates' => ['Darf alleine nach Hause'],
                'hort' => true,
            ],
            // Familie Schulz – Klasse 1b
            'max_schulz' => [
                'first_name' => 'Max', 'last_name' => 'Schulz',
                'group_id' => $this->groups['klasse_1b'],
                'class_id' => $this->groups['klasse_1b'],
                'parent' => 'schulz',
                'mandates' => [],
                'hort' => false,
            ],
            // Familie Fischer – Klasse 2a
            'emma_fischer' => [
                'first_name' => 'Emma', 'last_name' => 'Fischer',
                'group_id' => $this->groups['klasse_2a'],
                'class_id' => $this->groups['klasse_2a'],
                'parent' => 'fischer',
                'mandates' => ['Wird von Großeltern abgeholt'],
                'hort' => true,
            ],
            // Familie Koch – Klasse 3b
            'paul_koch' => [
                'first_name' => 'Paul', 'last_name' => 'Koch',
                'group_id' => $this->groups['klasse_3b'],
                'class_id' => $this->groups['klasse_3b'],
                'parent' => 'koch',
                'mandates' => ['Darf zu Fuß nach Hause', 'Darf zum Sportverein'],
                'hort' => false,
            ],
            // Familie Koch – 2. Kind – Klasse 1a
            'marie_koch' => [
                'first_name' => 'Marie', 'last_name' => 'Koch',
                'group_id' => $this->groups['klasse_1a'],
                'class_id' => $this->groups['klasse_1a'],
                'parent' => 'koch',
                'mandates' => [],
                'hort' => true,
            ],
            // Familie Bauer – Klasse 1a
            'tim_bauer' => [
                'first_name' => 'Tim', 'last_name' => 'Bauer',
                'group_id' => $this->groups['klasse_1a'],
                'class_id' => $this->groups['klasse_1a'],
                'parent' => 'bauer',
                'mandates' => ['Darf mit dem Bus nach Hause'],
                'hort' => true,
            ],
            // Familie Wagner – Klasse 3b
            'anna_wagner' => [
                'first_name' => 'Anna', 'last_name' => 'Wagner',
                'group_id' => $this->groups['klasse_3b'],
                'class_id' => $this->groups['klasse_3b'],
                'parent' => 'wagner',
                'mandates' => [],
                'hort' => false,
            ],
        ];

        foreach ($childrenData as $key => $data) {
            $child = Child::firstOrCreate(
                ['first_name' => $data['first_name'], 'last_name' => $data['last_name']],
                [
                    'group_id' => $data['group_id'],
                    'class_id' => $data['class_id'],
                    'notification' => true,
                    'auto_checkIn' => false,
                ]
            );
            $this->children[$key] = $child;

            // Eltern-Kind-Verknüpfung
            if (isset($this->users[$data['parent']])) {
                $exists = DB::table('child_user')
                    ->where('child_id', $child->id)
                    ->where('user_id', $this->users[$data['parent']]->id)
                    ->exists();
                if (!$exists) {
                    DB::table('child_user')->insert([
                        'child_id' => $child->id,
                        'user_id' => $this->users[$data['parent']]->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Mandate anlegen
            foreach ($data['mandates'] as $mandate) {
                ChildMandate::firstOrCreate(
                    ['child_id' => $child->id, 'mandate_name' => $mandate],
                    [
                        'mandate_description' => '',
                        'created_by' => $this->users[$data['parent']]->id ?? null,
                    ]
                );
            }

            // CheckIn für heute
            $checkIn = ChildCheckIn::firstOrCreate(
                ['child_id' => $child->id, 'date' => today()],
                [
                    'should_be' => $data['hort'],
                    'checked_in' => $data['hort'],
                    'checked_out' => false,
                    'checked_in_at' => $data['hort'] ? now()->setTime(7, 30) : null,
                ]
            );
        }
    }

    // =========================================================================
    // 7. Einstellungen
    // =========================================================================
    private function configureSettings(): void
    {
        // GeneralSettings
        try {
            $general = app(GeneralSetting::class);
            $general->app_name = 'ElternInfoBoard – Ev. Schulzentrum Radebeul';
            $general->logo = '';
            $general->favicon = '';
            $general->save();
        } catch (\Exception $e) {
            // Settings ggf. noch nicht migriert
        }

        // NotifySettings
        try {
            $notify = app(NotifySetting::class);
            $notify->hour_send_information_mail = 7;
            $notify->weekday_send_information_mail = 1;
            $notify->hour_send_reminder_mail = 6;
            $notify->krankmeldungen_report_hour = 8;
            $notify->krankmeldungen_report_minute = 0;
            $notify->schickzeiten_report_hour = 7;
            $notify->schickzeiten_report_weekday = 5;
            $notify->save();
        } catch (\Exception $e) {
            //
        }

        // SchickzeitenSettings
        try {
            $schick = app(SchickzeitenSetting::class);
            $schick->schicken_ab = '12:00';
            $schick->schicken_bis = '15:30';
            $schick->schicken_text = 'Bitte tragen Sie die regelmäßigen Abholzeiten Ihres Kindes ein.';
            $schick->schicken_intervall = 15;
            $schick->save();
        } catch (\Exception $e) {
            //
        }

        // PflichtstundenSettings
        try {
            $pflicht = app(PflichtstundenSetting::class);
            $pflicht->pflichtstunden_start = Carbon::now()->startOfYear()->format('m-d');
            $pflicht->pflichtstunden_ende = Carbon::now()->endOfYear()->format('m-d');
            $pflicht->pflichtstunden_text = 'Im Rahmen unseres Schulgemeinschaftsvertrages leisten alle Familien pro Schuljahr mindestens 10 Pflichtstunden. Diese können bei Schulfesten, Arbeitseinsätzen und Elternabenden erbracht werden.';
            $pflicht->pflichtstunden_anzahl = 10;
            $pflicht->pflichtstunden_betrag = 12.50;
            $pflicht->listen_autocreate = false;
            $pflicht->pflichtstunden_bereiche = ['Schulfest', 'Arbeitseinsatz', 'Elternabend', 'Reinigung', 'Bibliothek'];
            $pflicht->save();
        } catch (\Exception $e) {
            //
        }

        // CareSettings
        try {
            $care = app(CareSetting::class);
            $care->view_detailed_care = true;
            $care->hide_childs_when_absent = false;
            $care->groups_list = array_values(array_filter([
                $this->groups['hort'] ?? null,
                $this->groups['klasse_1a'] ?? null,
                $this->groups['klasse_1b'] ?? null,
                $this->groups['klasse_2a'] ?? null,
            ]));
            $care->class_list = array_values(array_filter([
                $this->groups['klasse_1a'] ?? null,
                $this->groups['klasse_1b'] ?? null,
                $this->groups['klasse_2a'] ?? null,
                $this->groups['klasse_3b'] ?? null,
                $this->groups['klasse_4a'] ?? null,
            ]));
            $care->hide_groups_when_empty = false;
            $care->show_message_on_empty_group = true;
            $care->end_time = '16:30';
            $care->info_to = $this->users['sekretariat']->id ?? null;
            $care->save();
        } catch (\Exception $e) {
            //
        }
    }

    // =========================================================================
    // 8. Module aktivieren
    // =========================================================================
    private function activateModules(): void
    {
        Module::all()->each(function (Module $module) {
            $options = $module->options ?? [];
            $options['active'] = '1';
            $module->options = $options;
            $module->save();
        });
    }

    // =========================================================================
    // 9. Krankheiten (wenn noch nicht vorhanden)
    // =========================================================================
    private function createDiseases(): void
    {
        if (Disease::count() > 0) {
            return;
        }
        $diseases = [
            ['name' => 'Erkältung / grippaler Infekt', 'reporting' => false,
                'wiederzulassung_durch' => 'Eltern',
                'wiederzulassung_wann' => '24 Stunden nach Abklingen der Symptome',
                'aushang_dauer' => 0],
            ['name' => 'Grippe (Influenza)', 'reporting' => true,
                'wiederzulassung_durch' => 'Arzt (kein Attest notwendig)',
                'wiederzulassung_wann' => '48 Stunden nach Abklingen von Fieber',
                'aushang_dauer' => 7],
            ['name' => 'Magen-Darm-Erkrankung', 'reporting' => false,
                'wiederzulassung_durch' => 'Eltern',
                'wiederzulassung_wann' => '48 Stunden nach letztem Erbrechen/Durchfall',
                'aushang_dauer' => 5],
            ['name' => 'Windpocken (Varizellen)', 'reporting' => true,
                'wiederzulassung_durch' => 'Arzt',
                'wiederzulassung_wann' => 'nach vollständiger Verkrustung der Bläschen',
                'aushang_dauer' => 21],
            ['name' => 'Kopfläuse', 'reporting' => true,
                'wiederzulassung_durch' => 'Eltern',
                'wiederzulassung_wann' => 'nach Erstbehandlung und Nachweis der Behandlung',
                'aushang_dauer' => 14],
            ['name' => 'Scharlach', 'reporting' => true,
                'wiederzulassung_durch' => 'Arzt',
                'wiederzulassung_wann' => '24 Stunden nach Beginn der Antibiotika-Therapie',
                'aushang_dauer' => 14],
            ['name' => 'Keuchhusten (Pertussis)', 'reporting' => true,
                'wiederzulassung_durch' => 'Gesundheitsamt',
                'wiederzulassung_wann' => '5 Tage nach Beginn der Antibiotika-Therapie',
                'aushang_dauer' => 20],
        ];
        foreach ($diseases as $d) {
            Disease::firstOrCreate(['name' => $d['name']], $d);
        }
    }

    // =========================================================================
    // 10. Krankmeldungen
    // =========================================================================
    private function createKrankmeldungen(): void
    {
        if (!isset($this->children['paul_koch'])) {
            return;
        }

        // Prüfen ob disease_id-Spalte existiert (ältere Installationen ohne Migration)
        $hasDiseaseId = \Illuminate\Support\Facades\Schema::hasColumn('krankmeldungen', 'disease_id');
        $disease = $hasDiseaseId ? Disease::first() : null;

        $krankmeldung1 = [
            'name' => 'Paul Koch',
            'kommentar' => 'Paul hat Fieber und Halsschmerzen. Wir melden ihn für heute und morgen krank.',
            'ende' => today()->addDays(2),
            'users_id' => $this->users['koch']->id,
        ];
        if ($hasDiseaseId && $disease) {
            $krankmeldung1['disease_id'] = $disease->id;
        }

        // 1 aktive Krankmeldung
        Krankmeldungen::firstOrCreate(
            ['child_id' => $this->children['paul_koch']->id, 'start' => today()],
            $krankmeldung1
        );

        // 1 vergangene Krankmeldung
        if (isset($this->children['lena_mueller'])) {
            $krankmeldung2 = [
                'name' => 'Lena Müller',
                'kommentar' => 'Lena hatte eine Erkältung.',
                'ende' => today()->subDays(12),
                'users_id' => $this->users['mueller']->id,
            ];
            if ($hasDiseaseId && $disease) {
                $krankmeldung2['disease_id'] = $disease->id;
            }

            Krankmeldungen::firstOrCreate(
                ['child_id' => $this->children['lena_mueller']->id, 'start' => today()->subDays(14)],
                $krankmeldung2
            );
        }
    }

    // =========================================================================
    // 11. Schickzeiten
    // =========================================================================
    private function createSchickzeiten(): void
    {
        // Regelmäßige Schickzeiten für einige Kinder
        $schickzeiten = [
            // Lena Müller – Mo bis Fr 13:30
            ['child' => 'lena_mueller', 'parent' => 'mueller', 'weekday' => 1, 'time' => '13:30'],
            ['child' => 'lena_mueller', 'parent' => 'mueller', 'weekday' => 2, 'time' => '13:30'],
            ['child' => 'lena_mueller', 'parent' => 'mueller', 'weekday' => 3, 'time' => '15:00'],
            ['child' => 'lena_mueller', 'parent' => 'mueller', 'weekday' => 4, 'time' => '13:30'],
            ['child' => 'lena_mueller', 'parent' => 'mueller', 'weekday' => 5, 'time' => '12:15'],
            // Tim Bauer – Mi früher
            ['child' => 'tim_bauer', 'parent' => 'bauer', 'weekday' => 3, 'time' => '12:15'],
            ['child' => 'tim_bauer', 'parent' => 'bauer', 'weekday' => 5, 'time' => '12:15'],
        ];

        foreach ($schickzeiten as $sz) {
            if (!isset($this->children[$sz['child']]) || !isset($this->users[$sz['parent']])) {
                continue;
            }
            $child = $this->children[$sz['child']];
            $parent = $this->users[$sz['parent']];
            $exists = DB::table('schickzeiten')
                ->where('child_id', $child->id)
                ->where('weekday', $sz['weekday'])
                ->whereNull('deleted_at')
                ->exists();
            if (!$exists) {
                DB::table('schickzeiten')->insert([
                    'users_id' => $parent->id,
                    'child_name' => $child->first_name . ' ' . $child->last_name,
                    'child_id' => $child->id,
                    'weekday' => $sz['weekday'],
                    'specific_date' => null,
                    'time' => $sz['time'],
                    'type' => 'regular',
                    'changedBy' => $parent->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    // =========================================================================
    // 12. Nachrichten (Posts)
    // =========================================================================
    private function createPosts(User $admin): void
    {
        // Nachrichten-Autor-IDs
        $schulleitungId = $this->users['schulleitung']->id;
        $lehrerinId = $this->users['lehrerin']->id;
        $adminId = $admin->id;

        // Post 1: Willkommen – an alle
        $post1 = Post::firstOrCreate(
            ['header' => 'Willkommen im neuen Schuljahr 2025/26'],
            [
                'news' => '<p>Liebe Eltern und Sorgeberechtigte,</p><p>wir begrüßen Sie herzlich zum neuen Schuljahr am <strong>Evangelischen Schulzentrum Radebeul</strong>! Wir freuen uns auf eine gemeinsame Zeit voller Lernen, Lachen und Wachsen.</p><p>Über dieses Portal erhalten Sie alle wichtigen Informationen zu Ihrem Kind, können Krankmeldungen absenden, Termine einsehen und vieles mehr.</p><p>Bei Fragen steht Ihnen unser Sekretariat gerne zur Verfügung.</p><p>Herzliche Grüße<br>Die Schulleitung</p>',
                'released' => true,
                'author' => $schulleitungId,
                'type' => 'post',
                'reactable' => true,
                'external' => false,
            ]
        );
        $this->attachGroupToPost($post1, 'gesamtelternschaft');

        // Post 2: Schulausflug Klasse 3b – mit Rückmeldung
        $post2 = Post::firstOrCreate(
            ['header' => 'Schulausflug Klasse 3b – Elbsandsteingebirge'],
            [
                'news' => '<p>Liebe Eltern der Klasse 3b,</p><p>wir planen für den <strong>' . Carbon::now()->addWeeks(3)->format('d.m.Y') . '</strong> einen Tagesausflug ins Elbsandsteingebirge zur Bastei. Die Kinder erkunden auf einem kindgerechten Wanderweg die Felsen und lernen die Landschaft kennen.</p><p><strong>Details:</strong></p><ul><li>Abfahrt: 8:00 Uhr am Schulhof</li><li>Rückkehr: ca. 16:30 Uhr</li><li>Kosten: 8,50 € (Bus + Eintritt)</li><li>Bitte festes Schuhwerk und Lunchpaket mitbringen</li></ul><p>Bitte melden Sie Ihr Kind verbindlich an:</p>',
                'released' => true,
                'author' => $lehrerinId,
                'type' => 'post',
                'reactable' => false,
                'external' => false,
            ]
        );
        $this->attachGroupToPost($post2, 'klasse_3b');
        // Rückmeldung für Post 2
        $rueck2 = Rueckmeldungen::firstOrCreate(
            ['post_id' => $post2->id],
            [
                'empfaenger' => $lehrerinId,
                'ende' => Carbon::now()->addWeeks(2),
                'text' => 'Nimmt ihr Kind am Ausflug teil?',
                'pflicht' => true,
                'type' => 'abfrage',
                'commentable' => false,
            ]
        );
        AbfrageOptions::firstOrCreate(['rueckmeldung_id' => $rueck2->id, 'option' => 'Ja, mein Kind nimmt teil'],
            ['type' => 'checkbox', 'required' => false]);
        AbfrageOptions::firstOrCreate(['rueckmeldung_id' => $rueck2->id, 'option' => 'Nein, mein Kind nimmt nicht teil'],
            ['type' => 'checkbox', 'required' => false]);
        AbfrageOptions::firstOrCreate(['rueckmeldung_id' => $rueck2->id, 'option' => 'Ich benötige mehr Informationen'],
            ['type' => 'text', 'required' => false]);
        // Demo-Rückmeldungen

        // Post 3: Elternabend – mit einfacher Rückmeldung
        $post3 = Post::firstOrCreate(
            ['header' => 'Elternabend Klasse 1a – ' . Carbon::now()->addWeeks(2)->format('d.m.Y')],
            [
                'news' => '<p>Liebe Eltern der Klasse 1a,</p><p>wir laden Sie herzlich zum Elternabend am <strong>' . Carbon::now()->addWeeks(2)->format('d.m.Y') . ' um 19:00 Uhr</strong> in den Konferenzraum der Schule ein.</p><p>Auf der Tagesordnung stehen:</p><ul><li>Rückblick auf das erste Schulhalbjahr</li><li>Informationen zum Förderunterricht</li><li>Planung des Sommerfestes</li><li>Verschiedenes</li></ul><p>Bitte melden Sie Ihre Teilnahme bis ' . Carbon::now()->addWeeks(1)->format('d.m.Y') . ' zurück.</p>',
                'released' => false,
                'author' => $this->users['lehrer2']->id,
                'type' => 'post',
                'reactable' => false,
                'external' => false,
            ]
        );
        $this->attachGroupToPost($post3, 'klasse_1a');
        Rueckmeldungen::firstOrCreate(
            ['post_id' => $post3->id],
            [
                'empfaenger' => $this->users['lehrer2']->id,
                'ende' => Carbon::now()->addWeeks(1),
                'text' => 'Ich nehme am Elternabend teil.',
                'pflicht' => false,
                'type' => 'text',
                'commentable' => true,
            ]
        );


        // Post 4: Datenschutz – mit Lesequittung
        $post4 = Post::firstOrCreate(
            ['header' => 'Wichtig: Datenschutzerklärung 2025/26'],
            [
                'news' => '<p>Liebe Eltern,</p><p>wir haben unsere Datenschutzerklärung aktualisiert. Diese regelt, wie wir mit den personenbezogenen Daten Ihrer Kinder und Ihrer Familie umgehen.</p><p>Bitte lesen Sie die aktualisierte Datenschutzerklärung aufmerksam durch. Sie finden diese auch in der Schule zur Einsichtnahme aus.</p><p>Mit der Bestätigung dieser Nachricht bestätigen Sie, die Datenschutzerklärung zur Kenntnis genommen zu haben.</p>',
                'released' => true,
                'author' => $schulleitungId,
                'type' => 'post',
                'reactable' => false,
                'external' => false,
                'read_receipt' => true,
                'read_receipt_deadline' => Carbon::now()->addWeeks(2),
                'archived_at' => Carbon::now()->addWeeks(2),
            ]
        );
        $this->attachGroupToPost($post4, 'gesamtelternschaft');
        // Eine Lesequittung als Demo
        if (isset($this->users['mueller'])) {
            ReadReceipts::firstOrCreate(
                ['post_id' => $post4->id, 'user_id' => $this->users['mueller']->id],
                ['confirmed_at' => now()->subDays(2)]
            );
        }

        // Post 5: Spendenaktion
        $post5 = Post::firstOrCreate(
            ['header' => 'Spendenaktion: Neue Bücher für die Schulbibliothek'],
            [
                'news' => '<p>Liebe Schulgemeinschaft,</p><p>unsere Schulbibliothek benötigt dringend neue Bücher! Im Rahmen unserer Spendenaktion möchten wir bis zum <strong>' . Carbon::now()->addMonths(2)->format('d.m.Y') . '</strong> insgesamt <strong>500 €</strong> sammeln, um neue altersgerechte Bücher für alle Klassenstufen anzuschaffen.</p><p>Spenden können Sie:</p><ul><li>Direkt an das Sekretariat (Bar oder Überweisung)</li><li>Über den Förderverein</li><li>Durch Buchspenden (Absprache mit der Bibliothekarin)</li></ul><p>Jeder Beitrag hilft! Herzlichen Dank für Ihre Unterstützung.</p>',
                'released' => true,
                'author' => $this->users['elternrat']->id,
                'type' => 'post',
                'reactable' => true,
                'external' => false,
            ]
        );
        $this->attachGroupToPost($post5, 'gesamtelternschaft');

        // Post 6: Sommerfest
        $post6 = Post::firstOrCreate(
            ['header' => 'Einladung zum Sommerfest ' . Carbon::now()->year],
            [
                'news' => '<p>Liebe Schulgemeinschaft,</p><p>herzliche Einladung zu unserem diesjährigen <strong>Sommerfest am ' . Carbon::now()->addMonths(3)->format('d.m.Y') . ' ab 14:00 Uhr</strong> auf dem Schulgelände!</p><p>Es erwartet Sie und Ihre Kinder:</p><ul><li>Auftritte der Schulchöre und -kapelle</li><li>Kaffee, Kuchen und Grillen</li><li>Spiele und Aktivitäten für Kinder</li><li>Flohmarkt</li><li>Ausstellung der Kunstprojekte</li></ul><p>Wir freuen uns auf einen gemeinsamen Nachmittag!</p>',
                'released' => true,
                'author' => $schulleitungId,
                'type' => 'post',
                'reactable' => true,
                'external' => false,
            ]
        );
        $this->attachGroupToPost($post6, 'gesamtelternschaft');

        // Post 7: Externe Angebote
        $post7 = Post::firstOrCreate(
            ['header' => 'Musikschule Radebeul – Anmeldung für Herbstkurse'],
            [
                'news' => '<p>Die Musikschule Radebeul bietet ab September neue Kurse für Kinder ab 5 Jahren an:</p><ul><li>Musikalische Früherziehung (5–7 Jahre)</li><li>Blockflöte Anfänger</li><li>Keyboard für Kinder</li><li>Kinderchor</li></ul><p>Anmeldungen sind bis 15. August möglich unter musikschule-radebeul.de</p>',
                'released' => true,
                'author' => $adminId,
                'type' => 'post',
                'reactable' => false,
                'external' => true,
            ]
        );
        $this->attachGroupToPost($post7, 'gesamtelternschaft');

        // Poll zu Post 5
        $poll = Poll::firstOrCreate(
            ['post_id' => $post5->id],
            [
                'poll_name' => 'Welchen Betrag können Sie für die Bücherspende aufbringen?',
                'description' => 'Ihre Angabe hilft uns bei der Planung.',
                'ends' => Carbon::now()->addWeeks(3),
                'author_id' => $this->users['elternrat']->id,
                'max_number' => 1,
            ]
        );
        $opt1 = Poll_Option::firstOrCreate(['poll_id' => $poll->id, 'option' => '5–10 €']);
        $opt2 = Poll_Option::firstOrCreate(['poll_id' => $poll->id, 'option' => '10–20 €']);
        $opt3 = Poll_Option::firstOrCreate(['poll_id' => $poll->id, 'option' => 'mehr als 20 €']);
        $opt4 = Poll_Option::firstOrCreate(['poll_id' => $poll->id, 'option' => 'Ich kann leider nicht spenden']);
        // Demo-Stimmen
        foreach (['mueller' => $opt1->id, 'schulz' => $opt2->id, 'bauer' => $opt2->id] as $pk => $optId) {
            if (isset($this->users[$pk])) {
                Poll_Votes::firstOrCreate([
                    'poll_id' => $poll->id,
                    'author_id' => $this->users[$pk]->id,
                ]);
            }
        }
    }

    private function attachGroupToPost(Post $post, string $groupKey): void
    {
        if (!isset($this->groups[$groupKey])) {
            return;
        }
        $exists = DB::table('group_post')
            ->where('group_id', $this->groups[$groupKey])
            ->where('post_id', $post->id)
            ->exists();
        if (!$exists) {
            DB::table('group_post')->insert([
                'group_id' => $this->groups[$groupKey],
                'post_id' => $post->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    // =========================================================================
    // 13. Termine
    // =========================================================================
    private function createTermine(User $admin): void
    {
        $termine = [
            [
                'terminname' => 'Elternabend Klasse 1a',
                'start' => Carbon::now()->addWeeks(2)->setTime(19, 0),
                'ende' => Carbon::now()->addWeeks(2)->setTime(21, 0),
                'fullDay' => false,
                'public' => false,
                'groups' => ['klasse_1a'],
            ],
            [
                'terminname' => 'Schulausflug Klasse 3b – Bastei',
                'start' => Carbon::now()->addWeeks(3)->setTime(8, 0),
                'ende' => Carbon::now()->addWeeks(3)->setTime(17, 0),
                'fullDay' => false,
                'public' => false,
                'groups' => ['klasse_3b'],
            ],
            [
                'terminname' => 'Sommerfest der Schule',
                'start' => Carbon::now()->addMonths(3)->setTime(14, 0),
                'ende' => Carbon::now()->addMonths(3)->setTime(19, 0),
                'fullDay' => false,
                'public' => true,
                'groups' => ['gesamtelternschaft'],
            ],
            [
                'terminname' => 'Tag der offenen Tür',
                'start' => Carbon::now()->addMonths(1)->startOfDay(),
                'ende' => Carbon::now()->addMonths(1)->setTime(16, 0),
                'fullDay' => false,
                'public' => true,
                'groups' => ['gesamtelternschaft'],
            ],
            [
                'terminname' => 'Pfingstferien Sachsen',
                'start' => Carbon::now()->addWeeks(7)->startOfDay(),
                'ende' => Carbon::now()->addWeeks(8)->endOfDay(),
                'fullDay' => true,
                'public' => true,
                'groups' => ['gesamtelternschaft'],
            ],
            [
                'terminname' => 'Frühjahrskonzert – Schulchor & Schulkapelle',
                'start' => Carbon::now()->addWeeks(5)->setTime(18, 0),
                'ende' => Carbon::now()->addWeeks(5)->setTime(20, 30),
                'fullDay' => false,
                'public' => true,
                'groups' => ['gesamtelternschaft'],
            ],
            [
                'terminname' => 'Elternratssitzung',
                'start' => Carbon::now()->addWeeks(1)->setTime(19, 30),
                'ende' => Carbon::now()->addWeeks(1)->setTime(21, 0),
                'fullDay' => false,
                'public' => false,
                'groups' => ['elternrat_gruppe'],
            ],
            [
                'terminname' => 'Zeugnisausgabe',
                'start' => Carbon::now()->addMonths(4)->setTime(7, 30),
                'ende' => Carbon::now()->addMonths(4)->setTime(11, 0),
                'fullDay' => false,
                'public' => true,
                'groups' => ['gesamtelternschaft'],
            ],
        ];

        foreach ($termine as $terminData) {
            $groups = $terminData['groups'];
            unset($terminData['groups']);

            // Ohne GlobalScope (der nur zukünftige Termine zeigt) direkt per DB::table
            $existing = DB::table('termine')->where('terminname', $terminData['terminname'])->first();
            if (!$existing) {
                $terminId = DB::table('termine')->insertGetId(array_merge($terminData, [
                    'start' => $terminData['start']->toDateTimeString(),
                    'ende' => $terminData['ende']->toDateTimeString(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            } else {
                $terminId = $existing->id;
            }

            foreach ($groups as $groupKey) {
                if (!isset($this->groups[$groupKey])) {
                    continue;
                }
                $exists = DB::table('group_termine')
                    ->where('termin_id', $terminId)
                    ->where('group_id', $this->groups[$groupKey])
                    ->exists();
                if (!$exists) {
                    DB::table('group_termine')->insert([
                        'termin_id' => $terminId,
                        'group_id' => $this->groups[$groupKey],
                    ]);
                }
            }
        }
    }

    // =========================================================================
    // 14. Listen & Elterngespräche
    // =========================================================================
    private function createListen(User $admin): void
    {
        // Liste 1: Elterngespräche Klasse 3b
        $liste1 = Liste::firstOrCreate(
            ['listenname' => 'Elterngespräche Klasse 3b – ' . Carbon::now()->addWeeks(3)->format('d.m.Y')],
            [
                'type' => 'time',
                'comment' => 'Bitte tragen Sie sich in einen freien Termin ein. Die Gespräche finden im Klassenzimmer 3b statt.',
                'besitzer' => $this->users['lehrerin']->id,
                'visible_for_all' => false,
                'active' => true,
                'ende' => Carbon::now()->addWeeks(3)->addDay(),
                'duration' => 15,
                'multiple' => false,
                'make_new_entry' => false,
                'creates_pflichtstunden' => false,
            ]
        );
        // Gruppe zuordnen
        $existsGL = DB::table('group_listen')
            ->where('liste_id', $liste1->id)
            ->where('group_id', $this->groups['klasse_3b'])
            ->exists();
        if (!$existsGL) {
            DB::table('group_listen')->insert([
                'liste_id' => $liste1->id,
                'group_id' => $this->groups['klasse_3b'],
            ]);
        }
        // Zeitslots anlegen
        $startDate = Carbon::now()->addWeeks(3)->setTime(14, 0);
        for ($i = 0; $i < 8; $i++) {
            $slotTime = $startDate->copy()->addMinutes($i * 15);
            listen_termine::firstOrCreate(
                ['listen_id' => $liste1->id, 'termin' => $slotTime],
                ['comment' => '', 'reserviert_fuer' => null, 'duration' => 15]
            );
        }
        $startDate2 = Carbon::now()->addWeeks(3)->addDay()->setTime(14, 0);
        for ($i = 0; $i < 8; $i++) {
            $slotTime = $startDate2->copy()->addMinutes($i * 15);
            $lt = listen_termine::firstOrCreate(
                ['listen_id' => $liste1->id, 'termin' => $slotTime],
                ['comment' => '', 'reserviert_fuer' => null, 'duration' => 15]
            );
        }
        // Reservierungen anlegen
        $slots = listen_termine::where('listen_id', $liste1->id)->get();
        if ($slots->count() >= 2) {
            if (isset($this->users['koch'])) {
                $slots[0]->reserviert_fuer = $this->users['koch']->id;
                $slots[0]->save();
            }
            if (isset($this->users['wagner'])) {
                $slots[1]->reserviert_fuer = $this->users['wagner']->id;
                $slots[1]->save();
            }
        }

        // Liste 2: Schulfest-Helfer (mit Pflichtstunden)
        $liste2 = Liste::firstOrCreate(
            ['listenname' => 'Helfer Sommerfest ' . Carbon::now()->year],
            [
                'type' => 'simple',
                'comment' => 'Wir suchen tatkräftige Helfer für unser Sommerfest! Jede Eintragung zählt als 3 Pflichtstunden.',
                'besitzer' => $this->users['elternrat']->id,
                'visible_for_all' => true,
                'active' => true,
                'ende' => Carbon::now()->addMonths(2),
                'duration' => 0,
                'multiple' => false,
                'make_new_entry' => true,
                'creates_pflichtstunden' => true,
            ]
        );
        foreach (['gesamtelternschaft', 'foerderverein'] as $gk) {
            if (isset($this->groups[$gk])) {
                $existsGL2 = DB::table('group_listen')
                    ->where('liste_id', $liste2->id)
                    ->where('group_id', $this->groups[$gk])
                    ->exists();
                if (!$existsGL2) {
                    DB::table('group_listen')->insert([
                        'liste_id' => $liste2->id,
                        'group_id' => $this->groups[$gk],
                    ]);
                }
            }
        }
        // Eintragungen
        foreach (['mueller', 'bauer', 'schulz'] as $pk) {
            if (isset($this->users[$pk])) {
                Listen_Eintragungen::firstOrCreate(
                    ['listen_id' => $liste2->id, 'user_id' => $this->users[$pk]->id],
                    ['eintragung' => 'Ich helfe gerne beim Sommerfest!', 'created_by' => $this->users[$pk]->id]
                );
            }
        }

        // Liste 3: Reinigungsdienst
        $liste3 = Liste::firstOrCreate(
            ['listenname' => 'Reinigungsdienst Schulgemeinschaftsraum ' . Carbon::now()->format('M Y')],
            [
                'type' => 'simple',
                'comment' => 'Bitte tragen Sie sich in den Reinigungsplan ein. Die Reinigung dauert ca. 1 Stunde und zählt als Pflichtstunde.',
                'besitzer' => $this->users['sekretariat']->id,
                'visible_for_all' => true,
                'active' => true,
                'ende' => Carbon::now()->endOfMonth(),
                'multiple' => false,
                'make_new_entry' => true,
                'creates_pflichtstunden' => true,
            ]
        );
        if (isset($this->groups['gesamtelternschaft'])) {
            $existsGL3 = DB::table('group_listen')
                ->where('liste_id', $liste3->id)
                ->where('group_id', $this->groups['gesamtelternschaft'])
                ->exists();
            if (!$existsGL3) {
                DB::table('group_listen')->insert([
                    'liste_id' => $liste3->id,
                    'group_id' => $this->groups['gesamtelternschaft'],
                ]);
            }
        }
    }

    // =========================================================================
    // 15. Elternrat
    // =========================================================================
    private function createElternrat(User $admin): void
    {
        $elternratUser = $this->users['elternrat'];

        // Diskussionen
        $disc1 = Discussion::firstOrCreate(
            ['header' => 'Verkehrssicherheit vor der Schule'],
            [
                'text' => '<p>Liebe Elternratsmitglieder,</p><p>die Verkehrssituation vor der Schule zur Stoßzeit ist weiterhin problematisch. Insbesondere die Einfahrt zur Schule und das Parken auf dem Gehweg bereiten Sorgen. Ich möchte das Thema auf die nächste Sitzungsordnung setzen und bitte um Rückmeldungen und Ideen.</p>',
                'owner' => $elternratUser->id,
                'sticky' => true,
            ]
        );

        $disc2 = Discussion::firstOrCreate(
            ['header' => 'Vorschlag: Erneuerung des Schulhofs'],
            [
                'text' => '<p>Ich möchte anregen, über eine Neugestaltung des Schulhofs nachzudenken. Der alte Kletterturm ist marode und sollte dringend ersetzt werden. Außerdem wäre eine Grünfläche oder ein kleiner Schulgarten wünschenswert. Hat jemand Erfahrungen mit Förderanträgen oder kennt Sponsoren?</p>',
                'owner' => $this->users['mueller']->id ?? $admin->id,
                'sticky' => false,
            ]
        );

        // Kommentare – direkt per DB
        $this->addComment($disc1, $this->users['mueller'] ?? $admin,
            'Ich stimme vollständig zu. Besonders morgens zwischen 7:30 und 8:00 Uhr ist es sehr gefährlich. Vielleicht könnte man Haltezonen einrichten?');
        $this->addComment($disc1, $this->users['fischer'] ?? $admin,
            'Ich würde vorschlagen, dass wir einen Brief an die Stadt Radebeul schreiben und um eine Verkehrsschau bitten.');
        $this->addComment($disc2, $this->users['bauer'] ?? $admin,
            'Gute Idee! Ich kenne einen lokalen Unternehmer, der vielleicht sponsern würde. Ich frage mal an.');

        // Elternrat-Events
        $event1 = ElternratEvent::firstOrCreate(
            ['title' => 'Elternratssitzung – ' . Carbon::now()->addWeeks(1)->format('d.m.Y')],
            [
                'description' => "Tagesordnung:\n1. Begrüßung\n2. Genehmigung des Protokolls der letzten Sitzung\n3. Verkehrssicherheit vor der Schule\n4. Planung Sommerfest\n5. Pflichtstunden-Abrechnungsstand\n6. Verschiedenes",
                'start_time' => Carbon::now()->addWeeks(1)->setTime(19, 30),
                'end_time' => Carbon::now()->addWeeks(1)->setTime(21, 0),
                'location' => 'Lehrerzimmer, Ev. Schulzentrum Radebeul',
                'created_by' => $elternratUser->id,
                'send_reminder' => true,
                'reminder_hours' => 24,
            ]
        );

        $event2 = ElternratEvent::firstOrCreate(
            ['title' => 'Arbeitstreffen Sommerfest-Vorbereitung'],
            [
                'description' => 'Besprechung der Aufgabenverteilung für das Sommerfest. Bitte alle Ideen mitbringen!',
                'start_time' => Carbon::now()->addWeeks(4)->setTime(18, 0),
                'end_time' => Carbon::now()->addWeeks(4)->setTime(19, 30),
                'location' => 'Schulküche',
                'created_by' => $elternratUser->id,
                'send_reminder' => false,
                'reminder_hours' => 0,
            ]
        );

        // Attendees
        $attendeeData = [
            ['event' => $event1, 'users' => ['elternrat' => 'accepted', 'mueller' => 'accepted', 'fischer' => 'accepted', 'bauer' => 'declined']],
            ['event' => $event2, 'users' => ['elternrat' => 'accepted', 'mueller' => 'maybe', 'schulz' => 'accepted']],
        ];
        foreach ($attendeeData as $ad) {
            foreach ($ad['users'] as $uk => $status) {
                if (isset($this->users[$uk])) {
                    EventAttendee::firstOrCreate(
                        ['event_id' => $ad['event']->id, 'user_id' => $this->users[$uk]->id],
                        ['status' => $status, 'comment' => '']
                    );
                }
            }
        }

        // Aufgaben
        $tasks = [
            [
                'title' => 'Protokoll der März-Sitzung schreiben',
                'description' => 'Das Protokoll der letzten Sitzung muss bis Ende der Woche fertig sein und an alle Mitglieder verschickt werden.',
                'assigned_to' => $this->users['mueller']->id ?? null,
                'created_by' => $elternratUser->id,
                'status' => 'open',
                'priority' => 'high',
                'due_date' => Carbon::now()->addDays(5)->toDateString(),
            ],
            [
                'title' => 'Spendenaufruf für Schulbibliothek gestalten',
                'description' => 'Flyer und Social-Media-Post für die Bücherspende erstellen.',
                'assigned_to' => $this->users['fischer']->id ?? null,
                'created_by' => $elternratUser->id,
                'status' => 'in_progress',
                'priority' => 'medium',
                'due_date' => Carbon::now()->addWeeks(2)->toDateString(),
            ],
            [
                'title' => 'Werbeplakate für Sommerfest drucken',
                'description' => 'DIN-A3-Plakate in 20-facher Ausfertigung bei der Druckerei Radebeul bestellen.',
                'assigned_to' => $this->users['bauer']->id ?? null,
                'created_by' => $elternratUser->id,
                'status' => 'completed',
                'priority' => 'low',
                'due_date' => Carbon::now()->subDays(5)->toDateString(),
                'completed_at' => Carbon::now()->subDays(5),
            ],
            [
                'title' => 'Brief Verkehrssicherheit an Stadtverwaltung',
                'description' => 'Anschreiben an die Stadt Radebeul bezüglich der Verkehrssituation vor der Schule verfassen.',
                'assigned_to' => $elternratUser->id,
                'created_by' => $elternratUser->id,
                'status' => 'open',
                'priority' => 'high',
                'due_date' => Carbon::now()->addWeeks(3)->toDateString(),
            ],
        ];
        foreach ($tasks as $taskData) {
            ElternratTask::firstOrCreate(
                ['title' => $taskData['title'], 'created_by' => $taskData['created_by']],
                $taskData
            );
        }
    }

    private function addComment($model, User $user, string $text): void
    {
        try {
            DB::table('comments')->insert([
                'commentable_id' => $model->id,
                'commentable_type' => get_class($model),
                'user_id' => $user->id,
                'comment' => $text,
                'parent_id' => null,
                'created_at' => now()->subMinutes(rand(10, 1440)),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Ignore if comments table structure differs
        }
    }

    // =========================================================================
    // 16. Vertretungsplan
    // =========================================================================
    private function createVertretungen(): void
    {
        $tomorrow = Carbon::tomorrow();
        $dayAfter = Carbon::now()->addDays(2);

        $vertretungen = [
            [
                'date' => $tomorrow->toDateString(),
                'klasse' => $this->groups['klasse_3b'] ?? null,
                'klasse_kurzform' => '3b',
                'stunde' => '2',
                'altFach' => 'Mathematik',
                'neuFach' => 'entfällt',
                'lehrer' => 'Hoffmann',
                'comment' => 'Frau Hoffmann ist erkrankt.',
            ],
            [
                'date' => $tomorrow->toDateString(),
                'klasse' => $this->groups['klasse_1a'] ?? null,
                'klasse_kurzform' => '1a',
                'stunde' => '4',
                'altFach' => 'Sachunterricht',
                'neuFach' => 'Deutsch',
                'lehrer' => 'Schneider',
                'comment' => 'Herr Schneider übernimmt die Vertretung.',
            ],
            [
                'date' => $dayAfter->toDateString(),
                'klasse' => $this->groups['klasse_2a'] ?? null,
                'klasse_kurzform' => '2a',
                'stunde' => '3',
                'altFach' => 'Sport',
                'neuFach' => 'entfällt',
                'lehrer' => 'Weber',
                'comment' => 'Turnhalle ist wegen Renovierungsarbeiten gesperrt.',
            ],
        ];

        foreach ($vertretungen as $vData) {
            $existing = DB::table('vertretungen')
                ->where('date', $vData['date'])
                ->where('klasse_kurzform', $vData['klasse_kurzform'])
                ->where('stunde', $vData['stunde'])
                ->first();
            if (!$existing) {
                DB::table('vertretungen')->insert(array_merge($vData, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        // Vertretungsplan-News
        VertretungsplanNews::firstOrCreate(
            ['news' => 'Hinweis: Aufgrund einer Lehrerfortbildung finden am ' . $dayAfter->format('d.m.Y') . ' einzelne Stunden nicht statt. Bitte beachten Sie die Vertretungspläne.'],
            [
                'start' => today(),
                'end' => $dayAfter,
            ]
        );
    }

    // =========================================================================
    // 17. Losungen
    // =========================================================================
    private function createLosungen(): void
    {
        $losungen = [
            [
                'date' => today(),
                'Losungsvers' => 'Psalm 46,2',
                'Losungstext' => 'Gott ist unsere Zuversicht und Stärke; eine Hilfe in den großen Nöten, die uns getroffen haben.',
                'Lehrtextvers' => 'Römer 8,38–39',
                'Lehrtext' => 'Denn ich bin gewiss, dass weder Tod noch Leben … uns scheiden kann von der Liebe Gottes, die in Christus Jesus ist, unserem Herrn.',
            ],
            [
                'date' => today()->addDay(),
                'Losungsvers' => 'Jesaja 43,1',
                'Losungstext' => 'Fürchte dich nicht, denn ich habe dich erlöst; ich habe dich bei deinem Namen gerufen; du bist mein.',
                'Lehrtextvers' => 'Johannes 10,14',
                'Lehrtext' => 'Ich bin der gute Hirte und kenne die Meinen, und die Meinen kennen mich.',
            ],
            [
                'date' => today()->addDays(2),
                'Losungsvers' => 'Sprüche 3,5–6',
                'Losungstext' => 'Vertrau auf den HERRN von ganzem Herzen und verlass dich nicht auf deinen Verstand; erkenne ihn auf allen deinen Wegen, so wird er deine Pfade ebnen.',
                'Lehrtextvers' => 'Matthäus 6,33',
                'Lehrtext' => 'Trachtet zuerst nach dem Reich Gottes und nach seiner Gerechtigkeit, so wird euch das alles zufallen.',
            ],
            [
                'date' => today()->addDays(3),
                'Losungsvers' => 'Klagelieder 3,22–23',
                'Losungstext' => 'Die Güte des HERRN hat kein Ende; seine Barmherzigkeit hört nicht auf. Sie ist jeden Morgen neu.',
                'Lehrtextvers' => '2. Korinther 4,16',
                'Lehrtext' => 'Darum werden wir nicht müde; sondern wenn auch unser äußerer Mensch verfällt, so wird doch der innere von Tag zu Tag erneuert.',
            ],
            [
                'date' => today()->addDays(4),
                'Losungsvers' => 'Psalm 23,1',
                'Losungstext' => 'Der HERR ist mein Hirte, mir wird nichts mangeln.',
                'Lehrtextvers' => 'Philipper 4,19',
                'Lehrtext' => 'Mein Gott aber wird all eurem Mangel abhelfen nach seinem Reichtum in der Herrlichkeit in Christus Jesus.',
            ],
            [
                'date' => today()->addDays(5),
                'Losungsvers' => 'Jeremia 29,11',
                'Losungstext' => 'Denn ich weiß wohl, was ich für Gedanken über euch habe, spricht der HERR: Gedanken des Friedens und nicht des Leides, euch eine Zukunft und eine Hoffnung zu geben.',
                'Lehrtextvers' => 'Römer 15,13',
                'Lehrtext' => 'Der Gott der Hoffnung aber erfülle euch mit aller Freude und Frieden im Glauben.',
            ],
            [
                'date' => today()->addDays(6),
                'Losungsvers' => 'Psalm 119,105',
                'Losungstext' => 'Dein Wort ist meines Fußes Leuchte und ein Licht auf meinem Weg.',
                'Lehrtextvers' => 'Johannes 8,12',
                'Lehrtext' => 'Ich bin das Licht der Welt. Wer mir nachfolgt, der wird nicht wandeln in der Finsternis.',
            ],
        ];

        foreach ($losungen as $losung) {
            Losung::firstOrCreate(
                ['date' => $losung['date']->toDateString()],
                $losung
            );
        }
    }

    // =========================================================================
    // 18. Pflichtstunden
    // =========================================================================
    private function createPflichtstunden(User $admin): void
    {
        $liste = Liste::where('listenname', 'like', 'Helfer Sommerfest%')->first();

        $pflichtstundenData = [
            [
                'user' => 'mueller',
                'start' => Carbon::now()->subWeeks(2)->setTime(14, 0),
                'end' => Carbon::now()->subWeeks(2)->setTime(17, 0),
                'description' => 'Mithilfe beim Aufbau der Schulfest-Bühne',
                'bereich' => 'Schulfest',
                'approved' => true,
                'approved_by' => $admin->id,
                'rejected' => false,
            ],
            [
                'user' => 'bauer',
                'start' => Carbon::now()->subWeeks(1)->setTime(9, 0),
                'end' => Carbon::now()->subWeeks(1)->setTime(11, 0),
                'description' => 'Reinigung des Schulgemeinschaftsraums',
                'bereich' => 'Reinigung',
                'approved' => true,
                'approved_by' => $this->users['sekretariat']->id ?? $admin->id,
                'rejected' => false,
            ],
            [
                'user' => 'schulz',
                'start' => Carbon::now()->subDays(3)->setTime(17, 0),
                'end' => Carbon::now()->subDays(3)->setTime(19, 0),
                'description' => 'Teilnahme am Elternabend und Protokollführung',
                'bereich' => 'Elternabend',
                'approved' => false,
                'approved_by' => null,
                'rejected' => false,
            ],
            [
                'user' => 'fischer',
                'start' => Carbon::now()->subWeeks(3)->setTime(8, 0),
                'end' => Carbon::now()->subWeeks(3)->setTime(10, 0),
                'description' => 'Buchsortierung in der Schulbibliothek',
                'bereich' => 'Bibliothek',
                'approved' => false,
                'approved_by' => null,
                'rejected' => false,
            ],
            [
                'user' => 'wagner',
                'start' => Carbon::now()->subMonths(1)->setTime(9, 0),
                'end' => Carbon::now()->subMonths(1)->setTime(12, 0),
                'description' => 'Gartenarbeit auf dem Schulgelände',
                'bereich' => 'Arbeitseinsatz',
                'approved' => false,
                'approved_by' => $admin->id,
                'rejected' => true,
            ],
        ];

        foreach ($pflichtstundenData as $pd) {
            if (!isset($this->users[$pd['user']])) {
                continue;
            }
            $user = $this->users[$pd['user']];
            $existing = Pflichtstunde::withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->where('description', $pd['description'])
                ->first();
            if (!$existing) {
                Pflichtstunde::withoutGlobalScopes()->create([
                    'user_id' => $user->id,
                    'start' => $pd['start'],
                    'end' => $pd['end'],
                    'description' => $pd['description'],
                    'bereich' => $pd['bereich'],
                    'approved' => $pd['approved'],
                    'approved_at' => $pd['approved'] ? now()->subDays(1) : null,
                    'approved_by' => $pd['approved_by'],
                    'rejected' => $pd['rejected'],
                    'rejected_at' => $pd['rejected'] ? now()->subDays(1) : null,
                    'rejected_by' => $pd['rejected'] ? $admin->id : null,
                    'rejection_reason' => $pd['rejected'] ? 'Angabe nicht ausreichend dokumentiert, bitte erneut einreichen.' : null,
                ]);
            }
        }
    }

    // =========================================================================
    // 19. Info-Seiten
    // =========================================================================
    private function createSites(User $admin): void
    {
        // Site direkt per DB::table (hat GlobalScope)
        $existingSite = DB::table('sites')->where('name', 'Schulinformationen')->first();
        if (!$existingSite) {
            $siteId = DB::table('sites')->insertGetId([
                'name' => 'Schulinformationen',
                'author_id' => $admin->id,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $siteId = $existingSite->id;
        }

        // Site zu Gruppe zuordnen
        if (isset($this->groups['gesamtelternschaft'])) {
            $exists = DB::table('site_group')
                ->where('site_id', $siteId)
                ->where('group_id', $this->groups['gesamtelternschaft'])
                ->exists();
            if (!$exists) {
                DB::table('site_group')->insert([
                    'site_id' => $siteId,
                    'group_id' => $this->groups['gesamtelternschaft'],
                ]);
            }
        }

        // Block 1: Begrüßungstext
        $text1 = DB::table('sites_blocks_text')->insertGetId([
            'content' => '<h2>Herzlich willkommen am Evangelischen Schulzentrum Radebeul</h2>
<p>Das Evangelische Schulzentrum Radebeul ist eine staatlich anerkannte Schule in freier Trägerschaft der Evangelisch-Lutherischen Landeskirche Sachsens. Wir bieten einen Bildungsweg von der Grundschule bis zum Gymnasium.</p>
<p>Unser Leitbild: <em>„Bildung als ganzheitlicher Prozess – Kopf, Herz und Hand"</em></p>
<h3>Unsere Leitlinien</h3>
<ul>
<li>Christliche Werte als Fundament</li>
<li>Individuelle Förderung jedes Kindes</li>
<li>Enge Zusammenarbeit zwischen Schule und Elternhaus</li>
<li>Soziales Lernen und Gemeinschaft</li>
</ul>',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $block1Exists = DB::table('site_blocks')
            ->where('site_id', $siteId)
            ->where('block_id', $text1)
            ->where('block_type', 'App\\Model\\SiteBlockText')
            ->exists();
        if (!$block1Exists) {
            DB::table('site_blocks')->insert([
                'site_id' => $siteId,
                'block_id' => $text1,
                'block_type' => 'App\\Model\\SiteBlockText',
                'position' => 1,
                'title' => 'Über unsere Schule',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Block 2: Kontakt und Öffnungszeiten
        $text2 = DB::table('sites_blocks_text')->insertGetId([
            'content' => '<h3>Kontakt & Öffnungszeiten</h3>
<table>
<tr><td><strong>Adresse:</strong></td><td>Friedensstraße 10, 01445 Radebeul</td></tr>
<tr><td><strong>Telefon:</strong></td><td>0351 / 123 456-0</td></tr>
<tr><td><strong>E-Mail:</strong></td><td>sekretariat@ev-schulzentrum-radebeul.de</td></tr>
</table>
<h4>Sekretariat</h4>
<p>Mo, Di, Do, Fr: 7:30–13:00 Uhr<br>Mi: 7:30–12:00 Uhr</p>
<h3>Schulordnung (Auszug)</h3>
<ol>
<li>Wir begegnen einander mit Respekt und Wertschätzung.</li>
<li>Wir gehen sorgsam mit dem Schulgebäude und -eigentum um.</li>
<li>Mobiltelefone sind während des Unterrichts ausgeschaltet.</li>
<li>Wir tragen zur Sauberkeit unserer Schule bei.</li>
</ol>',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $block2Exists = DB::table('site_blocks')
            ->where('site_id', $siteId)
            ->where('block_id', $text2)
            ->where('block_type', 'App\\Model\\SiteBlockText')
            ->exists();
        if (!$block2Exists) {
            DB::table('site_blocks')->insert([
                'site_id' => $siteId,
                'block_id' => $text2,
                'block_type' => 'App\\Model\\SiteBlockText',
                'position' => 2,
                'title' => 'Kontakt & Schulordnung',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    // =========================================================================
    // 20. Schulferien Sachsen
    // =========================================================================
    private function createHolidays(): void
    {
        $year = Carbon::now()->year;
        $holidays = [
            ['name' => 'Winterferien', 'start' => "{$year}-02-17", 'end' => "{$year}-02-28"],
            ['name' => 'Osterferien', 'start' => "{$year}-04-14", 'end' => "{$year}-04-25"],
            ['name' => 'Pfingstferien', 'start' => "{$year}-06-09", 'end' => "{$year}-06-13"],
            ['name' => 'Sommerferien', 'start' => "{$year}-06-28", 'end' => "{$year}-08-08"],
            ['name' => 'Herbstferien', 'start' => "{$year}-10-06", 'end' => "{$year}-10-17"],
            ['name' => 'Weihnachtsferien', 'start' => "{$year}-12-22", 'end' => ($year + 1) . '-01-02'],
        ];

        foreach ($holidays as $h) {
            Holiday::firstOrCreate(
                ['name' => $h['name'], 'year' => $year],
                array_merge($h, ['year' => $year])
            );
        }
    }

    // =========================================================================
    // 21. Arbeitsgemeinschaften
    // =========================================================================
    private function createArbeitsgemeinschaften(User $admin): void
    {
        $managerSchuleitung = $this->users['schulleitung'] ?? $admin;
        $managerLehrer = $this->users['lehrerin'] ?? $admin;

        $ags = [
            [
                'name' => 'Schulchor',
                'description' => 'Der Schulchor trifft sich wöchentlich und bereitet Aufführungen für Schulfeste und Gottesdienste vor.',
                'weekday' => 3, // Mittwoch
                'start_time' => '14:00',
                'end_time' => '15:30',
                'start_date' => Carbon::now()->startOfYear()->toDateString(),
                'end_date' => Carbon::now()->endOfYear()->toDateString(),
                'max_participants' => 30,
                'manager_id' => $managerSchuleitung->id,
                'groups' => ['klasse_3b', 'klasse_4a'],
            ],
            [
                'name' => 'Schulkapelle',
                'description' => 'Instrumentalgruppe für Schüler ab Klasse 3, die ein Instrument spielen.',
                'weekday' => 4, // Donnerstag
                'start_time' => '14:00',
                'end_time' => '15:00',
                'start_date' => Carbon::now()->startOfYear()->toDateString(),
                'end_date' => Carbon::now()->endOfYear()->toDateString(),
                'max_participants' => 20,
                'manager_id' => $managerSchuleitung->id,
                'groups' => ['klasse_3b', 'klasse_4a'],
            ],
            [
                'name' => 'Kreatives Schreiben',
                'description' => 'Wir schreiben Geschichten, Gedichte und kleine Theaterstücke.',
                'weekday' => 2, // Dienstag
                'start_time' => '13:15',
                'end_time' => '14:00',
                'start_date' => Carbon::now()->startOfYear()->toDateString(),
                'end_date' => Carbon::now()->endOfYear()->toDateString(),
                'max_participants' => 15,
                'manager_id' => $managerLehrer->id,
                'groups' => ['klasse_2a', 'klasse_3b'],
            ],
            [
                'name' => 'Natur und Umwelt',
                'description' => 'Erkundung der Natur rund um die Schule, Pflege des Schulgartens.',
                'weekday' => 5, // Freitag
                'start_time' => '13:00',
                'end_time' => '14:00',
                'start_date' => Carbon::now()->startOfYear()->toDateString(),
                'end_date' => Carbon::now()->endOfYear()->toDateString(),
                'max_participants' => 12,
                'manager_id' => $managerLehrer->id,
                'groups' => ['klasse_1a', 'klasse_1b', 'klasse_2a'],
            ],
        ];

        foreach ($ags as $agData) {
            $groupKeys = $agData['groups'];
            unset($agData['groups']);
            $ag = Arbeitsgemeinschaft::firstOrCreate(
                ['name' => $agData['name']],
                $agData
            );

            // Gruppen zuordnen
            foreach ($groupKeys as $gk) {
                if (isset($this->groups[$gk])) {
                    $exists = DB::table('arbeitsgemeinschaften_groups')
                        ->where('ag_id', $ag->id)
                        ->where('group_id', $this->groups[$gk])
                        ->exists();
                    if (!$exists) {
                        DB::table('arbeitsgemeinschaften_groups')->insert([
                            'ag_id' => $ag->id,
                            'group_id' => $this->groups[$gk],
                        ]);
                    }
                }
            }

            // Teilnehmer (einige Kinder)
            $participants = [];
            if ($agData['name'] === 'Schulchor') {
                $participants = ['paul_koch', 'anna_wagner'];
            } elseif ($agData['name'] === 'Kreatives Schreiben') {
                $participants = ['emma_fischer', 'paul_koch'];
            } elseif ($agData['name'] === 'Natur und Umwelt') {
                $participants = ['lena_mueller', 'tim_bauer', 'marie_koch', 'max_schulz'];
            }
            foreach ($participants as $ck) {
                if (isset($this->children[$ck])) {
                    $exists = DB::table('arbeitsgemeinschaften_participants')
                        ->where('ag_id', $ag->id)
                        ->where('participant_id', $this->children[$ck]->id)
                        ->exists();
                    if (!$exists) {
                        DB::table('arbeitsgemeinschaften_participants')->insert([
                            'ag_id' => $ag->id,
                            'participant_id' => $this->children[$ck]->id,
                        ]);
                    }
                }
            }
        }
    }

    // =========================================================================
    // 22. Benachrichtigungen (ohne WebPush)
    // =========================================================================
    private function createNotifications(User $admin): void
    {
        $notifications = [
            [
                'type' => 'info',
                'user_id' => $admin->id,
                'title' => 'Neue Rückmeldung eingegangen',
                'message' => 'Familie Koch hat auf "Schulausflug Klasse 3b – Elbsandsteingebirge" geantwortet.',
                'icon' => 'fas fa-reply',
                'url' => '/posts',
                'read' => false,
                'important' => false,
            ],
            [
                'type' => 'krankmeldung',
                'user_id' => $admin->id,
                'title' => 'Neue Krankmeldung',
                'message' => 'Paul Koch (Klasse 3b) wurde heute krank gemeldet.',
                'icon' => 'fas fa-medkit',
                'url' => '/krankmeldung',
                'read' => false,
                'important' => true,
            ],
            [
                'type' => 'info',
                'user_id' => $admin->id,
                'title' => 'Neuer Kommentar im Elternrat',
                'message' => 'Familie Müller hat einen Kommentar zur Diskussion "Verkehrssicherheit vor der Schule" hinterlassen.',
                'icon' => 'fas fa-comments',
                'url' => '/elternrat',
                'read' => false,
                'important' => false,
            ],
            [
                'type' => 'info',
                'user_id' => $this->users['lehrerin']->id,
                'title' => 'Neue Eintragung in Ihrer Liste',
                'message' => 'Familie Koch hat sich für einen Elterngesprächstermin eingetragen.',
                'icon' => 'fas fa-list',
                'url' => '/listen',
                'read' => false,
                'important' => false,
            ],
        ];

        foreach ($notifications as $nData) {
            // Direkt per DB::table – verhindert WebPush-Trigger
            $exists = DB::table('notifications')
                ->where('user_id', $nData['user_id'])
                ->where('title', $nData['title'])
                ->where('read', false)
                ->exists();
            if (!$exists) {
                DB::table('notifications')->insert(array_merge($nData, [
                    'created_at' => now()->subMinutes(rand(5, 120)),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    // =========================================================================
    // 23. Admin zu allen Gruppen hinzufügen
    // =========================================================================
    private function addAdminToAllGroups(User $admin): void
    {
        $allGroupIds = DB::table('groups')->pluck('id');
        foreach ($allGroupIds as $groupId) {
            $exists = DB::table('group_user')
                ->where('group_id', $groupId)
                ->where('user_id', $admin->id)
                ->exists();
            if (!$exists) {
                DB::table('group_user')->insert([
                    'group_id' => $groupId,
                    'user_id' => $admin->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}


