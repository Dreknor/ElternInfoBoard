<?php

namespace Database\Seeders;

use App\Model\Module;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateModuleSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('settings_modules')->insert([
            'setting' => 'bearbeite Rueckmeldungen',
            'category' => 'module',
            'options' => '
            {
                "active":"0",
                "rights":{},
                "adm-nav":
                    {"adm-rights":
                        ["manage rueckmeldungen"],
                        "name":"Rückmeldungen",
                        "link":"rueckmeldungen",
                        "icon":"fas fa-comment-dots"
                    }
            }',
            'created_at' => Carbon::now(),
        ]);


        // Update Losungen Settings
        $setting = Module::where('setting', 'Losung')->first();

        $settings = [
            'id' => ($setting ? $setting->id : rand(100000, 999999)),
            'setting' => 'Losung',
            'description' => 'Zeigt im Nachrichtenbereich die Tageslosung an. Die Losungen müssen jedes Jahr aus der aktuellen csv-Datei importiert werden.',
            'category' => 'module',
            'options' => '{"active":"1","rights":[],"home-view-top":"include.losung","adm-nav":{"adm-rights":["edit settings"],"name":"Losung importieren","link":"settings\/losungen\/import","icon":"fas fa-file-import"}}'
        ];

        if ($setting) {
            $setting->delete();
        }

        DB::table('settings_modules')->insert($settings);


        Module::where('setting', 'Gruppen')->update([
            'options' => '{"active":"1","rights":["view groups"],"nav":{"name":"Gruppen","link":"groups","icon":"fas fa-user-friends","bottom-nav":"true"}}'
        ]);


        DB::table('settings_modules')->insert([
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
        ]);
    }
}
