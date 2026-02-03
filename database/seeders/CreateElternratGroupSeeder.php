<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CreateElternratGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        DB::table('groups')->insert([
            [
                'name' => 'Elternrat',
                'protected' => 1,
            ],
        ]);
    }
}
