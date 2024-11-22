<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        $options = [
            'active' => '1',
            'rights' => [],
            'adm-nav' => [
                'adm-rights' => [
                    '0' => 'edit settings',
                ],
                'name' => 'Einstellungen',
                'link' => 'settings',
                'icon' => 'fas fa-cogs',
                'permission' => 'edit settings',
            ],
        ];

        $setting =
            [
                'setting' => 'Settings',
                'description' => 'Einstellungen für die Anwendung',
                'category' => 'module',
                'options' => $options,
            ];

        $module = \App\Model\Module::query()->create($setting);

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \App\Model\Module::query()->where('setting', 'Settings')->delete();
    }
};
