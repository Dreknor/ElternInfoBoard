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
        $permission = new Spatie\Permission\Models\Permission([
            'name' => 'role is assignable',
            'guard_name' => 'web'
        ]);
        $permission->save();

        $permission = new Spatie\Permission\Models\Permission([
            'name' => 'assign roles to users',
            'guard_name' => 'web'
        ]);
        $permission->save();


        \Illuminate\Support\Facades\Artisan::call('cache:clear');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('roles', function (Blueprint $table) {
            //
        });
    }
};
