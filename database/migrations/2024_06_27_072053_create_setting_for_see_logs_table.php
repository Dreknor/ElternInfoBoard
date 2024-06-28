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

        $permission = new Spatie\Permission\Models\Permission(
            ['name' => 'see logs',
                'guard_name' => 'web',
                'created_at' => Carbon\Carbon::now(),
                'updated_at' => Carbon\Carbon::now(),
            ]);

        $permission->save();

        \App\Model\Settings::insert([
            'setting' => 'Logs',
            'description' => 'Zeigt die geloggten Ereignisse.',
            'category' => 'module',
            'options' => '{
            "active":"1",
            "rights":[],
            "adm-nav":
                {"adm-rights":["see logs"],"name":"logs","link":"logs","icon":"fas fa-stream"}
            }',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('setting_for_see_logs');
    }
};
