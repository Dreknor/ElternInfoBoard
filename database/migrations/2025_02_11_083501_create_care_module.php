<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $module = new \App\Model\Module([
           'setting' => 'Anwesenheitsliste',
            'description' => "digitale Anwesenheitsliste der Kinder",
            'category' => 'module',
            'options' =>
            [
                "active" => "0",
                'rights' => [   ],
                "adm-nav" => [
                    "adm-rights" => ["edit schickzeiten"],
                    "name" => "Anwesenheit",
                    "link" => "care/anwesenheit",
                    "icon" => "fa-solid fa-children"
                ]
            ],
            'created_at' => now(),
        ]);

        $module->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \App\Model\Module::where('setting', 'Anwesenheitsliste')->delete();
    }
};
