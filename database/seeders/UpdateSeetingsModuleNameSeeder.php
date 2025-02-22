<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class UpdateSeetingsModuleNameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        try {
            $modul = \App\Model\Module::query()->where('setting', 'Settings')->first();
            $modul->setting = 'Modules';
            $options = $modul->options;
            if (array_key_exists('adm-nav', $modul->options)) {
                if (array_key_exists('link', $options['adm-nav'])) {
                    $options['adm-nav']['link'] = 'modules';
                }
                $modul->options = $options;
            }

            $modul->save();
            Cache::forget('modules');
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}
