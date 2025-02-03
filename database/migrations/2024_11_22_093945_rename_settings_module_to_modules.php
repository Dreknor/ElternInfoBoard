<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
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
        } catch (Exception $e) {
        }


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('modules', function (Blueprint $table) {
            //
        });
    }
};
