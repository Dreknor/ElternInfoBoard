<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UpdateGroupsPermission extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles = \Spatie\Permission\Models\Role::all();

        foreach ($roles as $role) {
            if ($role->hasPermissionTo('view groups')) {
                $role->revokePermissionTo('view groups')->givePermissionTo('edit groups');
            }
        }
    }
}
