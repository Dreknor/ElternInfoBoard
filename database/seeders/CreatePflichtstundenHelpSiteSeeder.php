<?php

namespace Database\Seeders;

use App\Model\Site;
use App\Model\SiteBlock;
use App\Model\SiteBlockText;
use App\Model\User;
use Illuminate\Database\Seeder;

class CreatePflichtstundenHelpSiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Finde oder erstelle Admin-User
        $admin = User::whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->first();

        if (!$admin) {
            $admin = User::first();
        }

        if (!$admin) {
            return; // Kein User gefunden
        }

        // Prüfe ob Site bereits existiert
        $site = Site::where('name', 'Pflichtstunden Hilfe')->first();

        if ($site) {
            // Lösche alte Blöcke
            $site->blocks()->forceDelete();
        } else {
            // Erstelle neue Site
            $site = Site::create([
                'name' => 'Pflichtstunden Hilfe',
                'author_id' => $admin->id,
                'is_active' => true,
            ]);
        }

        // Block 1: Einleitung
        $intro = SiteBlockText::create([
            'content' => <<<'HTML'
<div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-l-4 border-blue-600 p-6 rounded-lg mb-6">
    <h2 class="text-2xl font-bold text-blue-900 mb-3">
        <i class="fas fa-question-circle mr-2"></i>
        Was sind Pflichtstunden?
    </h2>
    <p class="text-gray-700 leading-relaxed mb-3">
        Pflichtstunden sind Freiwilligenstunden, die Eltern (Sorgeberechtigte) erbringen, um die Schule oder Einrichtung zu unterstützen.
        Dies ist eine Gemeinschaftsaufgabe, bei der jede Familie einen Beitrag leistet.
    </p>
    <p class="text-gray-700 leading-relaxed">
        <strong>Tipp:</strong> Sie und Ihr Ehepartner/Ihre Partnerin werden als <strong>eine Familie</strong> gezählt.
        Die Stunden werden zusammengezählt.
    </p>
</div>
HTML
        ]);

        SiteBlock::create([
            'site_id' => $site->id,
            'block_id' => $intro->id,
            'block_type' => SiteBlockText::class,
            'position' => 1,
            'title' => null,
        ]);

        // Block 2: Wie erfasse ich Stunden?
        $erfassung = SiteBlockText::create([
            'content' => <<<'HTML'
<div class="space-y-4">
    <h2 class="text-2xl font-bold text-gray-900 mb-4">
        <i class="fas fa-plus-circle text-green-600 mr-2"></i>
        Schritt-für-Schritt: Wie erfasse ich Pflichtstunden?
    </h2>

    <div class="bg-white border-l-4 border-green-500 rounded-lg p-6 shadow-sm">
        <div class="flex gap-4">
            <div class="flex-shrink-0">
                <div class="flex items-center justify-center h-12 w-12 rounded-md bg-green-100">
                    <span class="text-xl font-bold text-green-600">1</span>
                </div>
            </div>
            <div class="flex-grow">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Navigieren Sie zum Pflichtstunden-Bereich</h3>
                <p class="text-gray-600">Klicken Sie im Menü auf <strong>"Pflichtstunden"</strong> oder folgen Sie dem Link in Ihrem Dashboard.</p>
            </div>
        </div>
    </div>

    <div class="bg-white border-l-4 border-green-500 rounded-lg p-6 shadow-sm">
        <div class="flex gap-4">
            <div class="flex-shrink-0">
                <div class="flex items-center justify-center h-12 w-12 rounded-md bg-green-100">
                    <span class="text-xl font-bold text-green-600">2</span>
                </div>
            </div>
            <div class="flex-grow">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Füllen Sie das Formular aus</h3>
                <p class="text-gray-600 mb-3">Im Formular <strong>"Pflichtstunden eintragen"</strong> müssen Sie folgende Felder ausfüllen:</p>
                <ul class="list-disc list-inside text-gray-600 space-y-2">
                    <li><strong>Startdatum und -uhrzeit:</strong> Wann haben Sie angefangen?</li>
                    <li><strong>Enddatum und -uhrzeit:</strong> Wann haben Sie beendet?</li>
                    <li><strong>Grund:</strong> Was haben Sie gemacht? (z.B. "Helfen beim Schulfest", "Gartenarbeit", "Reinigung")</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="bg-white border-l-4 border-green-500 rounded-lg p-6 shadow-sm">
        <div class="flex gap-4">
            <div class="flex-shrink-0">
                <div class="flex items-center justify-center h-12 w-12 rounded-md bg-green-100">
                    <span class="text-xl font-bold text-green-600">3</span>
                </div>
            </div>
            <div class="flex-grow">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Speichern Sie die Eintragung</h3>
                <p class="text-gray-600">Klicken Sie auf <strong>"Pflichtstunden eintragen"</strong> um Ihre Eintragung einzureichen.</p>
            </div>
        </div>
    </div>

    <div class="bg-white border-l-4 border-blue-500 rounded-lg p-6 shadow-sm">
        <div class="flex gap-4">
            <div class="flex-shrink-0">
                <div class="flex items-center justify-center h-12 w-12 rounded-md bg-blue-100">
                    <span class="text-xl font-bold text-blue-600">4</span>
                </div>
            </div>
            <div class="flex-grow">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Warten Sie auf Bestätigung</h3>
                <p class="text-gray-600">Ihre Eintragung wird überprüft und genehmigt. Sie sehen den Status in Ihrer Übersicht:</p>
                <div class="mt-3 space-y-2 text-sm">
                    <p><span class="inline-flex items-center gap-1 px-2 py-1 bg-amber-100 text-amber-700 rounded-full">
                        <i class="fas fa-hourglass-half"></i> In Bearbeitung
                    </span> - wird noch überprüft</p>
                    <p><span class="inline-flex items-center gap-1 px-2 py-1 bg-green-100 text-green-700 rounded-full">
                        <i class="fas fa-check-circle"></i> Bestätigt
                    </span> - wird gezählt</p>
                    <p><span class="inline-flex items-center gap-1 px-2 py-1 bg-red-100 text-red-700 rounded-full">
                        <i class="fas fa-times-circle"></i> Abgelehnt
                    </span> - wurde nicht akzeptiert</p>
                </div>
            </div>
        </div>
    </div>
</div>
HTML
        ]);

        SiteBlock::create([
            'site_id' => $site->id,
            'block_id' => $erfassung->id,
            'block_type' => SiteBlockText::class,
            'position' => 2,
            'title' => null,
        ]);

        // Block 3: Gamification erklärt
        $gamification = SiteBlockText::create([
            'content' => <<<'HTML'
<div class="space-y-4">
    <h2 class="text-2xl font-bold text-gray-900 mb-4">
        <i class="fas fa-chart-line text-purple-600 mr-2"></i>
        Ihr Fortschritt und Ranking
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-6">
            <div class="flex items-center gap-3 mb-3">
                <i class="fas fa-chart-line text-blue-600 text-2xl"></i>
                <h3 class="text-lg font-semibold text-gray-900">Fortschritt</h3>
            </div>
            <p class="text-gray-600 text-sm mb-3">Zeigt an, wie viele Stunden Sie bereits geleistet haben.</p>
            <div class="bg-white rounded p-3 text-center">
                <p class="text-2xl font-bold text-blue-600">75%</p>
                <p class="text-xs text-gray-500 mt-1">Beispiel: 3 von 4 Stunden</p>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-50 to-pink-50 border border-purple-200 rounded-lg p-6">
            <div class="flex items-center gap-3 mb-3">
                <i class="fas fa-trophy text-purple-600 text-2xl"></i>
                <h3 class="text-lg font-semibold text-gray-900">Ranking</h3>
            </div>
            <p class="text-gray-600 text-sm mb-3">Zeigt an, auf welchem Platz Sie bei den Familien sind.</p>
            <div class="bg-white rounded p-3 text-center">
                <p class="text-2xl font-bold text-purple-600">5. Platz</p>
                <p class="text-xs text-gray-500 mt-1">von 210 Familien</p>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-50 to-emerald-50 border border-green-200 rounded-lg p-6">
            <div class="flex items-center gap-3 mb-3">
                <i class="fas fa-chart-bar text-green-600 text-2xl"></i>
                <h3 class="text-lg font-semibold text-gray-900">Vergleich</h3>
            </div>
            <p class="text-gray-600 text-sm mb-3">Vergleicht Ihren Fortschritt mit dem Durchschnitt aller Familien.</p>
            <div class="bg-white rounded p-3 text-center">
                <p class="text-sm font-semibold text-green-600">+10% über Ø</p>
                <p class="text-xs text-gray-500 mt-1">Sie leisten mehr als der Durchschnitt</p>
            </div>
        </div>
    </div>

    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg mt-4">
        <h4 class="font-semibold text-yellow-900 mb-2">
            <i class="fas fa-info-circle mr-2"></i>
            Wichtig: Ranking bei Gleichstand
        </h4>
        <p class="text-yellow-800 text-sm">
            Wenn mehrere Familien die gleichen Stunden geleistet haben, bekommen <strong>alle den schlechtesten Rang dieser Gruppe</strong>.
            Das ist fair und transparent für alle.
        </p>
    </div>
</div>
HTML
        ]);

        SiteBlock::create([
            'site_id' => $site->id,
            'block_id' => $gamification->id,
            'block_type' => SiteBlockText::class,
            'position' => 3,
            'title' => null,
        ]);

        // Block 4: Häufig gestellte Fragen
        $faq = SiteBlockText::create([
            'content' => <<<'HTML'
<div class="space-y-4">
    <h2 class="text-2xl font-bold text-gray-900 mb-4">
        <i class="fas fa-question text-orange-600 mr-2"></i>
        Häufig gestellte Fragen
    </h2>

    <details class="bg-white border border-gray-200 rounded-lg p-4 group" open>
        <summary class="cursor-pointer font-semibold text-gray-900 flex items-center gap-2">
            <i class="fas fa-chevron-right group-open:rotate-90 transition-transform text-gray-400"></i>
            Werden mein Partner und ich einzeln gewertet?
        </summary>
        <p class="text-gray-600 mt-3 ml-6">
            <strong>Nein!</strong> Sie und Ihr Partner/Ihre Partnerin werden als <strong>eine Familie</strong> gewertet.
            Alle von Ihnen gemeinsam geleisteten Stunden werden zusammengezählt. Das ist gerechter für Familien mit zwei Sorgeberechtigten.
        </p>
    </details>

    <details class="bg-white border border-gray-200 rounded-lg p-4 group">
        <summary class="cursor-pointer font-semibold text-gray-900 flex items-center gap-2">
            <i class="fas fa-chevron-right group-open:rotate-90 transition-transform text-gray-400"></i>
            Was passiert, wenn meine Eintragung abgelehnt wird?
        </summary>
        <p class="text-gray-600 mt-3 ml-6">
            Wenn Ihre Eintragung abgelehnt wird, erhalten Sie eine Benachrichtigung mit dem Grund.
            Sie können dann die Informationen überprüfen und eine neue Eintragung vornehmen.
        </p>
    </details>

    <details class="bg-white border border-gray-200 rounded-lg p-4 group">
        <summary class="cursor-pointer font-semibold text-gray-900 flex items-center gap-2">
            <i class="fas fa-chevron-right group-open:rotate-90 transition-transform text-gray-400"></i>
            Wie lange dauert es, bis meine Stunden bestätigt werden?
        </summary>
        <p class="text-gray-600 mt-3 ml-6">
            Das hängt von der Schule/Einrichtung ab. Normalerweise werden Eintragungen innerhalb weniger Tage überprüft.
            Sie können den Status jederzeit in Ihrer Übersicht einsehen.
        </p>
    </details>

    <details class="bg-white border border-gray-200 rounded-lg p-4 group">
        <summary class="cursor-pointer font-semibold text-gray-900 flex items-center gap-2">
            <i class="fas fa-chevron-right group-open:rotate-90 transition-transform text-gray-400"></i>
            Was zählt als Pflichtstunde?
        </summary>
        <p class="text-gray-600 mt-3 ml-6">
            Typische Pflichtstunden sind:
        </p>
        <ul class="list-disc list-inside text-gray-600 mt-2 ml-6 space-y-1">
            <li>Schulfeste und Schulveranstaltungen</li>
            <li>Gartenarbeit und Schulhofgestaltung</li>
            <li>Hilfe bei der Schulreinigung</li>
            <li>Aufsichtsdienste</li>
            <li>Unterstützung bei schulischen Projekten</li>
        </ul>
        <p class="text-gray-600 mt-2 ml-6 text-sm">
            <strong>Fragen Sie die Schulleitung oder Elternvertretung,</strong> wenn Sie sich unsicher sind, ob eine Tätigkeit zählt.
        </p>
    </details>

    <details class="bg-white border border-gray-200 rounded-lg p-4 group">
        <summary class="cursor-pointer font-semibold text-gray-900 flex items-center gap-2">
            <i class="fas fa-chevron-right group-open:rotate-90 transition-transform text-gray-400"></i>
            Was passiert, wenn ich die Pflichtstunden nicht erfülle?
        </summary>
        <p class="text-gray-600 mt-3 ml-6">
            Jede Schule/Einrichtung hat ihre eigenen Regelungen.
            Normalerweise müssen Sie einen Ausgleichsbeitrag zahlen.
            Der genaue Betrag wird Ihnen in Ihrer Übersicht angezeigt.
        </p>
    </details>
</div>
HTML
        ]);

        SiteBlock::create([
            'site_id' => $site->id,
            'block_id' => $faq->id,
            'block_type' => SiteBlockText::class,
            'position' => 4,
            'title' => null,
        ]);

        // Block 5: Tipps und Tricks
        $tips = SiteBlockText::create([
            'content' => <<<'HTML'
<div class="space-y-4">
    <h2 class="text-2xl font-bold text-gray-900 mb-4">
        <i class="fas fa-lightbulb text-yellow-600 mr-2"></i>
        Tipps für die Erfassung
    </h2>

    <div class="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-lg">
        <h4 class="font-semibold text-blue-900 mb-2">
            <i class="fas fa-clock mr-2"></i>
            Timing ist wichtig
        </h4>
        <p class="text-blue-800 text-sm">
            Erfassen Sie Ihre Stunden zeitnah nach dem Ereignis. Das macht die Eintragung genauer und erleichtert die Überprüfung.
        </p>
    </div>

    <div class="bg-green-50 border-l-4 border-green-500 p-6 rounded-lg">
        <h4 class="font-semibold text-green-900 mb-2">
            <i class="fas fa-pencil mr-2"></i>
            Seien Sie präzise
        </h4>
        <p class="text-green-800 text-sm">
            Geben Sie genaue Start- und Endzeiten an. Schreiben Sie auch eine ausführliche Beschreibung,
            was Sie gemacht haben. Das erhöht die Chance auf Genehmigung.
        </p>
    </div>

    <div class="bg-purple-50 border-l-4 border-purple-500 p-6 rounded-lg">
        <h4 class="font-semibold text-purple-900 mb-2">
            <i class="fas fa-calendar mr-2"></i>
            Planen Sie voraus
        </h4>
        <p class="text-purple-800 text-sm">
            Schauen Sie nach Schulveranstaltungen und Gelegenheiten zur Ableistung von Pflichtstunden.
            So erreichen Sie Ihr Ziel rechtzeitig.
        </p>
    </div>

    <div class="bg-orange-50 border-l-4 border-orange-500 p-6 rounded-lg">
        <h4 class="font-semibold text-orange-900 mb-2">
            <i class="fas fa-users mr-2"></i>
            Koordinieren Sie mit Ihrem Partner
        </h4>
        <p class="text-orange-800 text-sm">
            Da Sie als Familie gewertet werden, können beide Sorgeberechtigte Stunden erbringen.
            Teilen Sie sich die Aufgaben, wie es für Ihre Familie passt.
        </p>
    </div>
</div>
HTML
        ]);

        SiteBlock::create([
            'site_id' => $site->id,
            'block_id' => $tips->id,
            'block_type' => SiteBlockText::class,
            'position' => 5,
            'title' => null,
        ]);

        // Block 6: Kontakt und Hilfe
        $contact = SiteBlockText::create([
            'content' => <<<'HTML'
<div class="bg-gradient-to-r from-indigo-50 to-blue-50 border-l-4 border-indigo-600 p-6 rounded-lg">
    <h2 class="text-2xl font-bold text-indigo-900 mb-4">
        <i class="fas fa-headset mr-2"></i>
        Fragen oder Probleme?
    </h2>
    <p class="text-indigo-800 mb-4">
        Wenn Sie Fragen zur Erfassung von Pflichtstunden haben oder auf Probleme stoßen:
    </p>
    <ul class="space-y-2 text-indigo-800">
        <li class="flex items-start gap-3">
            <i class="fas fa-envelope mt-1 flex-shrink-0"></i>
            <span><strong>Kontaktieren Sie die Schulleitung</strong> oder die Elternvertretung</span>
        </li>
        <li class="flex items-start gap-3">
            <i class="fas fa-comments mt-1 flex-shrink-0"></i>
            <span><strong>Nutzen Sie die Nachrichtenfunction</strong> im Portal, um Fragen zu stellen</span>
        </li>
        <li class="flex items-start gap-3">
            <i class="fas fa-circle-question mt-1 flex-shrink-0"></i>
            <span><strong>Schauen Sie in der Hilfe nach,</strong> ob Ihre Frage bereits beantwortet wurde</span>
        </li>
    </ul>
</div>
HTML
        ]);

        SiteBlock::create([
            'site_id' => $site->id,
            'block_id' => $contact->id,
            'block_type' => SiteBlockText::class,
            'position' => 6,
            'title' => null,
        ]);

        echo "✅ Pflichtstunden Hilfe Site wurde erstellt!\n";
        echo "Sie können die Seite unter: https://yoursite.local/sites/show/{$site->id} aufrufen.\n";
    }
}

