<?php

namespace Database\Seeders;

use App\Model\Module;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateModuleSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        Module::query()->where('setting', 'Losung')->delete();

        Module::where('setting', 'Gruppen')->update([
            'options' => '{"active":"1","rights":["view groups"],"nav":{"name":"Gruppen","link":"groups","icon":"fas fa-user-friends","bottom-nav":"true"}}'
        ]);


        $settings = [
            [
                'id' => 15000,
                'setting' => 'Losung',
                'description' => 'Zeigt im Nachrichtenbereich die Tageslosung an. Die Losungen müssen jedes Jahr aus der aktuellen csv-Datei importiert werden.',
                'category' => 'module',
                'options' => json_encode([
                    'active' => '1',
                    'rights' => [],
                    "home-view-top" => "include.losung",
                    'nav' => [],
                    'adm-nav' => [
                        'adm-rights' => ['edit settings'],
                        'name' => 'Losung importieren',
                        'link' => 'settings/losungen/import',
                        'icon' => 'fas fa-file-import',
                    ],
                ])
            ],
            [
                'id' => 1150,
                'setting' => 'externe Angebote',
                'description' => 'Nachrichten können als externes Angebot gekennzeichnet werden. Diese erhalten einen eigenen Bereich ähnlich dem Archiv.',
                'category' => 'module',
                'options' => '
                {
                    "active":"1",
                    "rights":{"0":"view external offer"},
                    "nav":
                    {
                        "name":"ex. Angebot",
                        "link":"external",
                        "icon":"fas  fa-info"
                    }
                }',
            ],
            [
                'setting' => 'Push to WordPress',
                'description' => 'Posts können WordPress-Seite geschickt werden. Bedingung ist das Ausfüllen der Daten in der .env-Datei sowie die Vergabe des entsprechenden Rechtes.',
                'category' => 'setting',
                'options' => '{"active":"0"}',
            ], [
                'setting' => 'Logs',
                'description' => 'Zeigt die geloggten Ereignisse.',
                'category' => 'module',
                'options' => '{
                    "active":"1",
                    "rights":[],
                    "adm-nav":
                        {"adm-rights":["see logs"],"name":"logs","link":"logs","icon":"fas fa-stream"}
                    }',
            ], [
                'setting' => 'Seiten',
                'description' => 'Erlaubt das Anlegen und Verwalten von Seiten',
                'category' => 'module',
                'options' => json_encode([
                    "active"=>"0",
                    "rights" => json_encode(["view sites"]),
                    "nav"=>json_encode([
                        "name"=>"Seiten",
                        "link"=>"sites",
                        "icon"=>"fa fa-file",
                        "bottom-nav"=>"false"
                    ]),
                    "adm-nav"=>json_encode([
                        "adm-rights"=>json_encode(["create sites"]),
                        "name"=>"neue Seite",
                        "link"=>"sites/create",
                        "icon"=>"fa fa-file-pen"
                    ])
                ]),
            ],  [
                'setting' => 'Settings',
                'description' => 'Einstellungen für die Anwendung',
                'category' => 'module',
                'options' => json_encode([
                    'active' => '1',
                    'rights' => json_encode([]),
                    'adm-nav' => json_encode([
                        'adm-rights' => [
                            '0' => 'edit settings',
                        ],
                        'name' => 'Einstellungen',
                        'link' => 'settings',
                        'icon' => 'fas fa-cogs',
                        'permission' => 'edit settings',
                    ]),
                ])
                ], [
                    'setting' => 'Anwesenheitsliste',
                    'description' => "digitale Anwesenheitsliste der Kinder",
                    'category' => 'module',
                    'options' => json_encode([
                            "active" => "0",
                            'rights' => [],
                            "adm-nav" => [
                                "adm-rights" => ["edit schickzeiten"],
                                "name" => "Anwesenheit",
                                "link" => "care/anwesenheit",
                                "icon" => "fa-solid fa-children"
                            ]
                        ]),
                    'created_at' => now(),
                ], [
                    'setting' => 'Kinderverwaltung',
                    'description' => "Verwaltung der angelegten Kinder",
                    'category' => 'module',
                    'options' => json_encode([
                            "active" => "0",
                            'rights' => [],
                            "adm-nav" => [
                                "adm-rights" => ["edit schickzeiten"],
                                "name" => "Kinder",
                                "link" => "care/children",
                                "icon" => "fa-solid fa-children"
                            ]
                        ]),
                    'created_at' => now(),
                ],
                [
                    'setting' => 'bearbeite Rueckmeldungen',
                    'category' => 'module',
                    'options' => json_encode([
                        'active' => '0',
                        'rights' => [],
                        'adm-nav' => [
                            'adm-rights' => ['manage rueckmeldungen'],
                            'name' => 'Rückmeldungen',
                            'link' => 'rueckmeldungen',
                            'icon' => 'fas fa-comment-dots',
                        ],
                    ]),
                    'created_at' => Carbon::now(),
                ],
        ];

        foreach ($settings as $setting) {
            Log::info('UpdateModuleSettingsSeeder: ' . $setting['setting']);
            Module::insert($setting);
        }
    }
}
