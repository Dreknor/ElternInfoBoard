<?php

namespace App\Console\Commands\Demo;

use App\Enums\InquiryReviewStatus;
use App\Model\Inquiry;
use App\Model\InquiryAssignment;
use App\Model\InquiryChangeRequest;
use App\Model\InquiryResponse;
use App\Model\InquiryTemplate;
use App\Model\InquiryTemplateVersion;
use App\Model\User;
use App\Services\Elternabfragen\AssignmentAdminService;
use App\Services\Elternabfragen\ChangeRequestService;
use App\Services\Elternabfragen\ExportService;
use App\Services\Elternabfragen\GuardianConfirmation;
use App\Services\Elternabfragen\InquiryService;
use App\Services\Elternabfragen\ReminderService;
use App\Services\Elternabfragen\ResponseService;
use App\Services\Elternabfragen\TargetResolver;
use App\Services\Elternabfragen\TemplateService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

/**
 * Demo-Daten (demo:setup). Wird in App\Console\Commands\DemoSetup eingebunden und
 * nutzt dessen Zustand: $this->users, $this->groups (IDs), $this->children.
 *
 * Elternabfragen entstehen wie im echten Ablauf über die Services (Vorlage anlegen →
 * einreichen → freigeben, Abfrage anlegen → einreichen → freigeben/starten, Antworten,
 * Prüfung, Änderungsmeldungen). Vergangene Schritte werden per Carbon::setTestNow()
 * zeitlich zurückdatiert. Während des Seedings gehen E-Mails an den „array“-Mailer und
 * Benachrichtigungs-Jobs laufen synchron – an die Demo-Adressen wird nichts versendet.
 *
 * Idempotent: Vorlagen und Abfragen werden über ihren Titel erkannt und nicht doppelt angelegt.
 */
trait SeedsElternabfragen
{
    private const ABFRAGE_TPL_FOTO = 'Einverständnis Foto- und Videoaufnahmen';

    private const ABFRAGE_TPL_AUSFLUG = 'Einverständnis Ausflug / Wandertag';

    private const ABFRAGE_TPL_GANZTAG = 'Abfrage Ganztagsbetreuung (Hort)';

    private const ABFRAGE_TPL_SCHWIMMEN = 'Einverständnis Schwimmunterricht';

    private function createElternabfragen(User $admin): void
    {
        $sekretariat = $this->users['sekretariat'] ?? $admin;
        $schulleitung = $this->users['schulleitung'] ?? $admin;
        if ($sekretariat->id === $schulleitung->id) {
            // Vier-Augen-Prinzip: Freigabe durch eine andere Person
            $schulleitung = $admin;
        }

        $this->grantElternabfragenPermissions();

        $mailer = config('mail.default');
        $queue = config('queue.default');
        config(['mail.default' => 'array', 'queue.default' => 'sync']);

        $real = Carbon::now();

        try {
            $tpl = [];
            $tpl['foto'] = $this->abfrageTemplate(self::ABFRAGE_TPL_FOTO, $this->abfrageTemplateFoto(), $sekretariat, $schulleitung, $real->copy()->subMonths(4));
            $tpl['ausflug'] = $this->abfrageTemplate(self::ABFRAGE_TPL_AUSFLUG, $this->abfrageTemplateAusflug(), $sekretariat, $schulleitung, $real->copy()->subMonths(4)->addDay());
            $tpl['ganztag'] = $this->abfrageTemplate(self::ABFRAGE_TPL_GANZTAG, $this->abfrageTemplateGanztag(), $sekretariat, $schulleitung, $real->copy()->subWeeks(5));
            // Eingereicht, aber noch nicht freigegeben (erscheint unter „Freigaben“)
            $this->abfrageTemplate(self::ABFRAGE_TPL_SCHWIMMEN, $this->abfrageTemplateSchwimmen(), $sekretariat, null, $real->copy()->subDays(2));

            $this->abfrageBlock('Fotoabfrage (abgeschlossen)', fn () => $this->abfrageFotoClosed($tpl['foto'], $sekretariat, $schulleitung, $real));
            // Neue Version der Fotovorlage nach der abgeschlossenen Abfrage (Textanpassung)
            $this->abfrageBlock('Fotovorlage Version 2', fn () => $this->abfrageTemplateFotoV2($sekretariat, $schulleitung, $real->copy()->subWeeks(3)));
            $this->abfrageBlock('Ausflug 1a (läuft)', fn () => $this->abfrageAusflugRunning($tpl['ausflug'], $sekretariat, $schulleitung, $real));
            $this->abfrageBlock('Ganztag (läuft)', fn () => $this->abfrageGanztagRunning($tpl['ganztag'], $sekretariat, $schulleitung, $real));
            $this->abfrageBlock('Entwürfe', fn () => $this->abfrageDrafts($tpl['ausflug'], $sekretariat, $real));
        } finally {
            Carbon::setTestNow();
            Auth::forgetUser();
            config(['mail.default' => $mailer, 'queue.default' => $queue]);
        }
    }

    // -------------------------------------------------------------------------
    // Rechte
    // -------------------------------------------------------------------------

    /**
     * Die Modul-Migration vergibt die Rechte nur an Rollen mit „edit settings“ (hier: Administrator).
     * Für die Demo arbeitet das Sekretariat die Abfragen ab, Mitarbeitende lesen freigegebene Antworten.
     */
    private function grantElternabfragenPermissions(): void
    {
        $grants = [
            'Sekretariat' => ['manage elternabfragen', 'view elternabfragen answers', 'export elternabfragen'],
            'Mitarbeiter' => ['read elternabfragen'],
        ];

        foreach ($grants as $roleName => $names) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            $existing = Permission::where('guard_name', 'web')->whereIn('name', $names)->get();
            if ($role && $existing->isNotEmpty()) {
                $role->givePermissionTo($existing);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    // -------------------------------------------------------------------------
    // Vorlagen
    // -------------------------------------------------------------------------

    /**
     * Vorlage anlegen, einreichen und (falls $approver gesetzt) freigeben.
     * Liefert die freigegebene Version (bzw. die eingereichte, wenn keine Freigabe erfolgt).
     */
    private function abfrageTemplate(string $title, array $data, User $creator, ?User $approver, CarbonInterface $at): ?InquiryTemplateVersion
    {
        $existing = InquiryTemplate::where('title', $title)->first();
        if ($existing) {
            return $existing->approvedVersion ?? $existing->latestVersion;
        }

        $service = app(TemplateService::class);

        $this->abfrageAs($creator, $at);
        $version = $service->create(['title' => $title] + $data, $creator);
        $this->abfrageAs($creator, $at->copy()->addMinutes(20));
        $service->submit($version);

        if ($approver === null) {
            return $version->fresh();
        }

        $this->abfrageAs($approver, $at->copy()->addDay()->setTime(9, 15));
        $service->approve($version->fresh(), $approver, (bool) $version->is_sensitive, $version->is_sensitive
            ? 'Gesundheitsangaben nur freiwillig; Zugriff auf Klassenleitung und Sekretariat beschränkt.'
            : null);

        return $version->fresh();
    }

    private function abfrageTemplateFoto(): array
    {
        return [
            'category' => 'Datenschutz',
            'purpose' => 'Einwilligung in die Veröffentlichung von Foto- und Videoaufnahmen Ihres Kindes im Rahmen der Öffentlichkeitsarbeit der Schule (Art. 6 Abs. 1 lit. a DSGVO).',
            'parent_info' => 'Die Einwilligung ist freiwillig und kann jederzeit mit Wirkung für die Zukunft widerrufen werden. Aus einer Nichterteilung entstehen keine Nachteile.',
            'responsible_office' => 'Sekretariat',
            'target_hint' => 'Alle Klassen, jährlich zu Schuljahresbeginn',
            'default_deadline_days' => 14,
            'retention_unlimited' => true,
            'retention_note' => 'Aufbewahrung bis zum Widerruf bzw. bis zum Verlassen der Schule.',
            'is_sensitive' => false,
            'requires_all_guardians' => true,
            'record_relevant' => true,
            'fields' => [
                ['key' => 'hinweis', 'type' => 'info', 'label' => 'Bitte entscheiden Sie für jeden Verwendungszweck einzeln. Namen werden nie zusammen mit Fotos veröffentlicht.'],
                ['key' => 'homepage', 'type' => 'consent', 'label' => 'Fotos auf der Schulhomepage', 'required' => true,
                    'consent_text' => 'Ich willige ein, dass Fotos, auf denen mein Kind zu erkennen ist, auf der Homepage des Ev. Schulzentrums veröffentlicht werden. Die Einwilligung kann jederzeit beim Sekretariat widerrufen werden.'],
                ['key' => 'print', 'type' => 'consent', 'label' => 'Fotos in Jahrbuch, Schaukasten und Gemeindebrief', 'required' => true,
                    'consent_text' => 'Ich willige ein, dass Fotos meines Kindes in gedruckten Veröffentlichungen der Schule (Jahrbuch, Schaukasten, Gemeindebrief) verwendet werden.'],
                ['key' => 'klassenfoto', 'type' => 'yes_no', 'label' => 'Darf Ihr Kind am Klassenfoto des Fotografen teilnehmen?', 'required' => true],
                ['key' => 'anmerkung', 'type' => 'textarea', 'label' => 'Anmerkungen', 'help' => 'z. B. Einschränkungen, die wir beachten sollen', 'max_length' => 1000],
            ],
        ];
    }

    private function abfrageTemplateAusflug(): array
    {
        return [
            'category' => 'Klassenleben',
            'purpose' => 'Einverständnis zur Teilnahme an Ausflügen und Wandertagen sowie Erreichbarkeit der Eltern am Ausflugstag.',
            'parent_info' => 'Die Angaben werden nur von der Klassenleitung und den Begleitpersonen für den Ausflugstag genutzt und danach gelöscht.',
            'responsible_office' => 'Klassenleitung',
            'target_hint' => 'Einzelne Klassen',
            'default_deadline_days' => 10,
            'retention_days' => 60,
            'is_sensitive' => true,
            'requires_all_guardians' => false,
            'record_relevant' => false,
            'fields' => [
                ['key' => 'teilnahme', 'type' => 'consent', 'label' => 'Teilnahme am Ausflug', 'required' => true,
                    'consent_text' => 'Ich bin damit einverstanden, dass mein Kind am genannten Ausflug teilnimmt. Mein Kind wurde darauf hingewiesen, den Anweisungen der Lehrkräfte und Begleitpersonen zu folgen.'],
                ['key' => 'oepnv', 'type' => 'yes_no', 'label' => 'Darf Ihr Kind öffentliche Verkehrsmittel nutzen?', 'required' => true],
                ['key' => 'notfall_telefon', 'type' => 'text', 'label' => 'Telefonnummer für Notfälle am Ausflugstag', 'required' => true, 'format' => 'phone'],
                ['key' => 'gesundheit', 'type' => 'textarea', 'label' => 'Allergien, Unverträglichkeiten oder Medikamente', 'help' => 'Freiwillige Angabe – nur, was die Begleitpersonen wissen müssen.', 'max_length' => 1000],
            ],
        ];
    }

    private function abfrageTemplateGanztag(): array
    {
        return [
            'category' => 'Betreuung',
            'purpose' => 'Planung der Ganztagsbetreuung (Hort) für das kommende Schuljahr: Betreuungsumfang, Tage und Ferienbetreuung.',
            'parent_info' => 'Die Angaben sind eine unverbindliche Bedarfsmeldung. Der Betreuungsvertrag wird gesondert geschlossen.',
            'responsible_office' => 'Sekretariat / Hortleitung',
            'target_hint' => 'Alle Klassen',
            'default_deadline_days' => 21,
            'retention_days' => 365,
            'is_sensitive' => false,
            'requires_all_guardians' => false,
            'record_relevant' => false,
            'fields' => [
                ['key' => 'umfang', 'type' => 'radio', 'label' => 'Betreuungsumfang', 'required' => true,
                    'options' => ['Kein Bedarf', 'Bis 15:00 Uhr', 'Bis 16:30 Uhr', 'Frühhort (ab 6:30 Uhr) und bis 16:30 Uhr']],
                ['key' => 'tage', 'type' => 'checkbox', 'label' => 'An welchen Tagen?', 'help' => 'Nur bei Betreuungsbedarf',
                    'options' => ['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag']],
                ['key' => 'ferien', 'type' => 'select', 'label' => 'Ferienbetreuung gewünscht?', 'required' => true,
                    'options' => ['Ja, in allen Ferien', 'Ja, nur in einzelnen Ferienwochen', 'Nein', 'Noch unklar']],
                ['key' => 'ab', 'type' => 'date', 'label' => 'Betreuung ab'],
                ['key' => 'hinweise', 'type' => 'textarea', 'label' => 'Hinweise für die Hortleitung', 'max_length' => 1000],
            ],
        ];
    }

    private function abfrageTemplateSchwimmen(): array
    {
        return [
            'category' => 'Unterricht',
            'purpose' => 'Einverständnis zum Schwimmunterricht im Hallenbad und Angabe der Schwimmfähigkeit.',
            'responsible_office' => 'Sportlehrkraft',
            'target_hint' => 'Klassen 2 und 3',
            'default_deadline_days' => 14,
            'retention_days' => 180,
            'is_sensitive' => false,
            'fields' => [
                ['key' => 'einverstaendnis', 'type' => 'consent', 'label' => 'Teilnahme am Schwimmunterricht', 'required' => true,
                    'consent_text' => 'Ich bin damit einverstanden, dass mein Kind am Schwimmunterricht im Hallenbad teilnimmt und mit dem Bus dorthin fährt.'],
                ['key' => 'abzeichen', 'type' => 'select', 'label' => 'Vorhandenes Schwimmabzeichen', 'required' => true,
                    'options' => ['Keines', 'Seepferdchen', 'Bronze', 'Silber', 'Gold']],
            ],
        ];
    }

    /** Neue Version der Fotovorlage (angepasster Hinweistext), erneut geprüft und freigegeben. */
    private function abfrageTemplateFotoV2(User $creator, User $approver, CarbonInterface $at): void
    {
        $template = InquiryTemplate::where('title', self::ABFRAGE_TPL_FOTO)->first();
        if (! $template || $template->versions()->count() > 1) {
            return;
        }

        $service = app(TemplateService::class);
        $this->abfrageAs($creator, $at);
        $version = $service->newVersion($template, $creator);
        $data = $this->abfrageTemplateFoto();
        $data['parent_info'] .= ' Klassenfotos werden ausschließlich an die Familien der Klasse weitergegeben.';
        $service->update($version, ['title' => self::ABFRAGE_TPL_FOTO] + $data);
        $service->submit($version->fresh());

        $this->abfrageAs($approver, $at->copy()->addDay()->setTime(10, 0));
        $service->approve($version->fresh(), $approver, false, 'Hinweis zur Weitergabe der Klassenfotos ergänzt.');
    }

    // -------------------------------------------------------------------------
    // Abfragen
    // -------------------------------------------------------------------------

    /**
     * Abgeschlossen: Fotoeinverständnis für die ganze Schule (vor ca. 10 Wochen).
     * Eine Familie hat nicht geantwortet, eine Änderung wurde übernommen, eine ist offen.
     */
    private function abfrageFotoClosed(?InquiryTemplateVersion $version, User $sekretariat, User $schulleitung, Carbon $real): void
    {
        $title = 'Einverständnis Foto- und Videoaufnahmen – Schuljahr '.$this->abfrageSchoolYear($real->copy()->subWeeks(10));
        if (! $version || Inquiry::withTrashed()->where('title', $title)->exists()) {
            return;
        }

        $start = $real->copy()->subWeeks(10)->setTime(8, 0);
        $inquiry = $this->abfrageCreateAndStart($version, $sekretariat, $schulleitung, $start, [
            'title' => $title,
            'intro' => 'Liebe Eltern, zu Beginn des Schuljahres bitten wir Sie, Ihre Entscheidung zu Foto- und Videoaufnahmen für dieses Schuljahr zu bestätigen.',
            'deadline_at' => $start->copy()->addDays(14)->setTime(23, 59),
            'target_type' => Inquiry::TARGET_ALL,
            'responsible_user_id' => $sekretariat->id,
            'notify_mail' => true,
            'notify_push' => true,
            'auto_reminders' => true,
            'staff_visible' => true,
            'allow_change_requests' => true,
            'correction_window_days' => 7,
        ]);

        $answers = [
            'lena_mueller' => ['mueller', 1, 'web', ['homepage' => 'granted', 'print' => 'granted', 'klassenfoto' => 'yes']],
            'max_schulz' => ['schulz', 2, 'app', ['homepage' => 'denied', 'print' => 'granted', 'klassenfoto' => 'yes']],
            'emma_fischer' => ['fischer', 2, 'web', ['homepage' => 'granted', 'print' => 'granted', 'klassenfoto' => 'yes']],
            'paul_koch' => ['koch', 3, 'app', ['homepage' => 'granted', 'print' => 'denied', 'klassenfoto' => 'yes']],
            'marie_koch' => ['koch', 3, 'app', ['homepage' => 'granted', 'print' => 'denied', 'klassenfoto' => 'yes']],
            'tim_bauer' => ['bauer', 9, 'web', ['homepage' => 'granted', 'print' => 'granted', 'klassenfoto' => 'no',
                'anmerkung' => 'Tim möchte beim Klassenfoto nicht dabei sein. Gruppenfotos bei Schulfesten sind in Ordnung.']],
            // anna_wagner: keine Antwort
        ];
        foreach ($answers as $childKey => [$userKey, $day, $channel, $data]) {
            $this->abfrageSubmit($inquiry, $childKey, $userKey, $data, $channel, $start->copy()->addDays($day)->setTime(19, 30 + $day));
        }

        // Prüfung durch das Sekretariat
        $this->abfrageAs($sekretariat, $start->copy()->addDays(15)->setTime(10, 0));
        $admin = app(AssignmentAdminService::class);
        InquiryResponse::where('inquiry_id', $inquiry->id)->where('is_current', true)->with(['assignment.child'])->get()
            ->each(function (InquiryResponse $response) use ($admin, $sekretariat) {
                $isTim = $response->child_id === ($this->children['tim_bauer']->id ?? null);
                $admin->review(
                    $response,
                    $sekretariat,
                    $isTim ? InquiryReviewStatus::Clarification : InquiryReviewStatus::Approved,
                    $isTim ? 'Klassenfoto abgelehnt – Fotografen informieren, Tim beim Termin anderweitig betreuen.' : null,
                );
            });

        // Abschluss nach Fristende
        $this->abfrageAs($sekretariat, $start->copy()->addDays(16)->setTime(8, 0));
        app(InquiryService::class)->close($inquiry->fresh(), 'Rücklauf vollständig geprüft.');

        // Export für die Klassenleitungen
        $this->abfrageAs($sekretariat, $start->copy()->addDays(16)->setTime(8, 30));
        $exports = app(ExportService::class);
        $inquiry = $inquiry->fresh();
        $exports->create($inquiry, $sekretariat, null, array_keys($exports->availableColumns($inquiry)), true, 'xlsx', 'Fotoliste für Klassenleitungen und Fotografen');

        // Übernommene Änderungsmeldung (Familie Schulz, vor drei Wochen)
        $changes = app(ChangeRequestService::class);
        $request = $this->abfrageChangeRequest($inquiry, 'max_schulz', 'schulz',
            ['homepage' => 'granted', 'print' => 'granted', 'klassenfoto' => 'yes'],
            'Nach Rücksprache in der Familie sind wir nun doch mit Fotos auf der Homepage einverstanden.',
            $real->copy()->subWeeks(3)->setTime(20, 10));
        if ($request) {
            $this->abfrageAs($sekretariat, $real->copy()->subWeeks(3)->addDay()->setTime(9, 0));
            $changes->accept($request, $sekretariat, 'Übernommen, Klassenleitung informiert.');
        }

        // Offene Änderungsmeldung (Familie Fischer, vor zwei Tagen) – Widerruf Homepage
        $this->abfrageChangeRequest($inquiry, 'emma_fischer', 'fischer',
            ['homepage' => 'denied', 'print' => 'granted', 'klassenfoto' => 'yes'],
            'Wir möchten die Einwilligung für Fotos auf der Schulhomepage widerrufen. Bitte entfernen Sie auch bereits veröffentlichte Bilder von Emma.',
            $real->copy()->subDays(2)->setTime(21, 5));
    }

    /**
     * Läuft: Ausflug der Klasse 1a. Eine Antwort, ein Entwurf, ein offener Fall mit Erinnerung.
     */
    private function abfrageAusflugRunning(?InquiryTemplateVersion $version, User $sekretariat, User $schulleitung, Carbon $real): void
    {
        $title = 'Ausflug in den Zoo Dresden – Klasse 1a';
        if (! $version || ! isset($this->groups['klasse_1a']) || Inquiry::withTrashed()->where('title', $title)->exists()) {
            return;
        }

        $start = $real->copy()->subDays(4)->setTime(7, 30);
        $inquiry = $this->abfrageCreateAndStart($version, $sekretariat, $schulleitung, $start, [
            'title' => $title,
            'intro' => 'Am '.$real->copy()->addDays(10)->format('d.m.Y').' besuchen wir mit der Klasse 1a den Zoo Dresden. Abfahrt 8:15 Uhr an der Schule, Rückkehr gegen 14:00 Uhr. Bitte geben Sie Ihr Einverständnis bis zum genannten Datum.',
            'deadline_at' => $real->copy()->addDays(6)->setTime(23, 59),
            'target_type' => Inquiry::TARGET_GROUPS,
            'target_group_ids' => [$this->groups['klasse_1a']],
            'responsible_user_id' => ($this->users['lehrer2'] ?? $sekretariat)->id,
            'notify_mail' => true,
            'notify_push' => true,
            'auto_reminders' => true,
            'staff_visible' => true,
            'allow_change_requests' => true,
            'correction_window_days' => 3,
        ]);

        $this->abfrageSubmit($inquiry, 'lena_mueller', 'mueller', [
            'teilnahme' => 'granted', 'oepnv' => 'yes', 'notfall_telefon' => '0351 4567890',
            'gesundheit' => 'Leichte Pollenallergie, Notfallspray ist im Rucksack.',
        ], 'app', $start->copy()->addDay()->setTime(20, 15));

        // Familie Bauer hat begonnen, aber noch nicht abgesendet
        $this->abfrageDraft($inquiry, 'tim_bauer', 'bauer', ['teilnahme' => 'granted', 'oepnv' => 'yes'], $real->copy()->subDay()->setTime(21, 40));

        // Manuelle Erinnerung an die noch offenen Fälle (Marie Koch, Tim Bauer)
        $this->abfrageAs($sekretariat, $real->copy()->subDay()->setTime(9, 0));
        rescue(fn () => app(ReminderService::class)->remindManually($inquiry->fresh(), null, $sekretariat->id), null, false);
    }

    /**
     * Läuft: Ganztagsbetreuung für alle Klassen. Wenige Antworten, eine Korrektur.
     */
    private function abfrageGanztagRunning(?InquiryTemplateVersion $version, User $sekretariat, User $schulleitung, Carbon $real): void
    {
        $nextYear = $this->abfrageSchoolYear($real->copy()->addYear());
        $title = 'Betreuungsbedarf Ganztag/Hort – Schuljahr '.$nextYear;
        if (! $version || Inquiry::withTrashed()->where('title', $title)->exists()) {
            return;
        }

        $start = $real->copy()->subDays(3)->setTime(7, 0);
        $inquiry = $this->abfrageCreateAndStart($version, $sekretariat, $schulleitung, $start, [
            'title' => $title,
            'intro' => 'Für die Personalplanung im Hort benötigen wir frühzeitig Ihren voraussichtlichen Betreuungsbedarf für das Schuljahr '.$nextYear.'. Die Angabe ist noch unverbindlich.',
            'deadline_at' => $real->copy()->addDays(18)->setTime(23, 59),
            'target_type' => Inquiry::TARGET_ALL,
            'responsible_user_id' => $sekretariat->id,
            'notify_mail' => true,
            'notify_push' => true,
            'auto_reminders' => true,
            'staff_visible' => false,
            'allow_change_requests' => true,
            'correction_window_days' => 14,
        ]);

        $schoolStart = Carbon::create($real->month >= 8 ? $real->year + 1 : $real->year, 8, 1)->format('Y-m-d');

        $this->abfrageSubmit($inquiry, 'paul_koch', 'koch', [
            'umfang' => 'Kein Bedarf', 'tage' => [], 'ferien' => 'Nein',
        ], 'app', $start->copy()->addDay()->setTime(18, 5));
        $this->abfrageSubmit($inquiry, 'marie_koch', 'koch', [
            'umfang' => 'Bis 16:30 Uhr', 'tage' => ['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag'], 'ferien' => 'Ja, nur in einzelnen Ferienwochen', 'ab' => $schoolStart,
        ], 'app', $start->copy()->addDay()->setTime(18, 9));
        $this->abfrageSubmit($inquiry, 'emma_fischer', 'fischer', [
            'umfang' => 'Bis 15:00 Uhr', 'tage' => ['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag'], 'ferien' => 'Noch unklar', 'ab' => $schoolStart,
            'hinweise' => 'Emma wird dienstags von den Großeltern abgeholt.',
        ], 'web', $start->copy()->addDays(2)->setTime(7, 45));

        // Korrektur im Korrekturfenster: Marie braucht auch freitags Betreuung
        $this->abfrageSubmit($inquiry, 'marie_koch', 'koch', [
            'umfang' => 'Bis 16:30 Uhr', 'tage' => ['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag'], 'ferien' => 'Ja, nur in einzelnen Ferienwochen', 'ab' => $schoolStart,
        ], 'app', $real->copy()->subHours(5), 'Freitags wird nun doch Betreuung benötigt (neue Arbeitszeiten).');
    }

    /** Entwurf und eine zur Freigabe eingereichte Abfrage. */
    private function abfrageDrafts(?InquiryTemplateVersion $version, User $sekretariat, Carbon $real): void
    {
        if (! $version) {
            return;
        }
        $service = app(InquiryService::class);

        $title = 'Wandertag Sächsische Schweiz – Klasse 3b';
        if (isset($this->groups['klasse_3b']) && ! Inquiry::withTrashed()->where('title', $title)->exists()) {
            $this->abfrageAs($sekretariat, $real->copy()->subDay()->setTime(11, 20));
            $service->create([
                'template_version_id' => $version->id,
                'title' => $title,
                'intro' => 'Wanderung von Rathen zur Bastei. Bitte an festes Schuhwerk, Regenjacke und ausreichend Trinken denken.',
                'starts_at' => $real->copy()->addDays(7)->setTime(7, 0),
                'deadline_at' => $real->copy()->addDays(17)->setTime(23, 59),
                'target_type' => Inquiry::TARGET_GROUPS,
                'target_group_ids' => [$this->groups['klasse_3b']],
                'responsible_user_id' => ($this->users['lehrerin'] ?? $sekretariat)->id,
                'notify_mail' => true,
                'notify_push' => true,
                'auto_reminders' => true,
                'staff_visible' => true,
            ], $sekretariat);
        }

        $title = 'Theaterbesuch im Staatsschauspiel – Klasse 2a';
        if (isset($this->groups['klasse_2a']) && ! Inquiry::withTrashed()->where('title', $title)->exists()) {
            $this->abfrageAs($sekretariat, $real->copy()->subHours(3));
            $inquiry = $service->create([
                'template_version_id' => $version->id,
                'title' => $title,
                'intro' => 'Die Klasse 2a besucht die Vormittagsvorstellung „Pippi Langstrumpf“. Fahrt mit der Straßenbahn.',
                'starts_at' => $real->copy()->addDays(2)->setTime(7, 0),
                'deadline_at' => $real->copy()->addDays(12)->setTime(23, 59),
                'target_type' => Inquiry::TARGET_GROUPS,
                'target_group_ids' => [$this->groups['klasse_2a']],
                'responsible_user_id' => $sekretariat->id,
                'notify_mail' => true,
                'notify_push' => true,
                'auto_reminders' => false,
                'staff_visible' => true,
            ], $sekretariat);
            $service->submit($inquiry);
        }
    }

    // -------------------------------------------------------------------------
    // Hilfen
    // -------------------------------------------------------------------------

    /** Teilschritt in eigener Transaktion (Savepoint): Fehler brechen den Demo-Lauf nicht ab. */
    private function abfrageBlock(string $name, callable $fn): void
    {
        try {
            DB::transaction(fn () => $fn());
        } catch (Throwable $e) {
            $this->warn("    Elternabfragen – {$name} übersprungen: ".$e->getMessage());
        }
    }

    private function abfrageAs(User $user, CarbonInterface $at): void
    {
        Carbon::setTestNow($at);
        Auth::setUser($user);
    }

    private function abfrageSchoolYear(CarbonInterface $date): string
    {
        $start = $date->month >= 8 ? $date->year : $date->year - 1;

        return $start.'/'.substr((string) ($start + 1), -2);
    }

    /** Anlegen → einreichen → freigeben (Vier-Augen-Prinzip); der Start erfolgt mit der Freigabe. */
    private function abfrageCreateAndStart(InquiryTemplateVersion $version, User $creator, User $approver, CarbonInterface $start, array $data): Inquiry
    {
        $service = app(InquiryService::class);

        $this->abfrageAs($creator, $start->copy()->subDays(2)->setTime(10, 0));
        $inquiry = $service->create($data + [
            'template_version_id' => $version->id,
            'starts_at' => $start,
        ], $creator);
        $this->abfrageAs($creator, $start->copy()->subDays(2)->setTime(10, 30));
        $service->submit($inquiry);

        $this->abfrageAs($approver, $start);
        $service->approve($inquiry->fresh(), $approver, 'Zielgruppe und Frist geprüft.');

        return $inquiry->fresh();
    }

    private function abfrageAssignment(Inquiry $inquiry, string $childKey): ?InquiryAssignment
    {
        $child = $this->children[$childKey] ?? null;

        return $child ? InquiryAssignment::with(['inquiry.templateVersion', 'child'])
            ->where('inquiry_id', $inquiry->id)->where('child_id', $child->id)->first() : null;
    }

    /** Sorgeberechtigtes Konto für den Auftrag – bevorzugt die angegebene Familie. */
    private function abfrageGuardian(InquiryAssignment $assignment, string $userKey): ?User
    {
        $guardians = app(TargetResolver::class)->recipients($assignment->child);
        $preferred = $this->users[$userKey] ?? null;

        return ($preferred ? $guardians->firstWhere('id', $preferred->id) : null) ?? $guardians->first();
    }

    private function abfrageSubmit(Inquiry $inquiry, string $childKey, string $userKey, array $answers, string $channel, CarbonInterface $at, ?string $reason = null): void
    {
        $assignment = $this->abfrageAssignment($inquiry, $childKey);
        $user = $assignment ? $this->abfrageGuardian($assignment, $userKey) : null;
        if (! $assignment || ! $user) {
            return;
        }

        $this->abfrageAs($user, $at);
        app(ResponseService::class)->submit($assignment, $user, $answers, [], [
            'channel' => $channel,
            'user_agent' => $channel === 'app' ? 'ElternInfo-App' : 'Mozilla/5.0 (Demo)',
        ], $reason);

        // Gemeinsame Erklärung: weitere Sorgeberechtigte bestätigen am Folgetag
        $assignment->refresh();
        $confirmation = app(GuardianConfirmation::class);
        foreach ($confirmation->pendingUsers($assignment) as $other) {
            $this->abfrageAs($other, $at->copy()->addHours(14));
            $confirmation->confirm($assignment, $other, ['channel' => 'app']);
            $assignment->refresh();
        }
    }

    private function abfrageDraft(Inquiry $inquiry, string $childKey, string $userKey, array $answers, CarbonInterface $at): void
    {
        $assignment = $this->abfrageAssignment($inquiry, $childKey);
        $user = $assignment ? $this->abfrageGuardian($assignment, $userKey) : null;
        if (! $assignment || ! $user) {
            return;
        }

        $this->abfrageAs($user, $at);
        app(ResponseService::class)->saveDraft($assignment, $user, $answers);
    }

    private function abfrageChangeRequest(Inquiry $inquiry, string $childKey, string $userKey, array $answers, string $message, CarbonInterface $at): ?InquiryChangeRequest
    {
        $assignment = $this->abfrageAssignment($inquiry, $childKey);
        $user = $assignment ? $this->abfrageGuardian($assignment, $userKey) : null;
        if (! $assignment || ! $user || ! $assignment->isAnswered()) {
            return null;
        }

        $this->abfrageAs($user, $at);

        return app(ChangeRequestService::class)->submit($assignment->fresh(['inquiry.templateVersion', 'child']), $user, $answers, [], $message, [
            'channel' => 'web',
            'user_agent' => 'Mozilla/5.0 (Demo)',
        ]);
    }
}
