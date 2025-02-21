<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class UpdatePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('permissions')->insert([
            'name' => 'create polls',
            'guard_name' => 'web',
        ]);

        DB::table('permissions')->insert([
            'name' => 'delete groups',
            'guard_name' => 'web',
        ]);

        DB::table('permissions')->insert([
            'name' => 'see mails',
            'guard_name' => 'web',
        ]);

        DB::table('permissions')->insert([
            'name' => 'manage rueckmeldungen',
            'guard_name' => 'web',
        ]);

        DB::table('permissions')->insert([
            'name' => 'assign roles to users',
            'guard_name' => 'web',
        ]);

        DB::table('permissions')->insert([
            'name' => 'role is assignable',
            'guard_name' => 'web',
        ]);

        DB::table('permissions')->insert([
            'name' => 'edit groups',
            'guard_name' => 'web',
        ]);


    }
}
