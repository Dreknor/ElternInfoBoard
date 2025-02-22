<?php

namespace Database\Seeders;

use Carbon\Carbon;
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

        $permission= [
            [
                'name' => "view external offer",
                'guard_name' => 'web'
            ],[
                'name' => "push to wordpress",
                'guard_name' => 'web'
            ], [
                'name' => 'download krankmeldungen',
                'guard_name' => 'web',
            ], [
                'name' => 'allow password-less-login',
                'guard_name' => 'web',
            ], [
                'name' => 'create own group',
                'guard_name' => 'web',
            ], ['name' => 'show in contact form',
                'guard_name' => 'web',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ], ['name' => 'manage diseases',
                'guard_name' => 'web',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ], ['name' => 'see diseases',
                'guard_name' => 'web',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ], ['name' => 'delete posts',
                'guard_name' => 'web',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ], ['name' => 'see logs',
                'guard_name' => 'web',

            ], [
                'name' => 'view sites',
                'guard_name' => 'web',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ], [
                'name' => 'create sites',
                'guard_name' => 'web',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];




        DB::table('permissions')->insert($permission);




    }
}
