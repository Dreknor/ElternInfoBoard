<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateUsersPublicMailPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('settings_modules')->where('setting', 'Gruppen')->update([
            'options' => '{"active":"0","rights":[],"nav":{"name":"Gruppen","link":"groups","icon":"fas fa-user-friends"}}',
        ]);
    }
}
