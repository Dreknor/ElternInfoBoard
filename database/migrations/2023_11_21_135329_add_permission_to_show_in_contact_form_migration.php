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
        $permission = new Spatie\Permission\Models\Permission(
            ['name' => 'show in contact form',
                'guard_name' => 'web',
                'created_at' => Carbon\Carbon::now(),
                'updated_at' => Carbon\Carbon::now(),
            ]
        );
        $permission->save();

        \Illuminate\Support\Facades\Cache::clear();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('show_in_contact_form_migration', function (Blueprint $table) {
            //
        });
    }
};
