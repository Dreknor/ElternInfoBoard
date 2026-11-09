<?php

namespace App\Console\Commands\Demo;

use App\Model\AbfrageAntworten;
use App\Model\Changelog;
use App\Model\Group;
use App\Model\Holiday;
use App\Model\Link;
use App\Model\Post;
use App\Model\Reinigung;
use App\Model\ReinigungsTask;
use App\Model\User;
use App\Model\UserRueckmeldungen;
use App\Scopes\GetGroupsScope;
use App\Services\Family\FamilyResolver;
use App\Settings\CareSetting;
use App\Settings\ReinigungSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use DevDojo\LaravelReactions\Models\Reaction;
use Illuminate\Support\Facades\DB;

/**
 * Demo-Daten (demo:setup). Wird in App\Console\Commands\DemoSetup eingebunden und
 * nutzt dessen Zustand: $this->users, $this->groups (IDs), $this->children.
 *
 * Alle Methoden sind idempotent (zweiter Lauf ohne --fresh legt nichts doppelt an)
 * und lösen keine Mails, Push-Nachrichten oder Glocken-Benachrichtigungen aus.
 */
trait SeedsInhalte
{
    // =========================================================================
    // Reinigungsplan
    // =========================================================================

    /**
     * Gemeinsamer Plan (Bereich „Gesamt“) für die nächsten Wochen, verteilt wie
     * ReinigungController::autoCreate (je Familie eine Einheit, wenigste Einsätze
     * zuerst, nicht zweimal pro Woche), Ferienwochen ausgelassen – aber ohne
     * ReinigungNotifier (keine Mails/Push).
     */
    private function createReinigung(): void
    {
        $setting = app(ReinigungSetting::class);
        $setting->separate_bereiche = false;      // ein gemeinsamer Plan für die Schule
        $setting->combined_exclude_bereiche = [];
        $setting->skip_holidays = true;           // Ferienwochen beim Befüllen auslassen
        $setting->reminder_enabled = false;       // Demo: keine Erinnerungsmails
        $setting->save();

        $tasks = collect(['Klassenzimmer fegen', 'Fenster putzen', 'Tische und Stühle abwischen'])
            ->map(fn (string $task) => ReinigungsTask::firstOrCreate(['task' => $task]));

        $bemerkungen = [
            'Klassenzimmer fegen' => 'Schlüssel im Sekretariat abholen; Besen stehen im Abstellraum',
            'Fenster putzen' => 'Putzmittel und Abzieher im Hausmeisterraum',
            'Tische und Stühle abwischen' => 'Bitte Stühle danach hochstellen',
        ];

        $parents = collect(['mueller', 'schulz', 'fischer', 'koch', 'bauer', 'wagner'])
            ->map(fn (string $key) => $this->users[$key] ?? null)
            ->filter()
            ->keyBy('id');
        if ($parents->isEmpty()) {
            return;
        }

        $units = app(FamilyResolver::class)->familyUnits($parents->values())->values();
        $runCounts = $units->mapWithKeys(fn ($unit) => [$unit->key => 0])->all();

        // Bereits vorhandene Einsätze (zweiter Lauf) für die Fairness mitzählen
        foreach ($units as $unit) {
            $runCounts[$unit->key] = Reinigung::query()->whereIn('users_id', $unit->userIds)->count();
        }

        $bundesland = (new CareSetting)->bundesland;
        $week = Carbon::now()->startOfWeek()->startOfDay();

        for ($i = 0; $i < 7; $i++, $week->addWeek()) {
            if ($this->isHolidayWeek($week, $bundesland)) {
                continue;
            }

            $assignedThisWeek = [];
            foreach ($tasks as $task) {
                $existing = Reinigung::query()
                    ->where('bereich', Reinigung::BEREICH_GESAMT)
                    ->where('aufgabe', $task->task)
                    ->whereDate('datum', $week->toDateString())
                    ->first();
                if ($existing) {
                    $unit = $units->first(fn ($u) => in_array($existing->users_id, $u->userIds));
                    if ($unit) {
                        $assignedThisWeek[] = $unit->key;
                    }

                    continue;
                }

                $unit = $units->reject(fn ($u) => in_array($u->key, $assignedThisWeek, true))
                    ->sortBy(fn ($u) => $runCounts[$u->key])
                    ->first()
                    ?? $units->sortBy(fn ($u) => $runCounts[$u->key])->first();

                $userId = $parents->only($unit->userIds)->keys()->first() ?? $unit->userIds[0];

                Reinigung::create([
                    'bereich' => Reinigung::BEREICH_GESAMT,
                    'aufgabe' => $task->task,
                    'datum' => $week->toDateString(),
                    'users_id' => $userId,
                    'bemerkung' => $bemerkungen[$task->task] ?? null,
                ]);

                $runCounts[$unit->key]++;
                $assignedThisWeek[] = $unit->key;
            }
        }
    }

    /**
     * Ferienwoche wie HolidayService::holidayForWeek (mind. 3 Schultage in Ferien),
     * aber nur aus der Datenbank – ohne Abruf der Ferien-API.
     */
    private function isHolidayWeek(Carbon $monday, string $bundesland): bool
    {
        $friday = $monday->copy()->addDays(4);
        $holidays = Holiday::query()
            ->where('bundesland', $bundesland)
            ->whereDate('start', '<=', $friday->toDateString())
            ->whereDate('end', '>=', $monday->toDateString())
            ->get();

        $days = 0;
        for ($d = $monday->copy(); $d->lte($friday); $d->addDay()) {
            if ($holidays->contains(fn (Holiday $h) => $d->between($h->start->copy()->startOfDay(), $h->end->copy()->endOfDay()))) {
                $days++;
            }
        }

        return $days >= 3;
    }

    // =========================================================================
    // Links
    // =========================================================================

    private function createLinks(User $admin): void
    {
        $links = [
            [
                'title' => 'Homepage der Schule',
                'url' => 'https://www.ev-schulzentrum-demo.de',
                'description' => 'Aktuelles, Konzept und Ansprechpartner des Ev. Schulzentrums.',
                'category' => 'Schule',
                'icon' => 'fas fa-school',
                'pinned' => true,
                'groups' => null,
            ],
            [
                'title' => 'Mensa-Speiseplan',
                'url' => 'https://mensa.ev-schulzentrum-demo.de/speiseplan',
                'description' => 'Wochenplan und Essensbestellung des Caterers.',
                'category' => 'Schulalltag',
                'icon' => 'fas fa-utensils',
                'pinned' => true,
                'groups' => null,
            ],
            [
                'title' => 'Ferienkalender Sachsen',
                'url' => 'https://www.schule.sachsen.de/ferien',
                'description' => 'Schulferien und bewegliche Ferientage im Überblick.',
                'category' => 'Schulalltag',
                'icon' => 'fas fa-calendar-alt',
                'pinned' => false,
                'groups' => null,
            ],
            [
                'title' => 'Termine im ElternInfoBoard',
                'url' => '/termine',
                'description' => 'Alle Schultermine, auch zum Abonnieren im eigenen Kalender.',
                'category' => 'Schulalltag',
                'icon' => 'fas fa-calendar-alt',
                'pinned' => false,
                'groups' => null,
            ],
            [
                'title' => 'Förderverein: Mitglied werden',
                'url' => 'https://foerderverein.ev-schulzentrum-demo.de/mitglied-werden',
                'description' => 'Beitrittserklärung und Projekte des Fördervereins.',
                'category' => 'Gemeinschaft',
                'icon' => 'fas fa-hands-helping',
                'pinned' => false,
                'groups' => ['foerderverein', 'gesamtelternschaft'],
            ],
            [
                'title' => 'Hort-Ferienangebote',
                'url' => 'https://www.ev-schulzentrum-demo.de/hort/ferienangebote',
                'description' => 'Programm und Anmeldung für die Ferienbetreuung.',
                'category' => 'Betreuung',
                'icon' => 'fas fa-users',
                'pinned' => false,
                'groups' => ['hort'],
            ],
            [
                'title' => 'Lernplattform Klassenstufe 3/4',
                'url' => 'https://lernen.ev-schulzentrum-demo.de',
                'description' => 'Übungen und Wochenpläne für die Klassen 3b und 4a.',
                'category' => 'Lernen',
                'icon' => 'fas fa-laptop',
                'pinned' => false,
                'groups' => ['klasse_3b', 'klasse_4a'],
            ],
            [
                'title' => 'Sekretariat anrufen',
                'url' => 'tel:+493511234567',
                'description' => 'Mo–Fr 7:30–14:00 Uhr',
                'category' => 'Schule',
                'icon' => 'fas fa-phone',
                'pinned' => false,
                'groups' => null,
            ],
        ];

        foreach ($links as $position => $data) {
            $groupKeys = $data['groups'];
            unset($data['groups']);

            $link = Link::updateOrCreate(
                ['title' => $data['title']],
                array_merge($data, [
                    'for_all' => $groupKeys === null,
                    'is_active' => true,
                    'position' => $position,
                    'created_by' => $admin->id,
                    'updated_by' => $admin->id,
                ])
            );

            $groupIds = collect($groupKeys ?? [])
                ->map(fn (string $key) => $this->groups[$key] ?? null)
                ->filter()
                ->values()
                ->all();
            $link->groups()->sync($groupIds);
        }
    }

    // =========================================================================
    // Changelog
    // =========================================================================

    /**
     * Ohne notify() und ohne changeSettings (sonst würden alle Nutzer beim
     * nächsten Aufruf auf die Einstellungen umgeleitet).
     */
    private function createChangelog(User $admin): void
    {
        $entries = [
            [
                'header' => 'Neu im ElternInfoBoard: Reinigungsplan im Kalender',
                'text' => '<p>Ihre Reinigungsdienste erscheinen jetzt automatisch in Ihrer Terminübersicht und im abonnierten Kalender.</p><ul><li>Ferienwochen werden im Plan markiert und nicht belegt.</li><li>Die Einteilung erfolgt je Familie, nicht je Elternteil.</li></ul>',
                'date' => Carbon::now()->subWeeks(5),
            ],
            [
                'header' => 'Neu im ElternInfoBoard: Links',
                'text' => '<p>Unter <strong>Links</strong> finden Sie wichtige Seiten auf einen Blick – zum Beispiel den Speiseplan der Mensa, den Ferienkalender und den Förderverein.</p><p>Welche Links Sie sehen, hängt von Ihren Gruppen ab.</p>',
                'date' => Carbon::now()->subWeeks(2),
            ],
            [
                'header' => 'Neu im ElternInfoBoard: Reaktionen und Bilder in Nachrichten',
                'text' => '<p>Sie können auf Nachrichten jetzt mit einem Symbol reagieren. Bilder in Nachrichten lassen sich antippen und vergrößern.</p>',
                'date' => Carbon::now()->subDays(3),
            ],
        ];

        foreach ($entries as $entry) {
            $changelog = Changelog::firstOrNew(['header' => $entry['header']]);
            if ($changelog->exists) {
                continue;
            }
            $changelog->text = $entry['text'];
            $changelog->changeSettings = false;
            $changelog->created_at = $entry['date'];
            $changelog->updated_at = $entry['date'];
            $changelog->save();
        }
    }

    // =========================================================================
    // Nachrichten: Reaktionen, Antworten, Archiv, Bilder
    // =========================================================================

    private function createNachrichtenExtras(User $admin): void
    {
        $this->createDemoReactions();
        $this->createDemoAbfrageAntworten();
        $this->createDemoArchivPosts($admin);
        $this->createDemoPostImages();
    }

    private function createDemoReactions(): void
    {
        // Reaktionstypen legt der ReactionsSeeder bei --seed an; ohne --fresh sicherstellen.
        $types = collect(['like', 'happy', 'sad', 'love', 'haha', 'wow'])
            ->mapWithKeys(fn (string $name) => [$name => Reaction::unguarded(fn () => Reaction::firstOrCreate(['name' => $name]))]);

        $plan = [
            'Willkommen im neuen Schuljahr 2025/26' => [
                'mueller' => 'like', 'schulz' => 'love', 'fischer' => 'like', 'koch' => 'happy', 'wagner' => 'like',
            ],
            'Spendenaktion: Neue Bücher für die Schulbibliothek' => [
                'mueller' => 'love', 'bauer' => 'like', 'elternrat' => 'love',
            ],
            'Einladung zum Sommerfest '.Carbon::now()->year => [
                'mueller' => 'happy', 'schulz' => 'love', 'fischer' => 'wow', 'koch' => 'love', 'bauer' => 'happy', 'wagner' => 'haha',
            ],
        ];

        foreach ($plan as $header => $reactions) {
            $post = Post::where('header', $header)->first();
            if (! $post) {
                continue;
            }
            foreach ($reactions as $userKey => $name) {
                $user = $this->users[$userKey] ?? null;
                if (! $user || $post->reacted($user)) {
                    continue; // je Nutzer höchstens eine Reaktion (wie reactTo)
                }
                $post->reactions()->attach($types[$name]->id, [
                    'responder_id' => $user->id,
                    'responder_type' => get_class($user),
                ]);
            }
        }
    }

    /**
     * Antworten auf die Abfrage „Schulausflug Klasse 3b“ (je Kind, wie
     * FeedbackService::storeAbfrage), ohne Mail an die Empfängerin.
     */
    private function createDemoAbfrageAntworten(): void
    {
        $post = Post::where('header', 'Schulausflug Klasse 3b – Elbsandsteingebirge')->first();
        $rueckmeldung = $post?->rueckmeldung;
        if (! $rueckmeldung || $rueckmeldung->type !== 'abfrage') {
            return;
        }
        $options = $rueckmeldung->options()->get()->keyBy('option');

        $antworten = [
            ['koch', 'paul_koch', [
                'Ja, mein Kind nimmt teil' => '1',
            ], 2],
            ['wagner', 'anna_wagner', [
                'Ja, mein Kind nimmt teil' => '1',
                'Ich benötige mehr Informationen' => 'Gibt es unterwegs eine Möglichkeit, Getränke zu kaufen?',
            ], 1],
        ];

        foreach ($antworten as [$userKey, $childKey, $answers, $daysAgo]) {
            $user = $this->users[$userKey] ?? null;
            $child = $this->children[$childKey] ?? null;
            if (! $user) {
                continue;
            }
            $childId = $rueckmeldung->scope === 'child' ? $child?->id : null;

            $exists = UserRueckmeldungen::query()
                ->where('post_id', $post->id)
                ->where('users_id', $user->id)
                ->where('child_id', $childId)
                ->exists();
            if ($exists) {
                continue;
            }

            $date = Carbon::now()->subDays($daysAgo);
            $response = new UserRueckmeldungen([
                'post_id' => $post->id,
                'users_id' => $user->id,
                'child_id' => $childId,
                'text' => '',
                'rueckmeldung_number' => 1,
            ]);
            $response->created_at = $date;
            $response->updated_at = $date;
            $response->save();

            foreach ($answers as $optionText => $value) {
                $option = $options->get($optionText);
                if (! $option) {
                    continue;
                }
                AbfrageAntworten::create([
                    'rueckmeldung_id' => $response->id,
                    'user_id' => $user->id,
                    'child_id' => $childId,
                    'option_id' => $option->id,
                    'answer' => $value,
                ]);
            }
        }
    }

    private function createDemoArchivPosts(User $admin): void
    {
        $posts = [
            [
                'header' => 'Rückblick: Einschulungsfeier '.Carbon::now()->year,
                'news' => '<p>Liebe Eltern,</p><p>vielen Dank an alle Familien, die bei der Einschulungsfeier geholfen haben! Kuchenbuffet, Aufbau und Abbau haben wunderbar geklappt.</p><p>Herzliche Grüße<br>Die Schulleitung</p>',
                'author' => $this->users['schulleitung']->id ?? $admin->id,
                'group' => 'gesamtelternschaft',
                'created' => Carbon::now()->subWeeks(10),
                'archiv_ab' => Carbon::now()->subWeeks(8),
            ],
            [
                'header' => 'Herbstferien: Fundsachen bitte abholen',
                'news' => '<p>Liebe Eltern,</p><p>vor den Herbstferien haben sich viele Jacken, Mützen und Trinkflaschen angesammelt. Sie liegen bis Freitag im Foyer aus. Danach geben wir sie an die Kleiderkammer.</p>',
                'author' => $this->users['sekretariat']->id ?? $admin->id,
                'group' => 'gesamtelternschaft',
                'created' => Carbon::now()->subWeeks(6),
                'archiv_ab' => Carbon::now()->subWeeks(5),
            ],
            [
                'header' => 'Klasse 3b: Lesenacht in der Schulbibliothek',
                'news' => '<p>Liebe Eltern der Klasse 3b,</p><p>unsere Lesenacht war ein voller Erfolg – danke für die Unterstützung beim Frühstück! Die Fotos zeigen wir beim nächsten Elternabend.</p>',
                'author' => $this->users['lehrerin']->id ?? $admin->id,
                'group' => 'klasse_3b',
                'created' => Carbon::now()->subWeeks(2),
                'archiv_ab' => Carbon::now()->subDays(4),
            ],
        ];

        foreach ($posts as $data) {
            $post = Post::firstWhere('header', $data['header']);
            if (! $post) {
                $post = new Post([
                    'header' => $data['header'],
                    'news' => $data['news'],
                    'released' => true,
                    'author' => $data['author'],
                    'type' => 'post',
                    'reactable' => true,
                    'external' => false,
                    'archiv_ab' => $data['archiv_ab'],
                ]);
                $post->created_at = $data['created'];
                $post->updated_at = $data['created'];
                $post->save();
            }
            $this->attachGroupToPost($post, $data['group']);
        }

        // Archivansicht im Web zeigt Eltern nur Beiträge, die nach ihrer Registrierung
        // archiviert wurden (archiv_ab > users.created_at). Demo-Eltern daher auf den
        // Schuljahresbeginn zurückdatieren.
        $schuljahresbeginn = Carbon::now()->subWeeks(12)->startOfDay();
        foreach (['elternrat', 'mueller', 'schulz', 'fischer', 'koch', 'bauer', 'wagner'] as $key) {
            $user = $this->users[$key] ?? null;
            if ($user && $user->created_at?->greaterThan($schuljahresbeginn)) {
                DB::table('users')->where('id', $user->id)->update(['created_at' => $schuljahresbeginn]);
            }
        }
    }

    /**
     * Bilder per GD (keine Downloads): Titelbild am Sommerfest, Galeriebild an der
     * Begrüßung. Collections wie NachrichtenController: header / images.
     */
    private function createDemoPostImages(): void
    {
        $images = [
            ['header' => 'Einladung zum Sommerfest '.Carbon::now()->year, 'collection' => 'header',
                'file' => 'sommerfest.jpg', 'name' => 'Sommerfest', 'text' => 'Sommerfest', 'color' => [245, 158, 11]],
            ['header' => 'Willkommen im neuen Schuljahr 2025/26', 'collection' => 'images',
                'file' => 'schulhof.jpg', 'name' => 'Schulhof', 'text' => 'Willkommen!', 'color' => [37, 99, 235]],
            ['header' => 'Klasse 3b: Lesenacht in der Schulbibliothek', 'collection' => 'images',
                'file' => 'lesenacht.jpg', 'name' => 'Lesenacht', 'text' => 'Lesenacht 3b', 'color' => [124, 58, 237]],
        ];

        foreach ($images as $img) {
            $post = Post::where('header', $img['header'])->first();
            if (! $post || $post->getMedia($img['collection'])->contains('file_name', $img['file'])) {
                continue;
            }
            $jpeg = $this->demoImage($img['text'], $img['color']);
            if ($jpeg === null) {
                continue;
            }
            $post->addMediaFromString($jpeg)
                ->usingFileName($img['file'])
                ->usingName($img['name'])
                ->toMediaCollection($img['collection']);
        }
    }

    /** Einfarbige Fläche mit Text als JPEG (1200×630). */
    private function demoImage(string $text, array $rgb): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }
        $w = 1200;
        $h = 630;
        $im = imagecreatetruecolor($w, $h);
        imagefill($im, 0, 0, imagecolorallocate($im, ...$rgb));
        // dezente Kreise als Muster
        $light = imagecolorallocatealpha($im, 255, 255, 255, 100);
        imagefilledellipse($im, (int) ($w * 0.85), (int) ($h * 0.2), 420, 420, $light);
        imagefilledellipse($im, (int) ($w * 0.1), (int) ($h * 0.9), 360, 360, $light);
        $white = imagecolorallocate($im, 255, 255, 255);

        $font = base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans-Bold.ttf');
        if (function_exists('imagettftext') && is_file($font)) {
            $size = 72;
            $box = imagettfbbox($size, 0, $font, $text);
            $x = (int) (($w - ($box[2] - $box[0])) / 2);
            $y = (int) (($h + ($box[1] - $box[7])) / 2);
            imagettftext($im, $size, 0, $x, $y, $white, $font, $text);
        } else {
            $x = (int) (($w - imagefontwidth(5) * strlen($text)) / 2);
            imagestring($im, 5, $x, (int) ($h / 2), $text, $white);
        }

        ob_start();
        imagejpeg($im, null, 85);
        $data = ob_get_clean();
        imagedestroy($im);

        return $data ?: null;
    }

    // =========================================================================
    // Dateien (Modul „Dateien“: Media an Gruppen, wie FileController::store)
    // =========================================================================

    private function createDateien(User $admin): void
    {
        $dateien = [
            [
                'file' => 'Elternbrief_Herbst.pdf',
                'name' => 'Elternbrief Herbst',
                'groups' => ['gesamtelternschaft'],
                'content' => fn () => $this->demoPdf(
                    'Elternbrief Herbst '.Carbon::now()->year,
                    '<p>Liebe Eltern,</p><p>der Herbst bringt wieder viele gemeinsame Vorhaben: Laternenumzug, Elternabende und die Vorbereitung auf die Adventszeit. Die Termine finden Sie im ElternInfoBoard.</p><p>Bitte denken Sie an wetterfeste Kleidung und Wechselsachen für Ihr Kind.</p><p>Herzliche Grüße<br>Die Schulleitung</p>'
                ),
            ],
            [
                'file' => 'Hausordnung.pdf',
                'name' => 'Hausordnung',
                'groups' => ['gesamtelternschaft'],
                'content' => fn () => $this->demoPdf(
                    'Hausordnung',
                    '<ol><li>Wir gehen freundlich und respektvoll miteinander um.</li><li>Das Schulgelände wird ab 7:15 Uhr geöffnet.</li><li>Fahrräder werden auf dem Schulhof geschoben.</li><li>Handys bleiben während der Unterrichtszeit ausgeschaltet im Ranzen.</li></ol>'
                ),
            ],
            [
                'file' => 'Materialliste_Klasse_1.txt',
                'name' => 'Materialliste Klasse 1',
                'groups' => ['klasse_1a', 'klasse_1b'],
                'content' => fn () => "Materialliste Klasse 1\n======================\n\n- 2 Bleistifte (HB), Radiergummi, Anspitzer\n- Buntstifte (12 Farben)\n- Schere, Klebestift\n- 3 Hefte Lineatur 1, 2 Rechenhefte\n- Turnbeutel mit Hallenschuhen\n\nBitte alles mit Namen beschriften.\n",
            ],
        ];

        foreach ($dateien as $datei) {
            $content = null;
            foreach ($datei['groups'] as $groupKey) {
                $group = isset($this->groups[$groupKey])
                    ? Group::withoutGlobalScope(GetGroupsScope::class)->find($this->groups[$groupKey])
                    : null;
                if (! $group || $group->getMedia()->contains('file_name', $datei['file'])) {
                    continue;
                }
                $content ??= ($datei['content'])();
                $group->addMediaFromString($content)
                    ->usingFileName($datei['file'])
                    ->usingName($datei['name'])
                    ->toMediaCollection();
            }
        }
    }

    private function demoPdf(string $title, string $body): string
    {
        $html = '<!doctype html><html lang="de"><head><meta charset="utf-8"><style>'
            .'body{font-family:Helvetica,sans-serif;font-size:12pt;color:#222;margin:2cm}'
            .'h1{font-size:18pt;color:#1d4ed8;border-bottom:2px solid #1d4ed8;padding-bottom:6px}'
            .'.kopf{font-size:9pt;color:#666;margin-bottom:24px}</style></head><body>'
            .'<div class="kopf">Ev. Schulzentrum Radebeul (Demo) · '.Carbon::now()->format('d.m.Y').'</div>'
            .'<h1>'.e($title).'</h1>'.$body.'</body></html>';

        return Pdf::loadHTML($html)->setPaper('a4')->output();
    }
}
