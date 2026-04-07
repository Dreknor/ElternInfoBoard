<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StundenplanSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'group' => 'stundenplan',
                'name' => 'import_api_key',
                'locked' => 0,
                'payload' => json_encode(Str::random(64)),
            ],
            [
                'group' => 'stundenplan',
                'name' => 'import_api_url',
                'locked' => 0,
                'payload' => json_encode(url('/api/stundenplan/import') . '?key={YOUR_API_KEY}'),
            ],
            [
                'group' => 'stundenplan',
                'name' => 'allow_web_import',
                'locked' => 0,
                'payload' => json_encode(true),
            ],
            [
                'group' => 'stundenplan',
                'name' => 'allow_api_import',
                'locked' => 0,
                'payload' => json_encode(true),
            ],
            [
                'group' => 'stundenplan',
                'name' => 'show_absent_teachers',
                'locked' => 0,
                'payload' => json_encode(true),
            ],
        ];

        foreach ($settings as $setting) {
            // Check if setting already exists
            $exists = DB::table('settings')
                ->where('group', $setting['group'])
                ->where('name', $setting['name'])
                ->exists();

            if (!$exists) {
                DB::table('settings')->insert($setting);
                if ($this->command) {
                    $this->command->info("Created setting: {$setting['group']}.{$setting['name']}");
                }
            } else {
                if ($this->command) {
                    $this->command->info("Setting already exists: {$setting['group']}.{$setting['name']}");
                }
            }
        }

        if ($this->command) {
            $this->command->info('Stundenplan settings initialized successfully!');
        }
    }
}

