<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $permission = DB::table('permissions')->insert([
            'name' => 'edit groups',
            'guard_name' => 'web',
        ]);


        \App\Model\Module::where('setting', 'Gruppen')->update([
            'options' => '{"active":"1","rights":["view groups"],"nav":{"name":"Gruppen","link":"groups","icon":"fas fa-user-friends","bottom-nav":"true"}}'
        ]);


        $roles = \Spatie\Permission\Models\Role::all();

        foreach ($roles as $role) {
            if ($role->hasPermissionTo('view groups')) {
                $role->revokePermissionTo('view groups')->givePermissionTo('edit groups');
            }
        }

        exec('php artisan cache:clear');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
