<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('setting');
            $table->text('description')->nullable();
            $table->string('category');
            $table->json('options');
            $table->timestamps();
        });

        DB::table('permissions')->insert(
            [
                [
                    'name' => 'edit settings',
                    'guard_name' => 'web',
                ],
            ]
        );

        /**
         * Export to PHP Array plugin for PHPMyAdmin
         *
         * @version 5.0.4
         */

        /**
         * Database `eszinfo`
         */

        /* `eszinfo`.`settings` */
        $settings = [
            [
                'setting' => 'Losung',
                'description' => 'Zeigt im Nachrichtenbereich die Tageslosung an. Die Losungen müssen jedes Jahr aus der aktuellen csv-Datei importiert werden.',
                'category' => 'module',
                'options' => '{"active":"0","rights":[],"home-view-top":"include.losung"}',
            ],
            [
                'setting' => 'Changelog',
                'description' => 'Im Benutzmenü wird der Link Changelog hinzugefügt. Hier können Anpassungen an der Software dokumentiert werden sowie die Nutzer auf Änderungen in den Profil-Einstellungen hingewiesen werden.',
                'category' => 'module',
                'options' => '{"active":"0","rights":[],"nav-user":{"name":"Changelog","link":"changelog"}}',
            ],
            [
                'setting' => 'Nachrichten',
                'description' => 'Posts an die verschiedenen Gruppen. Die Können um Rückläufe ergänzt werden.',
                'category' => 'module',
                'options' => '{"active":"0","rights":[],"home-view":"nachrichten.start","nav":{"name":"Nachrichten","link":"\\/","icon":"far fa-newspaper", "bottom-nav":"false"},"adm-nav":{"adm-rights":["create posts"],"name":"neue Nachricht","link":"posts\\/create","icon":"fas fa-pen"}}',
            ],
            [
                'setting' => 'Termine',
                'description' => 'Ermöglich das veröffentlichen einer Terminübersicht im Nachrichtenbereich. Die Nutzer haben die Möglichkeit Termine direkt in ihren Kalender zu exportieren.',
                'category' => 'module',
                'options' => '{"active":"0","rights":[],"home-view-top":"termine.nachricht","adm-nav":{"adm-rights":["edit termin"],"name":"neuer Termin","link":"termin\\/create","icon":"far fa-calendar-alt"}}',
            ],
            [
                'setting' => 'Archiv',
                'description' => 'Abgelaufene Nachrichten können über das Archiv nachgelesen werden oder für eine erneute Veröffentlichung kopiert werden.',
                'category' => 'module',
                'options' => '{"active":"1","rights":[],"nav":{"name":"Archiv","link":"archiv","icon":"fas fa-archive", "bottom-nav":"false"}}',
            ],
            [
                'setting' => 'Dateien',
                'description' => 'Bereitstellung von Dateien, die nicht einer speziellen Nachricht zuzuordnen sind (Bsp.: Konzept, Gebührenverodnung,...)',
                'category' => 'module',
                'options' => '{"active":"0","rights":[],"nav":{"name":"Downloads","link":"files","icon":"fa fa-download", "bottom-nav":"false"},"adm-nav":{"adm-rights":["upload files"],"name":"Datei hochladen","link":"files\\/create","icon":"fas fa-upload"}}',
            ],
            [
                'setting' => 'Krankmeldung',
                'description' => 'Sorgeberechtigte können Krankmeldungen absenden. Diese erhält die Verwaltung per Mail sofort und eine Zusammenfassung tagesaktuell',
                'category' => 'module',
                'options' => '{"active":"0","rights":["view krankmeldung"],"nav":{"name":"Krankmeldung","link":"krankmeldung","icon":"fas fa-medkit", "bottom-nav":"false"}}',
            ],
            [
                'setting' => 'Reinigung',
                'description' => 'Für die Einteilung von Reinigungsdiensten durch die Familien',
                'category' => 'module',
                'options' => '{"active":"0","rights":[],"home-view":"reinigung.nachricht","nav":{"name":"Reinigungsplan","link":"reinigung","icon":"fas fa-broom", "bottom-nav":"false"}}',
            ],
            [
                'setting' => 'Listen',
                'description' => 'Ermöglicht die Erstellung von Terminlisten. Nutzer können diese Termine dann reservieren. Nutzung für Elterngespräche oder ähnliches.',
                'category' => 'module',
                'options' => '{"active":"0","rights":[],"nav":{"name":"Listen","link":"listen","icon":"far fa-list-alt", "bottom-nav":"false"},"adm-nav":{"adm-rights":["create terminliste"],"name":"neue Liste","link":"listen\\/create","icon":"far fa-list-alt"}}',
            ],
            [
                'setting' => 'Schickzeiten',
                'description' => 'Sorgeberechtigte tragen regelmäßige Zeiten ein, zu denen Ihr Kind die Schule verlassen soll.',
                'category' => 'module',
                'options' => '{"active":"0","rights":["view schickzeiten"],"nav":{"name":"Schickzeiten","link":"schickzeiten","icon":"fas fa-clock", "bottom-nav":"false"},"adm-nav":{"adm-rights":["edit schickzeiten"],"name":"Schickzeitenliste","link":"verwaltung\\/schickzeiten","icon":"fas fa-clock"}}',
            ],
            [
                'setting' => 'Kontakt',
                'description' => 'Bietet ein Kontaktformular zu den Mitarbeitern. Nachrichten werden an die Mail des Mitarbeiters versandt.',
                'category' => 'module',
                'options' => '{"active":"0","rights":[],"nav":{"name":"Kontakt","link":"feedback","icon":"far fa-comment", "bottom-nav":"false"}}',
            ],
            [
                'setting' => 'Elternrat',
                'description' => 'Ein eigener geschützer Bereich für den Elternrat. Bietet die Möglichkeit der Diskussion und der Dateiablage.',
                'category' => 'module',
                'options' => '{"active":"0","rights":["view elternrat"],"home-view":"","nav":{"name":"Elternrat","link":"elternrat","icon":"fas fa-user-friends", "bottom-nav":"false"}}',
            ],
            [
                'setting' => 'Rechte',
                'description' => 'Rollen und Rechtevergabe.',
                'category' => 'module',
                'options' => '
                {
                    "active":"1",
                    "rights": {},
                    "adm-nav":
                        {
                            "adm-rights":
                                {
                                    "0":"edit permission"
                                },
                            "name": "Rechte",
                            "link":"roles",
                            "icon":"fas fa-user-tag"
                        }
                }',
            ],
            [
                'setting' => 'Einstellungen',
                'description' => 'aktiviert die Benutzereinstellungen',
                'category' => 'module',
                'options' => '{"active":"1","rights":{},"nav-user":{"name":"Einstellungen","link":"einstellungen"}}',
            ],
            [
                'setting' => 'Benutzerverwaltung',
                'description' => 'Benutzerverwaltung durch die Schule',
                'category' => 'module',
                'options' => '
                    {
                        "active":"1",
                        "rights":{},
                        "adm-nav":
                            {
                                "adm-rights":
                                {
                                    "0":"edit user"
                                },
                                "name":"Benutzer",
                                "link":"users",
                                "icon":"fas fa-user"
                            }
                    }',
            ],
            [
                'setting' => 'Settings',
                'description' => 'Module können aktiviert und deaktiviert werden.',
                'category' => 'module',
                'options' => '
                    {
                        "active":"1",
                        "rights":{},
                        "adm-nav":
                            {
                                "adm-rights":
                                    {"0":"edit settings"},
                                    "name": "Module",
                                    "link":"settings",
                                    "icon":"fas fa-wrench"
                            }
                    }',
            ],
            [
                'setting' => 'Gruppen',
                'description' => 'übersicht über vorhandene Gruppen und deren Nutzer sowie Erstellung weiterer Gruppen. ',
                'category' => 'module',
                'options' => '
                {
                    "active":"1",
                    "rights":{},
                    "adm-nav":
                    {
                        "adm-rights":
                            {
                                "0":"view groups"
                            },
                            "name":"Gruppen",
                            "link":"groups",
                            "icon":"fas fa-user-friends"
                    }
                }',
            ],
            [
                'setting' => 'Datenschutz',
                'description' => 'Benutzer erhalten eine Übersicht über gespeicherte Daten innerhalb des Boards.',
                'category' => 'module',
                'options' => '{"active":"0","rights":{},"nav-user":{"name":"Datenschutz","link":"datenschutz"}}',
            ],
        ];
        DB::table('settings')->insert($settings);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
